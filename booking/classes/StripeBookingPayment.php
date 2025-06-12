<?php
/**
 * Intégration Stripe pour le module de réservation
 * Nécessite le module officiel Stripe de PrestaShop
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeBookingPayment
{
    private $stripe_module;
    private $context;
    
    public function __construct()
    {
        $this->context = Context::getContext();
        $this->stripe_module = Module::getInstanceByName('stripe_official');
        
        if (!$this->stripe_module || !$this->stripe_module->active) {
            throw new PrestaShopException('Module Stripe non installé ou inactif');
        }
    }
    
    /**
     * Créer une session de paiement Stripe pour une réservation
     */
    public function createPaymentSession(BookerAuthReserved $reservation)
    {
        if (!Configuration::get('BOOKING_STRIPE_ENABLED')) {
            throw new PrestaShopException('Paiements Stripe désactivés');
        }
        
        try {
            // Initialiser Stripe
            \Stripe\Stripe::setApiKey($this->getStripeSecretKey());
            
            $booker = new Booker($reservation->id_booker);
            
            // Calculer les montants
            $main_amount = (int)($reservation->total_price * 100); // en centimes
            $deposit_amount = (int)($reservation->deposit_amount * 100);
            
            // Créer les line items
            $line_items = [];
            
            // Article principal (réservation)
            $line_items[] = [
                'price_data' => [
                    'currency' => strtolower($this->context->currency->iso_code),
                    'product_data' => [
                        'name' => 'Réservation - ' . $booker->name,
                        'description' => $this->formatReservationDescription($reservation),
                        'images' => $this->getBookerImages($booker),
                    ],
                    'unit_amount' => $main_amount,
                ],
                'quantity' => 1,
            ];
            
            // Caution si activée
            if ($deposit_amount > 0) {
                $line_items[] = [
                    'price_data' => [
                        'currency' => strtolower($this->context->currency->iso_code),
                        'product_data' => [
                            'name' => 'Caution (remboursée après utilisation)',
                            'description' => 'Caution de garantie - remboursée automatiquement après utilisation',
                        ],
                        'unit_amount' => $deposit_amount,
                    ],
                    'quantity' => 1,
                ];
            }
            
            // URLs de retour
            $success_url = $this->context->link->getModuleLink(
                'booking', 
                'payment', 
                ['action' => 'success', 'booking_ref' => $reservation->booking_reference],
                true
            );
            
            $cancel_url = $this->context->link->getModuleLink(
                'booking', 
                'payment', 
                ['action' => 'cancel', 'booking_ref' => $reservation->booking_reference],
                true
            );
            
            // Métadonnées pour le suivi
            $metadata = [
                'booking_reference' => $reservation->booking_reference,
                'reservation_id' => $reservation->id,
                'booker_id' => $reservation->id_booker,
                'customer_email' => $reservation->customer_email,
                'booking_date' => $reservation->date_reserved,
                'prestashop_shop_id' => $this->context->shop->id,
            ];
            
            // Configuration de la session Stripe
            $session_config = [
                'payment_method_types' => ['card'],
                'line_items' => $line_items,
                'mode' => 'payment',
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
                'metadata' => $metadata,
                'customer_email' => $reservation->customer_email,
                'locale' => $this->getStripeLocale(),
                'billing_address_collection' => 'required',
                'shipping_address_collection' => [
                    'allowed_countries' => ['FR', 'BE', 'CH', 'ES', 'IT', 'DE'],
                ],
                'payment_intent_data' => [
                    'metadata' => $metadata,
                    'description' => 'Réservation ' . $reservation->booking_reference,
                ],
                'expires_at' => time() + (24 * 60 * 60), // Expire dans 24h
            ];
            
            // Ajouter la capture automatique avec caution
            if ($deposit_amount > 0) {
                $session_config['payment_intent_data']['capture_method'] = 'manual';
            }
            
            // Créer la session
            $session = \Stripe\Checkout\Session::create($session_config);
            
            // Sauvegarder l'ID de session
            $this->saveStripeSession($reservation, $session);
            
            return $session;
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            PrestaShopLogger::addLog('Erreur Stripe: ' . $e->getMessage());
            throw new PrestaShopException('Erreur lors de la création du paiement: ' . $e->getMessage());
        }
    }
    
    /**
     * Traiter le webhook de confirmation de paiement
     */
    public function handleWebhook($payload, $sig_header)
    {
        try {
            $endpoint_secret = Configuration::get('STRIPE_WEBHOOK_SECRET');
            
            $event = \Stripe\Webhook::constructEvent(
                $payload, 
                $sig_header, 
                $endpoint_secret
            );
            
            switch ($event['type']) {
                case 'checkout.session.completed':
                    $this->handlePaymentSuccess($event['data']['object']);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event['data']['object']);
                    break;
                    
                case 'charge.dispute.created':
                    $this->handleDispute($event['data']['object']);
                    break;
                    
                default:
                    PrestaShopLogger::addLog('Webhook Stripe non géré: ' . $event['type']);
            }
            
            return true;
            
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            PrestaShopLogger::addLog('Erreur signature webhook Stripe: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rembourser une réservation
     */
    public function refundReservation(BookerAuthReserved $reservation, $amount = null, $reason = null)
    {
        try {
            \Stripe\Stripe::setApiKey($this->getStripeSecretKey());
            
            // Récupérer l'ID du payment intent
            $payment_intent_id = $this->getPaymentIntentId($reservation);
            
            if (!$payment_intent_id) {
                throw new PrestaShopException('Paiement Stripe introuvable');
            }
            
            $refund_data = [
                'payment_intent' => $payment_intent_id,
                'metadata' => [
                    'booking_reference' => $reservation->booking_reference,
                    'refund_reason' => $reason ?: 'Annulation réservation',
                ],
            ];
            
            if ($amount) {
                $refund_data['amount'] = (int)($amount * 100); // en centimes
            }
            
            $refund = \Stripe\Refund::create($refund_data);
            
            // Mettre à jour le statut de paiement
            $reservation->payment_status = BookerAuthReserved::PAYMENT_REFUNDED;
            $reservation->update();
            
            // Logger
            PrestaShopLogger::addLog(
                'Remboursement Stripe effectué: ' . $refund->id . 
                ' pour réservation ' . $reservation->booking_reference
            );
            
            return $refund;
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            PrestaShopLogger::addLog('Erreur remboursement Stripe: ' . $e->getMessage());
            throw new PrestaShopException('Erreur lors du remboursement: ' . $e->getMessage());
        }
    }
    
    /**
     * Capturer le paiement (pour les cautions)
     */
    public function capturePayment(BookerAuthReserved $reservation, $amount = null)
    {
        try {
            \Stripe\Stripe::setApiKey($this->getStripeSecretKey());
            
            $payment_intent_id = $this->getPaymentIntentId($reservation);
            
            if (!$payment_intent_id) {
                throw new PrestaShopException('Paiement Stripe introuvable');
            }
            
            $capture_data = [];
            if ($amount) {
                $capture_data['amount_to_capture'] = (int)($amount * 100);
            }
            
            $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            $payment_intent->capture($capture_data);
            
            // Mettre à jour le statut
            $reservation->payment_status = BookerAuthReserved::PAYMENT_COMPLETED;
            $reservation->status = BookerAuthReserved::STATUS_PAID;
            $reservation->update();
            
            return $payment_intent;
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            PrestaShopLogger::addLog('Erreur capture Stripe: ' . $e->getMessage());
            throw new PrestaShopException('Erreur lors de la capture: ' . $e->getMessage());
        }
    }
    
    /**
     * Traiter le succès du paiement
     */
    private function handlePaymentSuccess($session)
    {
        $booking_reference = $session['metadata']['booking_reference'] ?? null;
        
        if (!$booking_reference) {
            PrestaShopLogger::addLog('Référence de réservation manquante dans le webhook Stripe');
            return;
        }
        
        $reservation = $this->getReservationByReference($booking_reference);
        
        if (!$reservation) {
            PrestaShopLogger::addLog('Réservation introuvable: ' . $booking_reference);
            return;
        }
        
        // Mettre à jour le statut
        $reservation->payment_status = BookerAuthReserved::PAYMENT_COMPLETED;
        $reservation->status = BookerAuthReserved::STATUS_PAID;
        $reservation->update();
        
        // Créer la commande PrestaShop si pas encore fait
        if (!$reservation->id_order) {
            $reservation->createOrder();
        }
        
        PrestaShopLogger::addLog('Paiement confirmé pour réservation: ' . $booking_reference);
    }
    
    /**
     * Traiter l'échec du paiement
     */
    private function handlePaymentFailed($payment_intent)
    {
        $booking_reference = $payment_intent['metadata']['booking_reference'] ?? null;
        
        if (!$booking_reference) {
            return;
        }
        
        $reservation = $this->getReservationByReference($booking_reference);
        
        if ($reservation) {
            // Logger l'échec
            PrestaShopLogger::addLog(
                'Échec de paiement pour réservation: ' . $booking_reference . 
                ' - Raison: ' . ($payment_intent['last_payment_error']['message'] ?? 'Inconnue')
            );
            
            // Optionnel: notifier le client
            $this->sendPaymentFailedEmail($reservation);
        }
    }
    
    /**
     * Utilitaires
     */
    private function getStripeSecretKey()
    {
        $test_mode = Configuration::get('STRIPE_SANDBOX');
        return $test_mode ? 
            Configuration::get('STRIPE_TEST_PRIVATE_KEY') : 
            Configuration::get('STRIPE_LIVE_PRIVATE_KEY');
    }
    
    private function getStripeLocale()
    {
        $lang_iso = $this->context->language->iso_code;
        $supported_locales = ['fr', 'en', 'de', 'es', 'it'];
        
        return in_array($lang_iso, $supported_locales) ? $lang_iso : 'en';
    }
    
    private function formatReservationDescription(BookerAuthReserved $reservation)
    {
        return sprintf(
            'Réservation du %s de %02dh à %02dh - Ref: %s',
            date('d/m/Y', strtotime($reservation->date_reserved)),
            $reservation->hour_from,
            $reservation->hour_to,
            $reservation->booking_reference
        );
    }
    
    private function getBookerImages(Booker $booker)
    {
        // À implémenter selon vos besoins
        return [];
    }
    
    private function saveStripeSession(BookerAuthReserved $reservation, $session)
    {
        // Sauvegarder l'ID de session dans une table ou en configuration
        Db::getInstance()->insert('booking_stripe_sessions', [
            'id_reservation' => (int)$reservation->id,
            'session_id' => pSQL($session->id),
            'payment_intent_id' => pSQL($session->payment_intent),
            'date_add' => date('Y-m-d H:i:s'),
        ]);
    }
    
    private function getPaymentIntentId(BookerAuthReserved $reservation)
    {
        return Db::getInstance()->getValue('
            SELECT payment_intent_id 
            FROM `' . _DB_PREFIX_ . 'booking_stripe_sessions` 
            WHERE id_reservation = ' . (int)$reservation->id
        );
    }
    
    private function getReservationByReference($reference)
    {
        $id = Db::getInstance()->getValue('
            SELECT id_reserved 
            FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
            WHERE booking_reference = "' . pSQL($reference) . '"
        ');
        
        return $id ? new BookerAuthReserved($id) : null;
    }
    
    private function sendPaymentFailedEmail(BookerAuthReserved $reservation)
    {
        // Envoyer un email d'échec de paiement
        $templateVars = [
            'firstname' => $reservation->customer_firstname,
            'lastname' => $reservation->customer_lastname,
            'booking_reference' => $reservation->booking_reference,
            'payment_url' => $this->context->link->getModuleLink(
                'booking', 
                'payment', 
                ['booking_ref' => $reservation->booking_reference]
            ),
        ];
        
        Mail::Send(
            $this->context->language->id,
            'booking_payment_failed',
            'Échec du paiement - ' . $reservation->booking_reference,
            $templateVars,
            $reservation->customer_email,
            $reservation->customer_firstname . ' ' . $reservation->customer_lastname,
            Configuration::get('PS_SHOP_EMAIL'),
            Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            dirname(__FILE__) . '/../mails/'
        );
    }
}

/**
 * Contrôleur pour gérer les paiements Stripe
 */
class BookingPaymentModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        $action = Tools::getValue('action');
        $booking_ref = Tools::getValue('booking_ref');
        
        if (!$booking_ref) {
            Tools::redirect('index.php');
        }
        
        $reservation = $this->getReservationByReference($booking_ref);
        
        if (!$reservation) {
            $this->errors[] = 'Réservation introuvable';
            $this->setTemplate('errors/404.tpl');
            return;
        }
        
        switch ($action) {
            case 'process':
                $this->processPayment($reservation);
                break;
                
            case 'success':
                $this->displaySuccess($reservation);
                break;
                
            case 'cancel':
                $this->displayCancel($reservation);
                break;
                
            default:
                $this->displayPaymentForm($reservation);
        }
    }
    
    private function processPayment(BookerAuthReserved $reservation)
    {
        try {
            $stripe_payment = new StripeBookingPayment();
            $session = $stripe_payment->createPaymentSession($reservation);
            
            // Rediriger vers Stripe Checkout
            Tools::redirect($session->url);
            
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->displayPaymentForm($reservation);
        }
    }
    
    private function displaySuccess(BookerAuthReserved $reservation)
    {
        $this->context->smarty->assign([
            'reservation' => $reservation,
            'success' => true,
        ]);
        
        $this->setTemplate('module:booking/views/templates/front/payment_result.tpl');
    }
    
    private function displayCancel(BookerAuthReserved $reservation)
    {
        $this->context->smarty->assign([
            'reservation' => $reservation,
            'cancelled' => true,
        ]);
        
        $this->setTemplate('module:booking/views/templates/front/payment_result.tpl');
    }
    
    private function displayPaymentForm(BookerAuthReserved $reservation)
    {
        $booker = new Booker($reservation->id_booker);
        
        $this->context->smarty->assign([
            'reservation' => $reservation,
            'booker' => $booker,
            'payment_url' => $this->context->link->getModuleLink(
                'booking', 
                'payment', 
                ['action' => 'process', 'booking_ref' => $reservation->booking_reference]
            ),
        ]);
        
        $this->setTemplate('module:booking/views/templates/front/payment_form.tpl');
    }
    
    private function getReservationByReference($reference)
    {
        $id = Db::getInstance()->getValue('
            SELECT id_reserved 
            FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
            WHERE booking_reference = "' . pSQL($reference) . '"
        ');
        
        return $id ? new BookerAuthReserved($id) : null;
    }
}