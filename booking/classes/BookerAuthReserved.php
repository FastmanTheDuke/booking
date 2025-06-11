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
    
    // Constantes pour les statuts
    const STATUS_PENDING = 0;        // Demande de réservation
    const STATUS_ACCEPTED = 1;       // Réservation acceptée
    const STATUS_PAID = 2;           // Paiement validé
    const STATUS_CANCELLED = 3;      // Annulée
    const STATUS_EXPIRED = 4;        // Expirée
    
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
     * Obtenir le libellé d'un statut
     */
    public static function getStatusLabel($status)
    {
        $statuses = self::getStatuses();
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
    public function changeStatus($new_status)
    {
        $this->status = (int)$new_status;
        return $this->update();
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
}
