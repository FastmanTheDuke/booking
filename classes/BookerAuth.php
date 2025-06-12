<?php
class BookerAuth extends ObjectModel
{
    /*Variables, which will be available during the class initialization */
    public $id_booker;
    public $date_from;
    public $date_to;
    public $active;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'booker_auth',
        'primary' => 'id_auth',
        'fields' => array(
            'id_booker' =>  array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'date_from' =>  array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true),
            'date_to' =>    array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true),            
            'active' =>     array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' =>   array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' =>   array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
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
        }
    }
    
    /**
     * Obtenir les disponibilités d'un booker
     */
    public static function getBookerAvailabilities($id_booker, $date_from = null, $date_to = null)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'booker_auth` 
                WHERE id_booker = ' . (int)$id_booker . '
                AND active = 1';
        
        if ($date_from) {
            $sql .= ' AND date_to >= "' . pSQL($date_from) . '"';
        }
        
        if ($date_to) {
            $sql .= ' AND date_from <= "' . pSQL($date_to) . '"';
        }
        
        $sql .= ' ORDER BY date_from ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Vérifier si un créneau est disponible
     */
    public function isSlotAvailable($date, $hour_from, $hour_to)
    {
        // Vérifier d'abord si c'est dans la période d'autorisation
        if ($date < $this->date_from || $date > $this->date_to) {
            return false;
        }
        
        // Vérifier s'il n'y a pas de réservations conflictuelles
        $sql = 'SELECT COUNT(*) 
                FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
                WHERE id_booker = ' . (int)$this->id_booker . '
                AND date_reserved = "' . pSQL($date) . '"
                AND status IN (' . BookerAuthReserved::STATUS_ACCEPTED . ', ' . BookerAuthReserved::STATUS_PAID . ')
                AND active = 1
                AND hour_from < ' . (int)$hour_to . '
                AND hour_to > ' . (int)$hour_from;
        
        return !(bool)Db::getInstance()->getValue($sql);
    }
}
