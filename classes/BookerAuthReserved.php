<?php
/**
 * Classe BookerAuthReserved - Gestion complète des réservations
 * Version mise à jour avec toutes les méthodes nécessaires
 */

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
            'date_to' =>            array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'hour_from' =>          array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'hour_to' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'status' =>             array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
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
            'total_price' =>        array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'deposit_amount' =>     array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
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
            $this->active = 1;
            $this->status = self::STATUS_PENDING;
            $this->payment_status = self::PAYMENT_PENDING;
            
            // Générer une référence unique si pas fournie
            if (!$this->booking_reference) {
                $this->booking_reference = $this->generateBookingReference();
            }
        }
    }
    
    /**
     * Actions avant l'ajout
     */
    public function add($auto_date = true, $null_values = false)
    {
        // Générer la référence de réservation si pas fournie
        if (!$this->booking_reference) {
            $this->booking_reference = $this->generateBookingReference();
        }
        
        // Valider les créneaux horaires
        if (!$this->validateTimeSlot()) {
            return false;
        }
        
        // Vérifier la disponibilité
        if (!$this->checkAvailability()) {
            return false;
        }
        
        return parent::add($auto_date, $null_values);
    }
    
    /**
     * Actions avant la mise à jour
     */
    public function update($null_values = false)
    {
        // Valider les créneaux horaires
        if (!$this->validateTimeSlot()) {
            return false;
        }
        
        // Vérifier la disponibilité (en excluant cette réservation)
        if (!$this->checkAvailability($this->id)) {
            return false;
        }
        
        return parent::update($null_values);
    }
    
    /**
     * Générer une référence de réservation unique
     */
    private function generateBookingReference()
    {
        do {
            $reference = 'BK' . date('Y') . strtoupper(Tools::substr(md5(uniqid()), 0, 6));
        } while (self::bookingReferenceExists($reference));
        
        return $reference;
    }
    
    /**
     * Vérifier si une référence de réservation existe
     */
    public static function bookingReferenceExists($reference)
    {
        return (bool)Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
            WHERE booking_reference = "' . pSQL($reference) . '"
        ');
    }
    
    /**
     * Valider le créneau horaire
     */
    private function validateTimeSlot()
    {
        if ($this->hour_from >= $this->hour_to) {
            return false;
        }
        
        if ($this->hour_from < 0 || $this->hour_from > 23 || $this->hour_to < 1 || $this->hour_to > 24) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Vérifier la disponibilité du créneau
     */
    private function checkAvailability($exclude_id = null)
    {
        // Vérifier qu'il existe une autorisation pour ce booker à cette date
        $auth_count = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth`
            WHERE id_booker = ' . (int)$this->id_booker . '
            AND active = 1
            AND date_from <= "' . pSQL($this->date_reserved) . '"
            AND date_to >= "' . pSQL($this->date_reserved) . '"
        ');
        
        if (!$auth_count) {
            return false;
        }
        
        // Vérifier qu'il n'y a pas de conflit avec d'autres réservations
        $where = 'id_booker = ' . (int)$this->id_booker . '
                  AND date_reserved = "' . pSQL($this->date_reserved) . '"
                  AND active = 1
                  AND status IN (' . self::STATUS_ACCEPTED . ', ' . self::STATUS_PAID . ')
                  AND ((hour_from < ' . (int)$this->hour_to . ' AND hour_to > ' . (int)$this->hour_from . '))';
        
        if ($exclude_id) {
            $where .= ' AND id_reserved != ' . (int)$exclude_id;
        }
        
        $conflict_count = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
            WHERE ' . $where
        );
        
        return $conflict_count == 0;
    }
    
    /**
     * Obtenir les libellés des statuts
     */
    public static function getStatuses()
    {
        return array(
            self::STATUS_PENDING => 'En attente',
            self::STATUS_ACCEPTED => 'Acceptée',
            self::STATUS_PAID => 'Payée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_EXPIRED => 'Expirée'
        );
    }
    
    /**
     * Obtenir les libellés des statuts de paiement
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
    public function getStatusLabel()
    {
        $statuses = self::getStatuses();
        return isset($statuses[$this->status]) ? $statuses[$this->status] : 'Inconnu';
    }
    
    /**
     * Obtenir le libellé d'un statut de paiement
     */
    public function getPaymentStatusLabel()
    {
        $statuses = self::getPaymentStatuses();
        return isset($statuses[$this->payment_status]) ? $statuses[$this->payment_status] : 'Inconnu';
    }
    
    /**
     * Obtenir les réservations par booker
     */
    public static function getReservationsByBooker($id_booker, $date_from = null, $date_to = null, $status = null)
    {
        $where_conditions = array();
        $where_conditions[] = 'id_booker = ' . (int)$id_booker;
        $where_conditions[] = 'active = 1';
        
        if ($date_from) {
            $where_conditions[] = 'date_reserved >= "' . pSQL($date_from) . '"';
        }
        
        if ($date_to) {
            $where_conditions[] = 'date_reserved <= "' . pSQL($date_to) . '"';
        }
        
        if ($status !== null) {
            $where_conditions[] = 'status = ' . (int)$status;
        }
        
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
                WHERE ' . implode(' AND ', $where_conditions) . '
                ORDER BY date_reserved ASC, hour_from ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Obtenir les réservations par référence
     */
    public static function getByBookingReference($reference)
    {
        $id = Db::getInstance()->getValue('
            SELECT id_reserved 
            FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
            WHERE booking_reference = "' . pSQL($reference) . '"
        ');
        
        return $id ? new BookerAuthReserved($id) : false;
    }
    
    /**
     * Obtenir les réservations par client
     */
    public static function getReservationsByCustomer($email, $limit = 10)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
                WHERE customer_email = "' . pSQL($email) . '"
                AND active = 1
                ORDER BY date_add DESC
                LIMIT ' . (int)$limit;
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Obtenir les réservations à venir
     */
    public static function getUpcomingReservations($days = 7, $booker_id = null)
    {
        $where_conditions = array();
        $where_conditions[] = 'active = 1';
        $where_conditions[] = 'date_reserved >= CURDATE()';
        $where_conditions[] = 'date_reserved <= DATE_ADD(CURDATE(), INTERVAL ' . (int)$days . ' DAY)';
        $where_conditions[] = 'status IN (' . self::STATUS_ACCEPTED . ', ' . self::STATUS_PAID . ')';
        
        if ($booker_id) {
            $where_conditions[] = 'id_booker = ' . (int)$booker_id;
        }
        
        $sql = 'SELECT r.*, b.name as booker_name
                FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` r
                LEFT JOIN `' . _DB_PREFIX_ . 'booker` b ON (r.id_booker = b.id_booker)
                WHERE ' . implode(' AND ', $where_conditions) . '
                ORDER BY date_reserved ASC, hour_from ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Obtenir les réservations expirées
     */
    public static function getExpiredReservations()
    {
        $expiry_hours = Configuration::get('BOOKING_EXPIRY_HOURS', 24);
        
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
                WHERE active = 1
                AND status = ' . self::STATUS_PENDING . '
                AND date_add <= DATE_SUB(NOW(), INTERVAL ' . (int)$expiry_hours . ' HOUR)
                ORDER BY date_add ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Marquer les réservations expirées
     */
    public static function markExpiredReservations()
    {
        $expired_reservations = self::getExpiredReservations();
        $marked_count = 0;
        
        foreach ($expired_reservations as $reservation_data) {
            $reservation = new BookerAuthReserved($reservation_data['id_reserved']);
            $reservation->status = self::STATUS_EXPIRED;
            $reservation->cancellation_reason = 'Expirée automatiquement';
            
            if ($reservation->update()) {
                $marked_count++;
            }
        }
        
        return $marked_count;
    }
    
    /**
     * Calculer les statistiques de réservations
     */
    public static function getReservationStats($period = 'month')
    {
        $stats = array();
        
        // Période de calcul
        switch ($period) {
            case 'today':
                $date_condition = 'DATE(date_reserved) = CURDATE()';
                break;
            case 'week':
                $date_condition = 'WEEK(date_reserved) = WEEK(CURDATE()) AND YEAR(date_reserved) = YEAR(CURDATE())';
                break;
            case 'year':
                $date_condition = 'YEAR(date_reserved) = YEAR(CURDATE())';
                break;
            default: // month
                $date_condition = 'MONTH(date_reserved) = MONTH(CURDATE()) AND YEAR(date_reserved) = YEAR(CURDATE())';
        }
        
        // Compter par statut
        $statuses = self::getStatuses();
        foreach ($statuses as $status_id => $status_label) {
            $count = Db::getInstance()->getValue('
                SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
                WHERE active = 1 AND status = ' . (int)$status_id . '
                AND ' . $date_condition
            );
            $stats['status_' . $status_id] = (int)$count;
        }
        
        // Chiffre d'affaires
        $revenue = Db::getInstance()->getValue('
            SELECT SUM(total_price) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
            WHERE active = 1 AND status = ' . self::STATUS_PAID . '
            AND ' . $date_condition
        );
        $stats['revenue'] = $revenue ? (float)$revenue : 0;
        
        // Réservations par booker
        $booker_stats = Db::getInstance()->executeS('
            SELECT r.id_booker, b.name as booker_name, COUNT(*) as count
            FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` r
            LEFT JOIN `' . _DB_PREFIX_ . 'booker` b ON (r.id_booker = b.id_booker)
            WHERE r.active = 1 AND ' . $date_condition . '
            GROUP BY r.id_booker
            ORDER BY count DESC
        ');
        $stats['by_booker'] = $booker_stats;
        
        return $stats;
    }
    
    /**
     * Annuler une réservation
     */
    public function cancel($reason = '')
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancellation_reason = $reason;
        
        $result = $this->update();
        
        if ($result) {
            // Déclencher un hook pour permettre aux autres modules de réagir
            Hook::exec('actionBookingCancelled', array('reservation' => $this));
            
            // Envoyer un email de notification si configuré
            if (Configuration::get('BOOKING_EMAIL_NOTIFICATIONS')) {
                $this->sendCancellationEmail();
            }
        }
        
        return $result;
    }
    
    /**
     * Confirmer une réservation (passer en statut accepté)
     */
    public function confirm()
    {
        $this->status = self::STATUS_ACCEPTED;
        
        $result = $this->update();
        
        if ($result) {
            Hook::exec('actionBookingConfirmed', array('reservation' => $this));
            
            if (Configuration::get('BOOKING_EMAIL_NOTIFICATIONS')) {
                $this->sendConfirmationEmail();
            }
        }
        
        return $result;
    }
    
    /**
     * Marquer comme payée
     */
    public function markAsPaid($order_id = null)
    {
        $this->status = self::STATUS_PAID;
        $this->payment_status = self::PAYMENT_COMPLETED;
        
        if ($order_id) {
            $this->id_order = $order_id;
        }
        
        $result = $this->update();
        
        if ($result) {
            Hook::exec('actionBookingPaid', array('reservation' => $this));
        }
        
        return $result;
    }
    
    /**
     * Envoyer un email de confirmation
     */
    private function sendConfirmationEmail()
    {
        // Implémentation de l'envoi d'email
        // À développer selon les besoins spécifiques
    }
    
    /**
     * Envoyer un email d'annulation
     */
    private function sendCancellationEmail()
    {
        // Implémentation de l'envoi d'email
        // À développer selon les besoins spécifiques
    }
    
    /**
     * Vérifier si la réservation peut être modifiée
     */
    public function canBeModified()
    {
        // Ne peut pas être modifiée si payée ou annulée
        if (in_array($this->status, array(self::STATUS_PAID, self::STATUS_CANCELLED, self::STATUS_EXPIRED))) {
            return false;
        }
        
        // Ne peut pas être modifiée si la date est passée
        if ($this->date_reserved < date('Y-m-d')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Vérifier si la réservation peut être annulée
     */
    public function canBeCancelled()
    {
        // Ne peut pas être annulée si déjà annulée ou expirée
        if (in_array($this->status, array(self::STATUS_CANCELLED, self::STATUS_EXPIRED))) {
            return false;
        }
        
        // Politique d'annulation configurable
        $cancellation_hours = Configuration::get('BOOKING_CANCELLATION_HOURS', 24);
        $reservation_datetime = $this->date_reserved . ' ' . sprintf('%02d:00:00', $this->hour_from);
        
        if (strtotime($reservation_datetime) - time() < $cancellation_hours * 3600) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtenir la durée de la réservation en heures
     */
    public function getDuration()
    {
        return $this->hour_to - $this->hour_from;
    }
    
    /**
     * Obtenir le montant total à payer (prix + caution)
     */
    public function getTotalAmount()
    {
        return $this->total_price + $this->deposit_amount;
    }
}