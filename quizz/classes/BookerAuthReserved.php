<?php
class BookerAuthReserved extends ObjectModel
{
    /*Variables, which will be available during the class initialization */
    public $id_booker;   
    public $date_reserved;
    public $hour_from;
	public $hour_to;
    public $active;
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'booker_auth_reserved',
        'primary' => 'id_reserved',
        'fields' => array(
            // Lang fields
            'id_booker' =>          array('type' => self::TYPE_INT),
            'date_reserved' =>          array('type' => self::TYPE_DATE, 'required' => true),
            'hour_from' =>          array('type' => self::TYPE_INT, 'required' => true),            
            'hour_to' =>          array('type' => self::TYPE_INT, 'required' => true),            
            'active' =>             array('type' => self::TYPE_BOOL),
        ),
    );
}