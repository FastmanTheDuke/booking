<?php
require_once (dirname(__FILE__). '/../../classes/QuizzQuestions.php');
require_once (dirname(__FILE__). '/../../classes/QuizzChoices.php');
require_once (dirname(__FILE__). '/../../classes/QuizzNicotine.php');
require_once (dirname(__FILE__). '/../../classes/QuizzResult.php');
/*
Appeler une classe externe dans un module
use Product;
*/
class AdminQuizzController extends ModuleAdminControllerCore
{
    public $controller_type='admin';   
	static $base = _DB_NAME_;
	static $tables = "quizz";
	
    
    public function __construct()
    {
        $this->display = 'options';
        $this->displayName = 'Quizz fields';
        $this->bootstrap = true;
        parent::__construct();
    }
    
    public function renderOptions()
    {
		/* $option = '';
		$options_files = [];
		$helper = new HelperForm(); */
		/*
		//Utilisé pour traitement en ajax (ex : import csv dynamique)
        $this->addJS(_MODULE_DIR_.$this->module->name.'/views/js/admin-importsupplier.js');       
        $this->context->smarty->assign('import_controller_link', $this->context->link->getAdminLink('AdminQuizz'));
		$option .= $this->context->smarty->fetch($this->getTemplatePath().'supplier-ipmport-headear.tpl'); 
		$fields_value = array(           
            'action' => 'processImport',
            'ajax' => '1'
        );		
        $options_files[] = array('name' => 'action', 'type' => 'hidden');
        $options_files[] = array('name' => 'ajax', 'type' => 'hidden');
		*/
		// Languages
		/* $languages = Language::getLanguages(true);
		
		$questions = new QuizzQuestions();
		$fields = $questions->getAllRows(1);		
		if($fields){
			foreach($fields as $field){				
				$options_files[] = array(
					//'type' => 'textarea',
					'type' => 'text',
					'label' => $this->l("Question ". $field["id_question"]),
					'name' => "id_question-".(int)($field["id_question"]),
					'cols' => 60,
					'required' => false,
					'lang' => true,
					'rows' => 10,
					'class' => 'rte',
					'autoload_rte' => true,
				);
			}
		}
		$fields = $questions->getAllRows();
		if($fields){
			foreach($fields as $field){						
				$fields_value["id_question-".(int)($field["id_question"])][(int)$field["id_lang"]]=$field["content"];
			}
		}	
		// Default language
		$languages = Language::getLanguages(false);
		foreach ($languages as $k => $language){
			$languages[$k]['is_default'] = (int)($language['id_lang'] == Configuration::get('PS_LANG_DEFAULT'));
		} */
        
		$form_actions = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Quizz'),
                    'icon' => 'icon-plus-sign-alt'
                ),
                'input' => $options_files,                
                'submit' => array(
                    'title' => $this->l('Mettre à jour'),
                    'name' => 'submit'.$this->module->name,
                ),
				/* 'buttons' => array(
					'save-and-stay' => array(
						'title' => $this->l('Save and Stay'),
						'name' => 'submitAdd'.$this->table.'AndStay',
						'type' => 'submit',
						'class' => 'btn btn-default pull-right',
						'icon' => 'process-icon-save',
                      ),
                ), */
            ),
        );
        //print_r($form_actions);
        
        
        /* if (Tools::getValue('csv_file') && is_file(Tools::getValue('csv_file')))
            $fields_value['csv_file'] = Tools::getValue('csv_file');
        if ((int)Tools::getValue('id_supplier'))
            $fields_value['id_supplier'] = Tools::getValue('id_supplier'); */

        
       
       /*  $helper->show_toolbar = true;
		
		
		
        $helper->languages = $languages;
		$helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
		$helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $this->fields_form = array();
        
        //$helper->submit_action = 'submit'.$this->module->name;
        $helper->currentIndex = 0;
        $helper->token =  $this->token;
        
        $helper->tpl_vars = array(
            'fields_value' => $fields_value,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ); */
        
       // $option .= $helper->generateForm(array($form_actions));
		
       
        $output="";
        $type="question";			
		$this->context->smarty->assign($type, $this->renderFormTab("QuizzQuestions","$type"));
		$type="choice";				
		$this->context->smarty->assign($type, $this->renderFormTab("QuizzChoices","$type"));
		$type="nicotine";				
		$this->context->smarty->assign($type, $this->renderFormTab("QuizzNicotine","$type"));
		$type="result";				
		$this->context->smarty->assign($type, $this->renderFormTab("QuizzResult","$type"));
		$output .= $this->context->smarty->fetch(__DIR__.'/../../views/templates/admin/quizz_edit.tpl');
		
        return $output;
        //return $option;
    }
	
	public function renderFormTab($class="QuizzQuestions",$type=""){
		$option = '';
		$fields_value = [        
            'class' => $class,
            'type' => $type
        ];		
        $options_files[] = ['name' => 'class', 'type' => 'hidden'];
        $options_files[] = ['name' => 'type', 'type' => 'hidden'];
		$helper = new HelperForm();
		
		// Languages
		$languages = Language::getLanguages(true);
		
		$questions = new $class();
		$fields = $questions->getAllRows(1);		
		if($fields){
			$counter=0;
			foreach($fields as $field){
				if($field["content"]!="numeric" && $field["content"]!="decimal"){
					$counter++;
					
					if($type=="nicotine"){
						$options_files[] = array(
							'type' => 'text',
							'label' => $this->l("$class levels". $counter),
							'name' => "level-".(int)($field["id_$type"]),
							'cols' => 60,
							'required' => false,
							'lang' => true,
							'rows' => 10,
							'class' => 'rte',
							'autoload_rte' => false,
						);
						$options_files[] = array(
							'type' => 'textarea',
							'label' => $this->l("$class ". $counter),
							'name' => "id_$type-".(int)($field["id_$type"]),
							'cols' => 60,
							'required' => false,
							'lang' => true,
							'rows' => 10,
							'class' => 'rte',
							'autoload_rte' => true,
						);
					}elseif($type=="result"){
						if($field["niveau"]!="rand"){
							$options_files[] = array(
								'type' => 'text',
								'label' => $this->l("$class Niveau". $counter),
								'name' => "niveau-".(int)($field["id_$type"]),
								'cols' => 60,
								'required' => false,
								'lang' => true,
								'rows' => 10,
								'class' => 'rte',
								'autoload_rte' => false,
							);
						}
						$options_files[] = array(
							'type' => 'text',
							'label' => $this->l("$class Dépendance". $counter),
							'name' => "name_section-".(int)($field["id_$type"]),
							'cols' => 60,
							'required' => false,
							'lang' => true,
							'rows' => 10,
							'class' => 'rte',
							'autoload_rte' => false,
						);
						$options_files[] = array(
							'type' => 'textarea',
							'label' => $this->l("$class ". $counter),
							'name' => "id_$type-".(int)($field["id_$type"]),
							'cols' => 60,
							'required' => false,
							'lang' => true,
							'rows' => 10,
							'class' => 'rte',
							'autoload_rte' => true,
						);
					}else{
						$options_files[] = array(
							//'type' => 'textarea',
							'type' => 'text',
							'label' => $this->l("$class ". $counter),
							'name' => "id_$type-".(int)($field["id_$type"]),
							'cols' => 60,
							'required' => false,
							'lang' => true,
							'rows' => 10,
							'class' => 'rte',
							'autoload_rte' => true,
						);
					}
					
				}
				
			}
		}
		//print_r($fields);
		$fields = $questions->getAllRows();		
		if($fields){
			foreach($fields as $field){				
				$fields_value["id_$type-".(int)($field["id_$type"])][(int)$field["id_lang"]]=$field["content"];				
				if(isset($field["level"])){$fields_value["level-".(int)($field["id_$type"])][(int)$field["id_lang"]]=$field["level"];}
				if(isset($field["name_section"])){$fields_value["name_section-".(int)($field["id_$type"])][(int)$field["id_lang"]]=$field["name_section"];}
				if(isset($field["niveau"])){$fields_value["niveau-".(int)($field["id_$type"])][(int)$field["id_lang"]]=$field["niveau"];}
				
			}
		}	
		// Default language
		$languages = Language::getLanguages(false);
		foreach ($languages as $k => $language){
			$languages[$k]['is_default'] = (int)($language['id_lang'] == Configuration::get('PS_LANG_DEFAULT'));
		}
        
		$form_actions = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l("$class"),
                    'icon' => 'icon-plus-sign-alt'
                ),
                'input' => $options_files,                
                'submit' => array(
                    'title' => $this->l('Mettre à jour'),
                    'name' => 'submit'.$this->module->name.$type,
                ),				
            ),
        );
		$helper->submit_action = 'submit' . $this->name.$type;
        $helper->show_toolbar = true;	
        $helper->languages = $languages;
		$helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
		$helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $this->fields_form = array();        
        $helper->currentIndex = 0;
        $helper->token =  $this->token;        
        $helper->tpl_vars = array(
            'fields_value' => $fields_value,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
		/* $helper->toolbar_btn = array(
			'save' =>
			array(
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&save'.$this->module->name.$type.
				'&token='.Tools::getAdminTokenLite('AdminModules'),
			)
		); */
		//$helper->currentIndex = $this->context->link->getAdminLink('AdminDealerlocator', false);
		return $helper->generateForm(array($form_actions));
	}
	public function postProcess()
	{
		$confirmations= [];
		$errors= [];
		
		if (Tools::isSubmit('submitquizzquestion')) {
			$type=Tools::getValue('type');
			$class=Tools::getValue('class');
			$questions = [];
			foreach ($_POST as $key => $value){				
				if(strpos($key,"id_question-")!==false){
					$id_question_lang = str_replace("id_question-","",$key);
					$id_question_lang = explode("_",$id_question_lang);
					$id_question = (int)($id_question_lang[0]);
					$id_lang = (int)$id_question_lang[1];					
					if($id_lang=="" || $value==""){continue;}				
					$questions[$id_question]["id_question"]=$id_question;				
					$questions[$id_question]["content"][$id_lang] = $value;		
				}				
			}
			foreach ($questions as $item){				
				$question = new QuizzQuestions($item["id_question"]);
				foreach ($item["content"] as $key => $value){
					$question->content[$key] = $this->vireP($value);
				}				
				$result = $question->save();				
				if ($result) {
					$confirmations['message']= 'Mise à jour réussie.';
				}else{
					$errors['message'].= '<br>Erreur sur '.$item["id_question"];
				}
			}
			if(!empty($errors)){$this->errors[] = $errors['message'];}else{$this->confirmations[] = $confirmations['message'];}
        }elseif(Tools::getValue('type')){
			$type=Tools::getValue('type');
			if (Tools::isSubmit('submitquizz'.$type)) {				
				$class=Tools::getValue('class');
				$tabupdater = [];
				foreach ($_POST as $key => $value){				
					if(strpos($key,"id_$type-")!==false){
						$tab_id_lang = str_replace("id_$type-","",$key);
						$tab_id_lang = explode("_",$tab_id_lang);
						$id = (int)($tab_id_lang[0]);
						$id_lang = (int)$tab_id_lang[1];					
						if($id_lang=="" || $value==""){continue;}				
						$tabupdater[$id]["id_$type"]=$id;				
						$tabupdater[$id]["content"][$id_lang] = $value;
					}
					if(strpos($key,"level-")!==false){
						$tab_id_lang = str_replace("level-","",$key);
						$tab_id_lang = explode("_",$tab_id_lang);
						$id = (int)($tab_id_lang[0]);
						$id_lang = (int)$tab_id_lang[1];					
						if($id_lang=="" || $value==""){continue;}
						$tabupdater[$id]["level"][$id_lang] = $value;
					}
					if(strpos($key,"name_section-")!==false){
						$tab_id_lang = str_replace("name_section-","",$key);
						$tab_id_lang = explode("_",$tab_id_lang);
						$id = (int)($tab_id_lang[0]);
						$id_lang = (int)$tab_id_lang[1];					
						if($id_lang=="" || $value==""){continue;}
						$tabupdater[$id]["name_section"][$id_lang] = $value;
					}
					if(strpos($key,"niveau-")!==false){
						$tab_id_lang = str_replace("niveau-","",$key);
						$tab_id_lang = explode("_",$tab_id_lang);
						$id = (int)($tab_id_lang[0]);
						$id_lang = (int)$tab_id_lang[1];					
						if($id_lang=="" || $value==""){continue;}
						$tabupdater[$id]["niveau"][$id_lang] = $value;
					}
				}
				foreach ($tabupdater as $item){				
					$object = new $class($item["id_$type"]);				
					if(isset($item["content"])){
						foreach ($item["content"] as $key => $value){
							$object->content[$key] = $this->vireP($value);
						}	
					}
					if(isset($item["level"])){
						foreach ($item["level"] as $key => $value){
							$object->level[$key] = $this->vireP($value);
						}	
					}
					if(isset($item["name_section"])){
						foreach ($item["name_section"] as $key => $value){
							$object->name_section[$key] = $this->vireP($value);
						}	
					}
					if(isset($item["niveau"])){
						foreach ($item["niveau"] as $key => $value){
							$object->niveau[$key] = $this->vireP($value);
						}	
					}								
					//$object->id_question = $item["id_question"];
					$result = $object->save();				
					if ($result) {
						$confirmations['message']= 'Mise à jour réussie.';
					}else{
						$errors['message'].= '<br>Erreur sur '.$item["id_$type"];
					}
				}			
				if(!empty($errors)){$this->errors[] = $errors['message'];}else{$this->confirmations[] = $confirmations['message'];}
			}
		}
		
		
		parent::postProcess();
	}
    public function vireP($string)
    {
        return str_replace("<p>","",str_replace("</p>","",$string));
    }
	private function getBooleanValues()
    {
        return array(
				array(
					'id' => 'active_on',
					'value' => 1,
				),
				array(
					'id' => 'active_off',
					'value' => 0,
				)				
			);
    }
    //marsnetwork@hotmail.fr
	
    public function ajaxProcessProcessImport()
    {
        $reponse = array();
        if(Tools::getValue('csv_files') || Tools::isSubmit('submitWic_erpfile')){
			
			$f = Tools::getValue('csv_files');
			//on traite les fichiers, enn traitant qu'un certain nombre de lignes à la fois
			//$current_file_index = (int)Tools::getValue('current_file_index', 0);
			$current_file_index = 0;       
			$separator = ';';
			$continue_csv = false;
			$count = 0;
			$done = 0;
			
			$count_files = 0;
				if (!is_file($f)) {
					$reponse['message'] .= basename($f)." Inexistant sur le FTP\n";
					$current_file_index++;
				}
				if ($count_files == $current_file_index) {
					if ($count == 0){
						$rows_to_examines = count(file($f, FILE_SKIP_EMPTY_LINES));
						$reponse['message'] .= "\n---------------------------------------------------------------------------------------------\n".basename($f)."\n";
						$reponse['message'] .= (int)((int)$rows_to_examines - 1) ." lignes à traiter\n";
					}
					
					if (($handle = fopen($f, "r")) !== false) {
						$row = 0;
						
						while (($data = fgetcsv($handle,5000, $separator)) !== false) {
							
							$row++;
							
							if ($row == 1)//ligne de titre enlevée 
								continue;
								
							if ($row < $count) 
								continue;
							
							if ($row > $count + $rows_to_examines) {
								$count = $row;
								$continue_csv = true;								
								break;
							}
							if ($row > $rows_to_examines) {
								$continue_csv = false;
								
								break;
							}else{
								$reponse['message'] .= "\n\n----------------------------------------------------------------------------------\n Starting line ".$row.	"\n----------------------------------------------------------------------------------\n";
								
								$reponse['message'] .= $this->processRow($data);  							
								
							}                                 
						}
						fclose($handle);
					}else{
						$reponse['message'] .="Impossible d'ouvrir le fichier";
					}
					//Si on a finit un fichier
					if (!$continue_csv) {
						$count = 0;
						$done = 1;
						$reponse['message'] .= "\n\n----------------------------------------------------------------------------------\n\n".$row." lignes traitées\n\n----------------------------------------------------------------------------------\nTRAITEMENT DU FICHIER TERMINE\n\n----------------------------------------------------------------------------------";
						$current_file_index++;
					}
				 }else{
					$reponse['message'].="Problème avec le fichier";					
				}
				$count_files++;
			
			if($done==1){
				echo json_encode($reponse);
			}else{
				$reponse['message'] .= "\n\n erreur \n";
				echo json_encode($reponse);
			}		
		}else{
			echo "NON";
			echo json_encode("BAD");
		}		
    }
    
    private function processRow($row,$soldes=false,$updateexist=false)
    {		
		if(!is_array($row) || !$row){			
			return $reponse.= "\n Fichier vide ";
		}		
        $reponse = '';
        //Shop::setContext(Shop::CONTEXT_ALL);
		$active=1;
		$id_lang=1;	
			$listfields = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = "'.self::$base.'" AND TABLE_NAME = "'._DB_PREFIX_.self::$tables.'"';			
			$runlistfields = Db::getInstance()->query($listfields);			
			$cells = "`active`,`id_lang`";
			$values = '"'.$active.'","'.$id_lang.'"';
			$toupdate='active="1",id_lang="'.$id_lang.'"';
			$plus=-1;
			$img_vid="";
			foreach($runlistfields as $data){
				if($data[0]!="last_update" && $data[0]!="active" && $data[0]!="id_lang" && $data[0]!="id" && $data[0]!="code_city"){
					$plus++;
					if(isset($row[$plus])){
						$cells .= ", `".$data[0].'`';
						$values .= ', "'.str_replace('"','\"',str_replace("'","\'",$row[$plus])).'"';
						$toupdate .= ", `".$data[0].'`="'.str_replace('"','\"',str_replace("'","\'",$row[$plus])).'"';
						if(isset($row[$plus]) && $plus==1){
							$name=$row[$plus];
						}
					}
				}
			}
			$toupdate.=',last_update="'.date("Y-m-d H:i:s").'"';
			$finalinsert = 'INSERT INTO `'.self::$base.'`.`'._DB_PREFIX_.self::$tables.'` ('.$cells.',`last_update`) VALUES ('.$values.',"'.date("Y-m-d H:i:s").'") ON DUPLICATE KEY UPDATE '.$toupdate;
			$finaldone = Db::getInstance()->execute($finalinsert);			   
        return $reponse;
    }
}