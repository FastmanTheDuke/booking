<?php
class Booker extends ObjectModel
{
    /*Variables, which will be available during the class initialization */
    public $name;
    public $description;
    public $google_account;
    public $active;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'booker',
        'primary' => 'id_booker',
        'multilang' => TRUE,
        'fields' => array(
            // Lang fields
            'name' =>          array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
            'description' =>            array('type' => self::TYPE_HTML, 'lang' => true, 'size' => 3999999999999),			
            'google_account' =>            array('type' => self::TYPE_STRING, 'size' => 3999999999999),			
            'active' =>             array('type' => self::TYPE_BOOL),
        ),
    );
}