<?php
class Booker extends ObjectModel
{
    /*Variables, which will be available during the class initialization */
    public $name;
    public $description;
    public $google_account;
    public $active;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'booker',
        'primary' => 'id_booker',
        'multilang' => true,
        'fields' => array(
            // Lang fields
            'name' =>           array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
            'description' =>    array('type' => self::TYPE_HTML, 'lang' => true, 'size' => 3999999999999),			
            'google_account' => array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 255),			
            'active' =>         array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' =>       array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' =>       array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
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
     * Obtenir les bookers actifs
     */
    public static function getActiveBookers($id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = Context::getContext()->language->id;
        }
        
        $sql = 'SELECT b.*, bl.description
                FROM `' . _DB_PREFIX_ . 'booker` b
                LEFT JOIN `' . _DB_PREFIX_ . 'booker_lang` bl ON (b.id_booker = bl.id_booker AND bl.id_lang = ' . (int)$id_lang . ')
                WHERE b.active = 1
                ORDER BY b.name ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Vérifier si le booker est disponible
     */
    public function isAvailable($date_from, $date_to = null)
    {
        if (!$date_to) {
            $date_to = $date_from;
        }
        
        $sql = 'SELECT COUNT(*) 
                FROM `' . _DB_PREFIX_ . 'booker_auth` 
                WHERE id_booker = ' . (int)$this->id . '
                AND active = 1
                AND date_from <= "' . pSQL($date_from) . '"
                AND date_to >= "' . pSQL($date_to) . '"';
        
        return (bool)Db::getInstance()->getValue($sql);
    }
}
