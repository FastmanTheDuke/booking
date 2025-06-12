<?php
/**
 * Contrôleur front pour gérer les paiements Stripe des réservations
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/../../classes/Booker.php');
require_once(dirname(__FILE__) . '/../../classes/BookerAuth.php');
require_once(dirname(__FILE__) . '/../../classes/BookerAuthReserved.php');
require_once(dirname(__FILE__) . '/../../classes/StripeBookingPayment.php');

class BookingPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function initContent()
    {
        parent::initContent();
        
        $action = Tools::getValue('action');
        $booking_ref = Tools::getValue('booking_ref');
        
        // Vérifier les paramètres obligatoires
        if (!$booking_ref) {
            $this->errors[] = $this->module->l('Référence de réservation manquante');
            Tools::redirect('index.php');
            return;
        }
        
        $reservation = $this->getReservationByReference($booking_ref);
        
        if (!$reservation || !Validate::isLoadedObject($reservation)) {
            $this->errors[] = $this->module->l('Réservation introuvable');
            $this->setTemplate('errors/404.tpl');
            return;
        }
        
        // Vérifier que la réservation est dans un état payable
        if (!$this->isPayable($reservation)) {
            $this->errors[] = $this->module->l('Cette réservation ne peut pas être payée');
            Tools::redirect('index.php');
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
                
            case 'webhook':
                $this->handleWebhook();
                break;
                
            default:
                $this->displayPaymentForm($reservation);
        }
    }
    
    /**
     * Afficher le formulaire de paiement
     */
    private function displayPaymentForm(BookerAuthReserved $reservation)
    {
        $booker = new Booker($reservation->id_booker);
        
        if (!Validate::isLoadedObject($booker)) {
            $this->errors[] = $this->module->l('Élément de réservation introuvable');
            Tools::redirect('index.php');
            return;
        }
        
        $this->context->smarty->assign([
            'reservation' => $reservation,
            'booker' => $booker,
            'payment_url' => $this->context->link->getModuleLink(
                'booking', 
                'payment', 
                ['action' => 'process', 'booking_ref' => $reservation->booking_reference]
            ),
            'cancel_url' => $this->context->link->getModuleLink(
                'booking', 
                'payment', 
                ['action' => 'cancel', 'booking_ref' => $reservation->booking_reference]
            ),
            'total_amount' => $reservation->total_price + $reservation->deposit_amount,
            'stripe_enabled' => Configuration::get('BOOKING_STRIPE_ENABLED'),
            'current_date' => date('Y-m-d H:i:s')
        ]);
        
        $this->setTemplate('module:booking/views/templates/front/payment_form.tpl');
    }
    
    /**
     * Traiter le paiement
     */
    private function processPayment(BookerAuthReserved $reservation)
    {
        try {
            // Vérifier que Stripe est activé
            if (!Configuration::get('BOOKING_STRIPE_ENABLED')) {
                throw new PrestaShopException($this->module->l('Paiements en ligne désactivés'));
            }
            
            $stripe_payment = new StripeBookingPayment();
            $session = $stripe_payment->createPaymentSession($reservation);
            
            // Mettre à jour le statut de la réservation
            $reservation->status = BookerAuthReserved::STATUS_ACCEPTED;
            $reservation->payment_status = BookerAuthReserved::PAYMENT_PENDING;
            $reservation->update();
            
            // Log de l'action
            PrestaShopLogger::addLog(
                'Redirection vers Stripe pour réservation: ' . $reservation->booking_reference,
                1,
                null,
                'BookingPayment'
            );
            
            // Rediriger vers Stripe Checkout
            Tools::redirect($session->url);
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Erreur paiement Stripe: ' . $e->getMessage(),
                3,
                null,
                'BookingPayment'
            );
            
            $this->errors[] = $this->module->l('Erreur lors de la création du paiement: ') . $e->getMessage();
            $this->displayPaymentForm($reservation);
        }
    }
    
    /**
     * Afficher la page de succès
     */
    private function displaySuccess(BookerAuthReserved $reservation)
    {
        // Vérifier que le paiement a bien été effectué
        $session_id = Tools::getValue('session_id');
        
        if ($session_id) {
            try {
                $stripe_payment = new StripeBookingPayment();
                $session_status = $stripe_payment->verifyPaymentSession($session_id);
                
                if ($session_status === 'complete') {
                    // Mettre à jour le statut de paiement
                    $reservation->status = BookerAuthReserved::STATUS_PAID;
                    $reservation->payment_status = BookerAuthReserved::PAYMENT_COMPLETED;
                    $reservation->update();
                    
                    // Créer la commande PrestaShop si nécessaire
                    $this->createPrestaShopOrder($reservation);
                    
                    // Envoyer l'email de confirmation
                    $stripe_payment->sendConfirmationEmail($reservation);
                }
                
            } catch (Exception $e) {
                PrestaShopLogger::addLog(
                    'Erreur vérification paiement: ' . $e->getMessage(),
                    3,
                    null,
                    'BookingPayment'
                );
            }
        }
        
        $booker = new Booker($reservation->id_booker);
        
        $this->context->smarty->assign([
            'reservation' => $reservation,
            'booker' => $booker,
            'success' => true,
            'payment_confirmed' => ($reservation->payment_status == BookerAuthReserved::PAYMENT_COMPLETED),
            'home_url' => $this->context->link->getPageLink('index'),
            'account_url' => $this->context->link->getPageLink('my-account')
        ]);
        
        $this->setTemplate('module:booking/views/templates/front/payment_result.tpl');
    }
    
    /**
     * Afficher la page d'annulation
     */
    private function displayCancel(BookerAuthReserved $reservation)
    {
        // Remettre le statut en attente
        if ($reservation->status == BookerAuthReserved::STATUS_ACCEPTED) {
            $reservation->status = BookerAuthReserved::STATUS_PENDING;
            $reservation->payment_status = BookerAuthReserved::PAYMENT_PENDING;
            $reservation->update();
        }
        
        $booker = new Booker($reservation->id_booker);
        
        $this->context->smarty->assign([
            'reservation' => $reservation,
            'booker' => $booker,
            'cancelled' => true,
            'retry_url' => $this->context->link->getModuleLink(
                'booking', 
                'payment', 
                ['booking_ref' => $reservation->booking_reference]
            ),
            'home_url' => $this->context->link->getPageLink('index')
        ]);
        
        $this->setTemplate('module:booking/views/templates/front/payment_result.tpl');
    }
    
    /**
     * Gérer les webhooks Stripe
     */
    private function handleWebhook()
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        try {
            $stripe_payment = new StripeBookingPayment();
            $event = $stripe_payment->verifyWebhook($payload, $sig_header);
            
            // Traiter l'événement
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handlePaymentSuccess($event->data->object);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;
                    
                default:
                    PrestaShopLogger::addLog(
                        'Webhook Stripe non géré: ' . $event->type,
                        1,
                        null,
                        'BookingPayment'
                    );
            }
            
            http_response_code(200);
            echo json_encode(['status' => 'success']);
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Erreur webhook Stripe: ' . $e->getMessage(),
                3,
                null,
                'BookingPayment'
            );
            
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        
        exit;
    }
    
    /**
     * Traiter le succès de paiement via webhook
     */
    private function handlePaymentSuccess($session)
    {
        $booking_ref = $session->metadata->booking_reference ?? null;
        
        if (!$booking_ref) {
            throw new Exception('Référence de réservation manquante dans le webhook');
        }
        
        $reservation = $this->getReservationByReference($booking_ref);
        
        if (!$reservation) {
            throw new Exception('Réservation introuvable: ' . $booking_ref);
        }
        
        // Mettre à jour le statut
        $reservation->status = BookerAuthReserved::STATUS_PAID;
        $reservation->payment_status = BookerAuthReserved::PAYMENT_COMPLETED;
        $reservation->update();
        
        // Créer la commande PrestaShop
        $this->createPrestaShopOrder($reservation);
        
        // Envoyer l'email de confirmation
        $stripe_payment = new StripeBookingPayment();
        $stripe_payment->sendConfirmationEmail($reservation);
        
        PrestaShopLogger::addLog(
            'Paiement confirmé pour réservation: ' . $booking_ref,
            1,
            null,
            'BookingPayment'
        );
    }
    
    /**
     * Traiter l'échec de paiement via webhook
     */
    private function handlePaymentFailed($payment_intent)
    {
        $booking_ref = $payment_intent->metadata->booking_reference ?? null;
        
        if (!$booking_ref) {
            return;
        }
        
        $reservation = $this->getReservationByReference($booking_ref);
        
        if ($reservation) {
            $reservation->status = BookerAuthReserved::STATUS_PENDING;
            $reservation->payment_status = BookerAuthReserved::PAYMENT_PENDING;
            $reservation->update();
            
            PrestaShopLogger::addLog(
                'Échec de paiement pour réservation: ' . $booking_ref,
                2,
                null,
                'BookingPayment'
            );
        }
    }
    
    /**
     * Créer une commande PrestaShop pour la réservation
     */
    private function createPrestaShopOrder(BookerAuthReserved $reservation)
    {
        if ($reservation->id_order) {
            return; // Commande déjà créée
        }
        
        try {
            // Récupérer ou créer le client
            $customer = $this->getOrCreateCustomer($reservation);
            
            // Créer le panier
            $cart = new Cart();
            $cart->id_customer = $customer->id;
            $cart->id_address_delivery = $customer->id_address;
            $cart->id_address_invoice = $customer->id_address;
            $cart->id_lang = $this->context->language->id;
            $cart->id_currency = $this->context->currency->id;
            $cart->id_carrier = 1; // Carrier par défaut
            $cart->recyclable = 0;
            $cart->gift = 0;
            $cart->add();
            
            // Créer un produit virtuel pour la réservation
            $product_id = $this->getOrCreateBookingProduct($reservation);
            
            if ($product_id) {
                $cart->updateQty(1, $product_id);
            }
            
            // Créer la commande
            $order = new Order();
            $order->id_customer = $customer->id;
            $order->id_cart = $cart->id;
            $order->current_state = Configuration::get('PS_OS_PAYMENT');
            $order->payment = 'Stripe (Réservation)';
            $order->total_paid = $reservation->total_price + $reservation->deposit_amount;
            $order->total_paid_tax_incl = $order->total_paid;
            $order->total_paid_tax_excl = $order->total_paid;
            $order->reference = Order::generateReference();
            
            if ($order->add()) {
                $reservation->id_order = $order->id;
                $reservation->update();
                
                PrestaShopLogger::addLog(
                    'Commande créée pour réservation: ' . $reservation->booking_reference . ' (Order: ' . $order->id . ')',
                    1,
                    null,
                    'BookingPayment'
                );
            }
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Erreur création commande: ' . $e->getMessage(),
                3,
                null,
                'BookingPayment'
            );
        }
    }
    
    /**
     * Récupérer ou créer un client
     */
    private function getOrCreateCustomer(BookerAuthReserved $reservation)
    {
        // Chercher un client existant
        $customer = Customer::getCustomersByEmail($reservation->customer_email);
        
        if ($customer && count($customer) > 0) {
            return new Customer($customer[0]['id_customer']);
        }
        
        // Créer un nouveau client
        $customer = new Customer();
        $customer->firstname = $reservation->customer_firstname;
        $customer->lastname = $reservation->customer_lastname;
        $customer->email = $reservation->customer_email;
        $customer->passwd = Tools::hash('booking_' . time());
        $customer->active = 1;
        $customer->add();
        
        // Créer une adresse par défaut
        $address = new Address();
        $address->id_customer = $customer->id;
        $address->firstname = $customer->firstname;
        $address->lastname = $customer->lastname;
        $address->address1 = 'Adresse de réservation';
        $address->city = 'Ville';
        $address->postcode = '00000';
        $address->id_country = Configuration::get('PS_COUNTRY_DEFAULT');
        $address->phone = $reservation->customer_phone;
        $address->alias = 'Réservation';
        $address->add();
        
        $customer->id_address = $address->id;
        $customer->update();
        
        return $customer;
    }
    
    /**
     * Créer ou récupérer un produit pour la réservation
     */
    private function getOrCreateBookingProduct(BookerAuthReserved $reservation)
    {
        // Logique pour créer un produit virtuel représentant la réservation
        // À implémenter selon les besoins
        return null;
    }
    
    /**
     * Récupérer une réservation par sa référence
     */
    private function getReservationByReference($reference)
    {
        $id = Db::getInstance()->getValue('
            SELECT id_reserved 
            FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
            WHERE booking_reference = "' . pSQL($reference) . '"
        ');
        
        return $id ? new BookerAuthReserved($id) : null;
    }
    
    /**
     * Vérifier si une réservation peut être payée
     */
    private function isPayable(BookerAuthReserved $reservation)
    {
        return in_array($reservation->status, [
            BookerAuthReserved::STATUS_PENDING,
            BookerAuthReserved::STATUS_ACCEPTED
        ]) && $reservation->payment_status != BookerAuthReserved::PAYMENT_COMPLETED;
    }
    
    /**
     * Définir les médias CSS/JS
     */
    public function setMedia()
    {
        parent::setMedia();
        
        $this->registerStylesheet(
            'booking-payment-style',
            'modules/' . $this->module->name . '/views/css/payment.css',
            [
                'media' => 'all',
                'priority' => 200,
            ]
        );
    }
}