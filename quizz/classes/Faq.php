<?php
class Faq extends ObjectModel
{
    /*Variables, which will be available during the class initialization */
    public $content;
    public $title;
    public $position;
    public $manual_position;
    public $active;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'belvg_faq',
        'primary' => 'id_belvg_faq',
        'multilang' => TRUE,
        'fields' => array(            
            // Lang fields
            'title' =>          array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
            'content' =>            array('type' => self::TYPE_HTML, 'lang' => TRUE, 'validate' => 'isString', 'size' => 3999999999999),
			'position' =>           array('type' => self::TYPE_INT),
			'manual_position' =>    array('type' => self::TYPE_STRING),
            'active' =>             array('type' => self::TYPE_BOOL),
        ),
    );
}