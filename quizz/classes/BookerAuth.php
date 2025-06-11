<?php
class BookerAuth extends ObjectModel
{
    /*Variables, which will be available during the class initialization */
    public $id_booker;
    public $date_from;
    public $date_to;
    public $active;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'booker_auth',
        'primary' => 'id_auth',
        'fields' => array(
            // Lang fields
            'id_booker' =>          array('type' => self::TYPE_BOOL),
            'date_from' =>          array('type' => self::TYPE_DATE, 'required' => true),
            'date_to' =>          array('type' => self::TYPE_DATE, 'required' => true),            
            'active' =>             array('type' => self::TYPE_BOOL),
        ),
    );
}