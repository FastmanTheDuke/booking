<?php
require_once (dirname(__FILE__). '/../../classes/BookerAuth.php');
class AdminBookerAuthController extends ModuleAdminControllerCore
{
    protected $_module = NULL;
	public $controller_type='admin';   
    protected $position_identifier = 'id_auth'; //this filed is required if you use position for sorting

    public function __construct()
    {
		$this->display = 'options';
        $this->context = Context::getContext();
		$this->bootstrap = true;
        $this->table = 'booker_auth';
        $this->identifier = 'id_auth';
        $this->className = 'BookerAuth';
        $this->_defaultOrderBy = 'id_auth';
		$this->allow_export = true;
        //$this->addRowAction('edit');
        //$this->addRowAction('delete');
       // Shop::addTableAssociation($this->table, array('type' => 'shop'));
		
        $this->fields_list = array(
            'id_auth' => array('title' => ('ID Auth'), 'filter_key' => 'a!id_auth', 'align' => 'center','remove_onclick' => true),       
            'id_booker' => array('title' => ('ID Booker'), 'filter_key' => 'a!id_booker', 'align' => 'center','remove_onclick' => true),
            'date_from' => array('title' => ('From'), 'filter_key' => 'a!date_from', 'align' => 'center','remove_onclick' => true),
            'date_to' => array('title' => ('To'), 'filter_key' => 'a!date_to', 'align' => 'center','remove_onclick' => true),			
            'active' => array('title' => ('Displayed'), 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'orderby' => FALSE,'remove_onclick' => true),
        );
		$this->bulk_actions = array('delete' => array('text' => 'Delete selected',
                    'confirm' => 'Delete selected items?'));
					
        
        $this->has_bulk_actions = true;
        $this->shopLinkType = '';
        $this->no_link = false;       // Content line is clickable if false
        $this->simple_header = false; // false = search header, true = not header. No filters, no paginations and no sorting.
        $this->actions = array('edit', 'delete');
		$this->list_no_link = true;
		//$this->initToolbar();
		//$this->processFilter();
		//$this->tpl_list_vars['ajaxUrl'] = $this->context->link->getModuleLink($this->module->name,'display', array('ajax'=>true));		
		
		//echo $this->context->link->getAdminLink('AdminController');
		//echo $this->context->link->getAdminLink('AdminFaq');
		//$this->setTemplate('module:'.$this->module->name.'/views/templates/front/display.tpl');
        parent::__construct();
    }
	public function editableField($manual_position){
		//echo $manual_position;
		$this->context->smarty->assign('manual_position', $manual_position);  
		//return $this->module->setTemplate('module:quizz/views/templates/admin/editable_field.tpl')->fetch();
		return $this->context->smarty->fetch($this->getTemplatePath().'editable_field.tpl');
	}
	public function editableCombinationPriceMethod($value, $question)
    {
		$this->context->smarty->assign('value', $value);  
		$this->context->smarty->assign('id', (int)$question['id_auth']);
		return $this->context->smarty->fetch($this->getTemplatePath().'editable_field.tpl');
    }	
	public function renderList()
	{
		//https://www.prestashop.com/forums/topic/1063433-param%C3%A8tre-champs-en-fonction-dun-autre-dans-admin-controller-module/
		$list = parent::renderList();	
		$this->context->smarty->assign(
		array(		  
		  'ajaxUrl' => $this->context->link->getAdminLink('AdminBookerAuth')
		));
		$content = $this->context->smarty->fetch($this->getTemplatePath().'ajax.tpl');
		return $list . $content;
	}
    public function renderForm()
    {
        $this->display = 'edit';
        $this->initToolbar();
		
        $this->fields_form = [       
            //'tinymce' => true,
			//'legend' => "test",
			//'title' => $this->l('FAQ list'),
			//'icon' => 'icon-tags',
			'legend' => [
                'title' => $this->module->l('Edit Booker Auth'),
                'icon' => 'icon-cog'
            ],
            'input' => [
               /*  [
                    'type' => 'text',
                    'label' => ('Id AUTH:'),
                    'name' => 'id_auth',
                    'id' => 'id_auth', 
                    'required' => TRUE,
				], */
				[
					'type' => 'select',
					'label' => $this->l('AUTH:'),
					'id' => 'id_booker',
					'name' => 'id_booker',
					'required' => true,
					'options' => array(
						'query' => $idevents = array(
							array(
								'id_booker' => 1,
								'label' => 'id_booker 1',
								'value' => 'id_booker 1',
								'name' => 'id_booker 1',
								'option' => 'id_booker 1'
							),
							array(
								'id_booker' => 2,
								'label' => 'id_booker 2',
								'value' => 'id_booker 2',
								'name' => 'id_booker 2',
								'option' => 'id_booker 2'
							),  
							array(
								'id_booker' => 3,
								'label' => 'id_booker 3',
								'value' => 'id_booker 3',
								'name' => 'id_booker 3',
								'option' => 'id_booker 3'
							),                                        
						),
						'id' => 'id_booker',
						'name' => 'id_booker'
					)
				],
				[
                    'type' => 'datetime',
                    'label' => ('From:'),
                    'name' => 'date_from',
                    'id' => 'date_from', 
                    'required' => TRUE,
				],
                [
                    'type' => 'datetime',
                    'label' => ('To:'),
                    'name' => 'date_to',
                    'id' => 'date_to', 
                    'required' => TRUE,
				],
                
                [
                    'type' => 'switch',
                    'label' => ('Displayed:'),
                    'name' => 'active',
                    'required' => FALSE,
                    'is_bool' => FALSE,
                    'values' => array(array(
                            'id' => 'require_on',
                            'value' => 1,
                            'label' => ('Yes')), array(
                            'id' => 'require_off',
                            'value' => 0,
                            'label' => ('No'))),
                ]
			],
            'submit' => ['title' => ('   Save   ')],			
		];

       /*  if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = array(
                'type' => 'shop',
                'label' => ('Shop association:'),
                'name' => 'checkBoxShopAsso',
                );
        } */
		$helper = parent::renderForm();
		/* $helper->show_toolbar = true;
		$helper->bulk_actions = true; 
		$helper->identifier = "id_auth";
		$this->has_bulk_actions = true;
		*/
        return $helper;
    }
	public function initPageHeaderToolbar()
    {
 
        //Bouton d'ajout
        $this->page_header_toolbar_btn['new'] = array(
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->module->l('Add new Booker Auth'),
            'icon' => 'process-icon-new'
        );
 
        parent::initPageHeaderToolbar();
    } 
	public function ajaxProcessManualPosition() {		
		
		$id=Tools::getValue('id'); 
		$valeur=Tools::getValue('valeur');
		$data[]=$id;
		$data[]=$valeur;
		
		$sql="UPDATE `"._DB_PREFIX_."booker` set manual_position=".$valeur." where id_auth=".$id;
		$manual_position = Db::getInstance()->query($sql); 		
		echo json_encode($data);//something you want to return 
		exit; 
	}
	/* public function postProcess()
	{
		//if ((Tools::isSubmit('manual_position_post')) == true) {
			print_r($_POST);
			$id_auth = (int)Tools::getValue('id_auth_ajax');
			$manual_position = (int)Tools::getValue('manual_position_ajax');
			$sql="UPDATE `"._DB_PREFIX_."booker` set manual_position=".$manual_position." where id_auth=".$id_auth;
			$manual_position = Db::getInstance()->query($sql); 
		//}		
		parent::postProcess();
		return false;
	} */
	/* public function initPageHeaderToolbar()
    {
 
        //Bouton d'ajout
        $this->page_header_toolbar_btn['new'] = array(
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->module->l('Add new Sample'),
            'icon' => 'process-icon-new'
        );
 
        parent::initPageHeaderToolbar();
    } */
	/* public function setMedia() {
        parent::setMedia();       
        $this->context->controller->addJS('modules/'.$this->name.'/js/ajax.js');
        
    } */
}