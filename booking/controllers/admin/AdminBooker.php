<?php
require_once (dirname(__FILE__). '/../../classes/Booker.php');
class AdminBookerController extends ModuleAdminControllerCore
{
    protected $_module = NULL;
	public $controller_type='admin';   
    protected $position_identifier = 'id_booker'; //this filed is required if you use position for sorting

    public function __construct()
    {
		$this->display = 'options';
        $this->context = Context::getContext();
		$this->bootstrap = true;
        $this->table = 'booker';
        $this->identifier = 'id_booker';
        $this->className = 'Booker';
        $this->_defaultOrderBy = 'id_booker';
        $this->lang = TRUE;
		$this->allow_export = true;
        //$this->addRowAction('edit');
        //$this->addRowAction('delete');
       // Shop::addTableAssociation($this->table, array('type' => 'shop'));
		
        $this->fields_list = array(
            'id_booker' => array('title' => ('ID'), 'filter_key' => 'a!id_booker', 'align' => 'center', 'width' => 25,'class' => 'fixed-width-xs','remove_onclick' => true),            
            'name' => array('title' => ('name'), 'width' => '300', 'filter_key' => 'b!name','remove_onclick' => true),
            'description' => array('title' => ('description'), 'width' => '300','lang' => true,'remove_onclick' => true),				
            'google_account' => array('title' => ('google_account'), 'width' => '300','remove_onclick' => true),
            'active' => array('title' => ('Displayed'), 'width' => 25, 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'orderby' => FALSE,'remove_onclick' => true),
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
		$this->context->smarty->assign('id', (int)$question['id_booker']);
		return $this->context->smarty->fetch($this->getTemplatePath().'editable_field.tpl');
    }	
	public function renderList()
	{
		//https://www.prestashop.com/forums/topic/1063433-param%C3%A8tre-champs-en-fonction-dun-autre-dans-admin-controller-module/
		$list = parent::renderList();	
		$this->context->smarty->assign(
		array(		  
		  'ajaxUrl' => $this->context->link->getAdminLink('AdminBooker')
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
                'title' => $this->module->l('Edit Booker'),
                'icon' => 'icon-cog'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => ('Name:'),
                    'name' => 'name',
                    'id' => 'name', 
                    'required' => TRUE,
                    'size' => 50,
				],
                [
					'type' => 'textarea',
					'label' => $this->l('Description MULTILANG'),
					'name' => 'description',
					'cols' => 60,
					'required' => false,
					'lang' => true,
					'rows' => 10,
					'class' => 'rte',
					'autoload_rte' => true,
					'hint' => ('Invalid characters:').' <>;=#{}',
				],
				[
                    'type' => 'text',
                    'label' => ('GOOGLE ACCOUNT:'),
                    'name' => 'google_account',
                    'id' => 'name',
                    'size' => 200,
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
		$helper->identifier = "id_booker";
		$this->has_bulk_actions = true;
		*/
        return $helper;
    }
	public function initPageHeaderToolbar()
    {
 
        //Bouton d'ajout
        $this->page_header_toolbar_btn['new'] = array(
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->module->l('Add new Booker'),
            'icon' => 'process-icon-new'
        );
 
        parent::initPageHeaderToolbar();
    } 
	public function ajaxProcessManualPosition() {		
		
		$id=Tools::getValue('id'); 
		$valeur=Tools::getValue('valeur');
		$data[]=$id;
		$data[]=$valeur;
		
		$sql="UPDATE `"._DB_PREFIX_."booker` set manual_position=".$valeur." where id_booker=".$id;
		$manual_position = Db::getInstance()->query($sql); 		
		echo json_encode($data);//something you want to return 
		exit; 
	}
	/* public function postProcess()
	{
		//if ((Tools::isSubmit('manual_position_post')) == true) {
			print_r($_POST);
			$id_booker = (int)Tools::getValue('id_booker_ajax');
			$manual_position = (int)Tools::getValue('manual_position_ajax');
			$sql="UPDATE `"._DB_PREFIX_."booker` set manual_position=".$manual_position." where id_booker=".$id_booker;
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