<?php
class QuizzQuestions extends ObjectModel
{
    /*Variables, which will be available during the class initialization */
    public $id_question;
    public $content;
    public $type;
    public $section;
    //public $name_section;
    public $max_points;
    public $value;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'quizz_questions',
        'primary' => 'id_question',
        'multilang' => true,
		'multishop' => false,
		'multilang_shop' => false,
        'fields' => array(
			'id_question'  => 		['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'/* , 'required' => true */],			
            'content' =>            array('type' => self::TYPE_HTML, 'lang' => TRUE, 'validate' => 'isString', 'size' => 3999999999999),
			'type' =>    			array('type' => self::TYPE_STRING),
			'section' =>           	array('type' => self::TYPE_INT),
			//'name_section' =>          	array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 256),
			'max_points' =>           array('type' => self::TYPE_INT),
            'value' =>             array('type' => self::TYPE_INT),
        ),
    );
	public function __construct($id_question=null,$id_lang=null)
    {
        parent::__construct($id_question,$id_lang);
        if ($id_question) 
        {
			if ($id_lang){
				$sql_select = "SELECT q.`".$this->identifier."`,q.type,q.section,q.max_points,q.value,ql.content,ql.id_lang FROM " . _DB_PREFIX_ . $this->table." q
					INNER JOIN " . _DB_PREFIX_ . $this->table."_lang ql on ql.`".$this->identifier."`=q.`".$this->identifier."` AND ql.`id_lang` =" . $id_lang." 
					WHERE q.`".$this->identifier."` = '" . $id_question . "'
					ORDER BY q.`".$this->identifier."`,ql.id_lang ASC";				
				if ($row = Db::getInstance()->getRow( $sql_select ) ){
					$this->hydrate($row,$id_lang);
				} 
			}else{
				$sql_select = "SELECT * FROM " . _DB_PREFIX_ . $this->table . " WHERE `".$this->identifier."` = '" . $id_question . "'";
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
			$sql_select = "SELECT q.id_question,q.type,q.section,q.max_points,q.value,ql.content,ql.id_lang FROM " . _DB_PREFIX_ . $this->table." q
			INNER JOIN " . _DB_PREFIX_ . $this->table."_lang ql on ql.id_question=q.id_question AND ql.`id_lang` =" . $id_lang." 
			ORDER BY q.id_question,ql.id_lang ASC";
            if ($rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_select ) ){
				return $rows;
			}                 
        }else{
			$sql_select = "SELECT q.id_question,q.type,q.section,q.max_points,q.value,ql.content,ql.id_lang FROM " . _DB_PREFIX_ . $this->table." q
			INNER JOIN " . _DB_PREFIX_ . $this->table."_lang ql on ql.id_question=q.id_question
			ORDER BY q.id_question,ql.id_lang ASC
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