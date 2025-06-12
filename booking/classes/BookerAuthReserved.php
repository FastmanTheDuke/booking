<?php
class BookerAuthReserved extends ObjectModel
{
    /*Variables, which will be available during the class initialization */
    public $id_booker;   
    public $date_reserved;
    public $date_to;
    public $hour_from;
    public $hour_to;
    public $status;
    public $active;
    public $date_add;
    public $date_upd;
    
    // Nouvelles propriétés pour le système complet
    public $booking_reference;
    public $customer_firstname;
    public $customer_lastname;
    public $customer_email;
    public $customer_phone;
    public $customer_message;
    public $total_price;
    public $deposit_amount;
    public $id_order;
    public $payment_status;
    public $cancellation_reason;
    
    // Constantes pour les statuts
    const STATUS_PENDING = 0;        // Demande de réservation
    const STATUS_ACCEPTED = 1;       // Réservation acceptée
    const STATUS_PAID = 2;           // Paiement validé
    const STATUS_CANCELLED = 3;      // Annulée
    const STATUS_EXPIRED = 4;        // Expirée
    
    // Constantes pour le statut de paiement
    const PAYMENT_PENDING = 0;       // En attente
    const PAYMENT_PARTIAL = 1;       // Partiel (acompte)
    const PAYMENT_COMPLETED = 2;     // Complet
    const PAYMENT_REFUNDED = 3;      // Remboursé
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'booker_auth_reserved',
        'primary' => 'id_reserved',
        'fields' => array(
            'id_booker' =>          array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'date_reserved' =>      array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true),
            'date_to' =>            array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => false),
            'hour_from' =>          array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),            
            'hour_to' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'status' =>             array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'active' =>             array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            
            // Nouvelles propriétés
            'booking_reference' =>  array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 20),
            'customer_firstname' => array('type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 100),
            'customer_lastname' =>  array('type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 100),
            'customer_email' =>     array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 150),
            'customer_phone' =>     array('type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 20),
            'customer_message' =>   array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'total_price' =>        array('type' => self::TYPE_PRICE, 'validate' => 'isPrice'),
            'deposit_amount' =>     array('type' => self::TYPE_PRICE, 'validate' => 'isPrice'),
            'id_order' =>           array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'payment_status' =>     array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'cancellation_reason' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
        ),
    );
    
    /**
     * Constructeur
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        
        // Valeurs par défaut
        if (!$this->id) {
            $this->status = self::STATUS_PENDING;
            $this->payment_status = self::PAYMENT_PENDING;
            $this->active = 1;
        }
    }
    
    /**
     * Obtenir tous les statuts possibles
     */
    public static function getStatuses()
    {
        return array(
            self::STATUS_PENDING => 'Demande de réservation',
            self::STATUS_ACCEPTED => 'Réservation acceptée',
            self::STATUS_PAID => 'Paiement validé',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_EXPIRED => 'Expirée'
        );
    }
    
    /**
     * Obtenir les statuts de paiement
     */
    public static function getPaymentStatuses()
    {
        return array(
            self::PAYMENT_PENDING => 'En attente',
            self::PAYMENT_PARTIAL => 'Partiel',
            self::PAYMENT_COMPLETED => 'Complet',
            self::PAYMENT_REFUNDED => 'Remboursé'
        );
    }
    
    /**
     * Obtenir le libellé d'un statut
     */
    public static function getStatusLabel($status)
    {
        $statuses = self::getStatuses();
        return isset($statuses[$status]) ? $statuses[$status] : 'Inconnu';
    }
    
    /**
     * Obtenir le libellé d'un statut de paiement
     */
    public static function getPaymentStatusLabel($status)
    {
        $statuses = self::getPaymentStatuses();
        return isset($statuses[$status]) ? $statuses[$status] : 'Inconnu';
    }
    
    /**
     * Vérifier si une réservation est en conflit avec une autre
     */
    public function hasConflict()
    {
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
                WHERE `id_booker` = ' . (int)$this->id_booker . '
                AND `id_reserved` != ' . (int)$this->id . '
                AND `status` IN (' . self::STATUS_ACCEPTED . ', ' . self::STATUS_PAID . ')
                AND `active` = 1
                AND (
                    (
                        `date_reserved` = "' . pSQL($this->date_reserved) . '"
                        AND `hour_from` < ' . (int)$this->hour_to . '
                        AND `hour_to` > ' . (int)$this->hour_from . '
                    )';
        
        // Si date_to est définie, vérifier sur toute la période
        if ($this->date_to && $this->date_to != '0000-00-00') {
            $sql .= ' OR (
                        `date_reserved` >= "' . pSQL($this->date_reserved) . '"
                        AND `date_reserved` <= "' . pSQL($this->date_to) . '"
                        AND `hour_from` < ' . (int)$this->hour_to . '
                        AND `hour_to` > ' . (int)$this->hour_from . '
                    )';
        }
        
        $sql .= ')';
        
        return (bool)Db::getInstance()->getValue($sql);
    }
    
    /**
     * Valider la réservation avant sauvegarde
     */
    public function validateFields($die = true, $error_return = false)
    {
        $parent_validation = parent::validateFields($die, $error_return);
        
        if (!$parent_validation) {
            return false;
        }
        
        // Vérifier que hour_from < hour_to
        if ($this->hour_from >= $this->hour_to) {
            if ($die) {
                die('L\'heure de début doit être inférieure à l\'heure de fin');
            }
            return false;
        }
        
        // Vérifier que date_reserved <= date_to si date_to est définie
        if ($this->date_to && $this->date_to != '0000-00-00' && $this->date_reserved > $this->date_to) {
            if ($die) {
                die('La date de début doit être inférieure ou égale à la date de fin');
            }
            return false;
        }
        
        // Vérifier les conflits de réservation pour les statuts acceptés/payés
        if (in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_PAID]) && $this->hasConflict()) {
            if ($die) {
                die('Cette réservation entre en conflit avec une réservation existante');
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Changer le statut de la réservation
     */
    public function changeStatus($new_status, $reason = null)
    {
        $old_status = $this->status;
        $this->status = (int)$new_status;
        
        if ($reason && $new_status == self::STATUS_CANCELLED) {
            $this->cancellation_reason = pSQL($reason);
        }
        
        if ($this->update()) {
            // Envoyer l'email selon le nouveau statut
            $this->sendStatusChangeEmail($old_status, $new_status);
            
            // Gestion automatique des commandes PrestaShop
            if ($new_status == self::STATUS_ACCEPTED) {
                $this->createOrder();
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Créer une commande PrestaShop pour la réservation
     */
    public function createOrder()
    {
        if ($this->id_order) {
            return $this->id_order; // Commande déjà créée
        }
        
        try {
            $context = Context::getContext();
            
            // Créer un client temporaire si nécessaire
            $customer = $this->getOrCreateCustomer();
            
            // Créer le panier
            $cart = new Cart();
            $cart->id_customer = $customer->id;
            $cart->id_address_delivery = $customer->id_address;
            $cart->id_address_invoice = $customer->id_address;
            $cart->id_lang = $context->language->id;
            $cart->id_currency = $context->currency->id;
            $cart->id_carrier = 1; // Carrier par défaut
            $cart->save();
            
            // Créer un produit virtuel pour la réservation
            $product = $this->createVirtualProduct();
            
            // Ajouter le produit au panier
            $cart->updateQty(1, $product->id);
            
            // Valider la commande
            $payment_module = new PaymentModule();
            $payment_module->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_PREPARATION'), // Statut "En préparation"
                $this->total_price,
                'Réservation - ' . $this->booking_reference,
                null,
                array(),
                (int)$context->currency->id,
                false,
                $customer->secure_key
            );
            
            $this->id_order = $payment_module->currentOrder;
            $this->update();
            
            return $this->id_order;
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Erreur création commande réservation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir ou créer un client pour la réservation
     */
    private function getOrCreateCustomer()
    {
        // Chercher si le client existe déjà
        $id_customer = Customer::customerExists($this->customer_email, true);
        
        if ($id_customer) {
            return new Customer($id_customer);
        }
        
        // Créer un nouveau client
        $customer = new Customer();
        $customer->firstname = $this->customer_firstname;
        $customer->lastname = $this->customer_lastname;
        $customer->email = $this->customer_email;
        $customer->passwd = Tools::encrypt(Tools::passwdGen());
        $customer->active = 1;
        $customer->add();
        
        // Créer une adresse
        $address = new Address();
        $address->id_customer = $customer->id;
        $address->alias = 'Réservation';
        $address->firstname = $this->customer_firstname;
        $address->lastname = $this->customer_lastname;
        $address->address1 = 'Adresse de réservation';
        $address->city = 'Ville';
        $address->postcode = '00000';
        $address->id_country = Country::getByIso('FR');
        $address->phone = $this->customer_phone ?: '';
        $address->add();
        
        $customer->id_address = $address->id;
        $customer->update();
        
        return $customer;
    }
    
    /**
     * Créer un produit virtuel pour la réservation
     */
    private function createVirtualProduct()
    {
        $booker = new Booker($this->id_booker);
        
        $product = new Product();
        $product->name = array(
            Configuration::get('PS_LANG_DEFAULT') => 'Réservation - ' . $booker->name . ' - ' . $this->booking_reference
        );
        $product->description = array(
            Configuration::get('PS_LANG_DEFAULT') => 'Réservation du ' . date('d/m/Y', strtotime($this->date_reserved)) . 
                ' de ' . $this->hour_from . 'h à ' . $this->hour_to . 'h'
        );
        $product->price = $this->total_price;
        $product->active = 0; // Produit non visible publiquement
        $product->is_virtual = 1;
        $product->reference = 'BOOKING-' . $this->booking_reference;
        $product->add();
        
        return $product;
    }
    
    /**
     * Envoyer l'email de changement de statut
     */
    private function sendStatusChangeEmail($old_status, $new_status)
    {
        $templates = array(
            self::STATUS_ACCEPTED => 'booking_confirmed',
            self::STATUS_PAID => 'booking_payment_confirmed',
            self::STATUS_CANCELLED => 'booking_cancelled',
            self::STATUS_EXPIRED => 'booking_expired'
        );
        
        if (!isset($templates[$new_status])) {
            return;
        }
        
        $booker = new Booker($this->id_booker, Context::getContext()->language->id);
        
        $templateVars = array(
            'firstname' => $this->customer_firstname,
            'lastname' => $this->customer_lastname,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_message' => $this->customer_message,
            'booking_reference' => $this->booking_reference,
            'booker_name' => $booker->name,
            'booking_dates' => date('d/m/Y', strtotime($this->date_reserved)),
            'time_slots' => $this->hour_from . 'h - ' . $this->hour_to . 'h',
            'total_price' => number_format($this->total_price, 2),
            'cancellation_reason' => $this->cancellation_reason,
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'shop_email' => Configuration::get('PS_SHOP_EMAIL'),
            'shop_phone' => Configuration::get('PS_SHOP_PHONE'),
            'shop_address' => Configuration::get('PS_SHOP_ADDR1') . ' ' . Configuration::get('PS_SHOP_CITY'),
            'shop_url' => Context::getContext()->shop->getBaseURL(true),
            'requires_payment' => ($new_status == self::STATUS_ACCEPTED && Configuration::get('BOOKING_PAYMENT_ENABLED')),
            'payment_url' => $this->getPaymentUrl(),
            'deposit_amount' => number_format($this->deposit_amount, 2),
            'emergency_phone' => Configuration::get('BOOKING_EMERGENCY_PHONE')
        );
        
        $subject = $this->getEmailSubject($new_status);
        
        Mail::Send(
            Context::getContext()->language->id,
            $templates[$new_status],
            $subject,
            $templateVars,
            $this->customer_email,
            $this->customer_firstname . ' ' . $this->customer_lastname,
            Configuration::get('PS_SHOP_EMAIL'),
            Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            dirname(__FILE__) . '/../mails/'
        );
    }
    
    /**
     * Obtenir l'URL de paiement
     */
    private function getPaymentUrl()
    {
        if (!$this->id_order) {
            return null;
        }
        
        $context = Context::getContext();
        return $context->link->getPageLink('order-confirmation', true, null, array(
            'id_cart' => $this->id_order,
            'id_module' => 1, // À adapter selon le module de paiement
            'key' => 'booking_' . $this->booking_reference
        ));
    }
    
    /**
     * Obtenir le sujet de l'email selon le statut
     */
    private function getEmailSubject($status)
    {
        $subjects = array(
            self::STATUS_ACCEPTED => 'Réservation confirmée - ' . $this->booking_reference,
            self::STATUS_PAID => 'Paiement confirmé - ' . $this->booking_reference,
            self::STATUS_CANCELLED => 'Réservation annulée - ' . $this->booking_reference,
            self::STATUS_EXPIRED => 'Réservation expirée - ' . $this->booking_reference
        );
        
        return isset($subjects[$status]) ? $subjects[$status] : 'Mise à jour réservation - ' . $this->booking_reference;
    }
    
    /**
     * Obtenir les réservations d'un booker pour une période donnée
     */
    public static function getReservationsByBooker($id_booker, $date_from = null, $date_to = null, $status = null)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
                WHERE `id_booker` = ' . (int)$id_booker . '
                AND `active` = 1';
        
        if ($date_from) {
            $sql .= ' AND `date_reserved` >= "' . pSQL($date_from) . '"';
        }
        
        if ($date_to) {
            $sql .= ' AND `date_reserved` <= "' . pSQL($date_to) . '"';
        }
        
        if ($status !== null) {
            $sql .= ' AND `status` = ' . (int)$status;
        }
        
        $sql .= ' ORDER BY `date_reserved` ASC, `hour_from` ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Obtenir les créneaux disponibles pour un booker
     */
    public static function getAvailableSlots($id_booker, $date, $hour_from = 0, $hour_to = 23)
    {
        $reserved_slots = Db::getInstance()->executeS('
            SELECT `hour_from`, `hour_to` 
            FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
            WHERE `id_booker` = ' . (int)$id_booker . '
            AND `date_reserved` = "' . pSQL($date) . '"
            AND `status` IN (' . self::STATUS_ACCEPTED . ', ' . self::STATUS_PAID . ')
            AND `active` = 1
            ORDER BY `hour_from` ASC
        ');
        
        $available_slots = array();
        $current_hour = $hour_from;
        
        foreach ($reserved_slots as $slot) {
            if ($current_hour < $slot['hour_from']) {
                $available_slots[] = array(
                    'hour_from' => $current_hour,
                    'hour_to' => $slot['hour_from']
                );
            }
            $current_hour = max($current_hour, $slot['hour_to']);
        }
        
        if ($current_hour < $hour_to) {
            $available_slots[] = array(
                'hour_from' => $current_hour,
                'hour_to' => $hour_to
            );
        }
        
        return $available_slots;
    }
    
    /**
     * Annuler automatiquement les réservations expirées
     */
    public static function cancelExpiredReservations($expiry_hours = 24)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'booker_auth_reserved` 
                SET `status` = ' . self::STATUS_EXPIRED . '
                WHERE `status` = ' . self::STATUS_PENDING . '
                AND `date_add` < DATE_SUB(NOW(), INTERVAL ' . (int)$expiry_hours . ' HOUR)';
        
        return Db::getInstance()->execute($sql);
    }
    
    /**
     * Obtenir le chiffre d'affaires par période
     */
    public static function getRevenueByPeriod($date_from, $date_to, $id_booker = null)
    {
        $sql = 'SELECT SUM(total_price) as revenue, COUNT(*) as reservations
                FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
                WHERE `status` IN (' . self::STATUS_PAID . ')
                AND `active` = 1
                AND `date_reserved` >= "' . pSQL($date_from) . '"
                AND `date_reserved` <= "' . pSQL($date_to) . '"';
        
        if ($id_booker) {
            $sql .= ' AND `id_booker` = ' . (int)$id_booker;
        }
        
        return Db::getInstance()->getRow($sql);
    }
}