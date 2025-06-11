<?php
/*
 * 08/2106
 * beforebigbang/La Teapot du web pour le vieux plongeur
 * Import version 32 : on a désormais la possibilité de créer des produits;
 * ceci associe aussi aux boutiques les produits
 */

//exemple de produit à tester  98-6048G_OCE dans le fichier BASE AUP 2016.csv


class AdminQuizzListController extends ModuleAdminControllerCore
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
        $this->addJS(_MODULE_DIR_.$this->module->name.'/views/js/admin-importsupplier.js');
        
        $option = '';
        
        $this->context->smarty->assign('import_controller_link', $this->context->link->getAdminLink('AdminQuizz'));
        $option .= $this->context->smarty->fetch($this->getTemplatePath().'supplier-ipmport-headear.tpl');
        
       
		//$listfields = "SELECT id_question,content,(SELECT name_section FROM `"._DB_PREFIX_."quizz_questions` qq WHERE qq.section=q.section LIMIT 1) as 'section' FROM `"._DB_PREFIX_."quizz_questions` q ";
		$listfields = "SELECT id_question,id_lang,content FROM `"._DB_PREFIX_."quizz_questions` q ";
		$runlistfields = Db::getInstance()->query($listfields);
		//print_r($runlistfields);
		$fields_value = array(           
            'action' => 'processImport',
            'ajax' => '1'
        );
		
        $options_files[] = array('name' => 'action', 'type' => 'hidden');
        $options_files[] = array('name' => 'ajax', 'type' => 'hidden');
		// Languages
		$languages = Language::getLanguages(true);
		
		$helper = new HelperForm();
		
        foreach($runlistfields as $key => $value){
			
			$options_files[] = array(
				'type' => 'textarea',
				'label' => $this->l("Question ". (int)($key+1)),
				'name' => "id_question_".$key,
				'cols' => 60,
				'required' => false,
				'lang' => true,
				'rows' => 10,
				'class' => 'rte',
				'autoload_rte' => true,
			);
			foreach($value as $k => $v){
				
				if(is_string($k)){
					if($k=="id_lang"){
						$id_lang=$v;
					}else{
						//$fields_value["id_question_$key"] = $v;
						$fields_value["id_question_$key"][(int)$id_lang]=$v;
					}					
					/* foreach ($languages as $k => $language){
						if()
						$helper->fields_value["id_question_$key"][(int)$language['id_lang']]=$v;
					} */
				}
			}
		}
		
		
		
		// Default language
		$languages = Language::getLanguages(false);
		foreach ($languages as $k => $language){
			$languages[$k]['is_default'] = (int)($language['id_lang'] == Configuration::get('PS_LANG_DEFAULT'));
		}
        
		/* print_r($options_files);
		print_r($fields_value); */
        
		$form_actions = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Quizz Questions'),
                    'icon' => 'icon-plus-sign-alt'
                ),
                'input' => $options_files,                
                'submit' => array(
                    'title' => $this->l('Mettre à jour'),
                    'name' => 'submit'.$this->module->name,
                )
            ),
        );
        //print_r($form_actions);
        
        
        /* if (Tools::getValue('csv_file') && is_file(Tools::getValue('csv_file')))
            $fields_value['csv_file'] = Tools::getValue('csv_file');
        if ((int)Tools::getValue('id_supplier'))
            $fields_value['id_supplier'] = Tools::getValue('id_supplier'); */

        
       
        $helper->show_toolbar = false;
		
		
		
        $helper->languages = $languages;
		$helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
		$helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $this->fields_form = array();
        
        $helper->submit_action = 'submit'.$this->module->name;
        $helper->currentIndex = 0;
        $helper->token =  $this->token;
        
        $helper->tpl_vars = array(
            'fields_value' => $fields_value,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        
        $option .= $helper->generateForm(array($form_actions));
        //$option .= $this->getAddFileForm();
        
        return $option;
    }
	public function renderList()
    {
        /* $sql = new DbQuery();
        $sql->select('pa.*, pr.price as proprice, if(pr.active = 1 , true, false) as prostatus, pl.name as pname, ag.id_attribute_group, ag.is_color_group, agl.name AS group_name, al.name AS attribute_name, a.id_attribute, pa.unit_price_impact');
        $sql->from('product_attribute', 'pa');
        $sql->leftJoin('product_attribute_combination', 'pac', 'pac.id_product_attribute = pa.id_product_attribute');
        $sql->leftJoin('attribute', 'a', 'a.id_attribute = pac.id_attribute');
        $sql->leftJoin('product', 'pr', 'pr.id_product = pa.id_product');
        $sql->leftJoin('product_lang', 'pl', 'pl.id_product = pr.id_product AND pl.id_lang = 1');
        $sql->leftJoin('attribute_group', 'ag', 'ag.id_attribute_group = a.id_attribute_group');
        $sql->leftJoin('attribute_lang', 'al', '(a.id_attribute = al.id_attribute AND al.id_lang = 1)');
        $sql->leftJoin('attribute_group_lang', 'agl', '(ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = 1)');
        //$sql->where('pr.active = 1');
        $sql->orderBy('pa.id_product_attribute');
        $links = Db::getInstance()->ExecuteS($sql);
        // CONTENT
        $fields_list = array(
            'pname' => array(
                'title' => $this->trans('Product', array(), 'Admin.Global'),
                'align' => 'center',            // Align Col (not header)
                //'width' => 50,                // header search : width
                'type'  => 'text',
                'class' => 'fixed-width-xs',    // class css
                'search' => true,              // header search : display
                'orderby' => true,              // header search : order

            ),
            'attribute_name' => array(
                'title' => $this->l('Attribute'),
                'type' => 'text',
            ),
            'reference' => array(
                'title' => $this->l('Reference'),
                'type' => 'text',
            ),
            'price' => array(
                'title' => $this->trans('Impact Price Tax(Excl)', array(), 'Admin.Global'),
                'type' => 'editable',
                'ajax' => true,
                'inline' => true,
				'callback' => 'editableCombinationPriceMethod',
				//'id' => 'unique_id'
            ),
            'proprice' => array(
                'title' => $this->trans('Price Tax(Excl)', array(), 'Admin.Global'),
                'type' => 'text',
            ),
            'prostatus' => array(
                'title' => $this->l('Status'),
                'type' => 'bool',
                'align' => 'center',
                'active' => 'status',
                'ajax' => true,
            )
        ); */
		$sql="SELECT id_question,id_lang,content FROM `"._DB_PREFIX_."quizz_questions`";
		$links = Db::getInstance()->ExecuteS($sql);
		$fields_list = array(
            'id_question' => array(
                'title' => "ID",
                'align' => 'center',            // Align Col (not header)
                //'width' => 50,                // header search : width
                'type'  => 'text',
                'class' => 'fixed-width-xs',    // class css
                'search' => true,              // header search : display
                'orderby' => true,              // header search : order

            ),
            'id_lang' => array(
                'title' => $this->l('id_lang'),
                'type' => 'text',
            ),
            'content' => array(
                'title' => $this->l('content'),
                'type' => 'editable',
                'ajax' => true,
				'callback' => 'editableCombinationPriceMethod',
            ),            
        );


        // TOOLBAR
        $this->initToolbar();
       
        $helper = new HelperList();
        
       /*  $helper->orderBy = 'id_product';
        $helper->orderWay = 'DESC';
        $helper->bulk_actions = true;       // bulk_actions
       
        $helper->shopLinkType = '';
        $helper->no_link = true;       // Content line is clickable if false
        $helper->simple_header = false; // false = search header, true = not header. No filters, no paginations and no sorting.
        $helper->actions = array('');
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->listTotal =  count($links);
        $helper->_default_pagination = 50;
        $helper->toolbar_scroll = true;
        $helper->identifier = 'id_product_attribute';
        $helper->title = $this->l('Product Combination Price List');
        $helper->table = 'product_attribute';
        $helper->token = Tools::getAdminTokenLite('BulkPriceUpdate');
        $helper->currentIndex = self::$currentIndex.'&details'; */
		
        $helper->orderBy = 'id_question';
        $helper->orderWay = 'DESC';
        $helper->bulk_actions = true;       // bulk_actions
       
        $helper->shopLinkType = '';
        $helper->no_link = false;       // Content line is clickable if false
        $helper->simple_header = true; // false = search header, true = not header. No filters, no paginations and no sorting.
        $helper->actions = array('edit', 'delete', 'view');
        $helper->show_toolbar = true;
        $helper->module = $this;
        $helper->listTotal =  count($links);
        $helper->_default_pagination = 50;
        $helper->toolbar_scroll = true;
        $helper->identifier = 'id_question';
        $helper->title = $this->l('Quizz List');
        $helper->table = 'quizz_questions';
       // $helper->token = Tools::getAdminTokenLite('BulkPriceUpdate');
		$helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = self::$currentIndex.'&details';
        return $helper->generateList($links, $fields_list);
    }
	public function postProcess()
	{		
		if (Tools::isSubmit('submitWic_erpfile')) {			
			$result=$this->postProcessUploadFile();
			if ($result!==false) {
				if ($result===true) {
					$this->confirmations[] = $this->l('Votre fichier a bien été téléversé.');
				}else{
					$this->confirmations[] = $result;
				}
            }
        }		
		parent::postProcess();
	}
	public function editableCombinationPriceMethod($value, $question)
    {
        //$question['content'] return the same than $value || current value/content
		return (int)$question['id_question']."-".(int)$question['id_lang']."-".$question['content'];
         
		 //return '<form class="qty active" method="post" id="form_'.(int)$question['id_question'].'" action="'.self::$currentIndex.'&id_question='.(int)$question['id_question'].'&changeCombinationPrice&token='.Tools::getAdminTokenLite('BulkPriceUpdate').'"><div class="ps-number edit-qty hover-buttons text-right" style="display:flex;" placeholder="0"><input type="number" step="any" name="price" class="form-control" value="'.$value.'" /><button type="submit" form="form_'.(int)$question['id_question'].'" class="check-button"><i class="material-icons list-action-enable">done</i></button></div></form>';
    }
	public function ajaxProcessTest()
	{
		$id_question = (int)Tools::getValue('id_question');
        echo "IDP: ".$id_question;
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