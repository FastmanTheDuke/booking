<?php
class QuizzResult extends ObjectModel
{
    /*Variables, which will be available during the class initialization */
    public $id_result;
	public $section;
	public $max_points;    
    public $delimiter1;
    public $delimiter2;
	public $niveau;
	public $name_section;
    
    

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'quizz_results_names',
        'primary' => 'id_result',
        'multilang' => true,
		'multishop' => false,
		'multilang_shop' => false,
        'fields' => array(
			'id_result'  => 					['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],            
			'delimiter1' =>    				array('type' => self::TYPE_STRING),
			'delimiter2' =>					array('type' => self::TYPE_STRING),
			'content' =>           			array('type' => self::TYPE_HTML, 'lang' => TRUE, 'validate' => 'isString', 'size' => 3999999999999),
			'niveau' =>           			array('type' => self::TYPE_HTML, 'lang' => TRUE, 'validate' => 'isString', 'size' => 3999999999999),
			'name_section' =>           	array('type' => self::TYPE_HTML, 'lang' => TRUE, 'validate' => 'isString', 'size' => 3999999999999),
        ),
    );
	public function __construct($id=null,$id_lang=null)
    {
        parent::__construct($id,$id_lang);
        if ($id) 
        {
			if ($id_lang){
				$sql_select = "SELECT q.*,ql.* FROM " . _DB_PREFIX_ . $this->table." q
					INNER JOIN " . _DB_PREFIX_ . $this->table."_lang ql on ql.`".$this->identifier."`=q.`".$this->identifier."` AND ql.`id_lang` =" . $id_lang." 
					WHERE q.`".$this->identifier."` = '" . $id . "'
					ORDER BY q.`".$this->identifier."`,ql.id_lang ASC";				
				if ($row = Db::getInstance()->getRow( $sql_select ) ){
					$this->hydrate($row,$id_lang);
				} 
			}else{
				$sql_select = "SELECT * FROM " . _DB_PREFIX_ . $this->table . " WHERE `".$this->identifier."` = '" . $id . "'";
				if ($row = Db::getInstance()->getRow( $sql_select ) ){
					$this->hydrate($row);
				} 
			}		
                            
        }
    }
	public function getAllRows($id_lang=false)
    {
        
        if ($id_lang) 
        {           
			$sql_select = "SELECT q.*,ql.* FROM " . _DB_PREFIX_ . $this->table." q
			INNER JOIN " . _DB_PREFIX_ . $this->table."_lang ql on ql.".$this->identifier."=q.".$this->identifier." AND ql.`id_lang` =" . $id_lang." 
			ORDER BY q.".$this->identifier.",ql.id_lang ASC";
            if ($rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_select ) ){
				return $rows;
			}                 
        }else{
			$sql_select = "SELECT q.*,ql.* FROM " . _DB_PREFIX_ . $this->table." q
			INNER JOIN " . _DB_PREFIX_ . $this->table."_lang ql on ql.".$this->identifier."=q.".$this->identifier."
			ORDER BY q.".$this->identifier.",ql.id_lang ASC
			";
            if ($rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_select ) ){				
				return $rows;
			}                 
        }
    }
	public function save($nullValues = false, $autodate = true)
    {
		print_r($nullValues);
        return parent::save($nullValues, $autodate);
    }
	public function update($nullValues = false)
    {
        return parent::update($null_values);   
    }
	public function add($autodate = true,$nullValues = false)
    {
        return parent::add($autodate,$nullValues);   
    }
	
}