<?php
/* __construct()
initBreadcrumbs() //Set breadcrumbs array for the controller page
initToolbarTitle() //Set default toolbar_title to admin breadcrumb
viewAccess($disable = false) //Check rights to view the current tab
checkToken() //Check for security token
ajaxProcessHelpAccess() //Use HelperHelpAccess 
processFilter() //Set the filters used for the list display
Pоstprосess() //Process requests to the controller
processDeleteImage() //Execute the function $object->deleteImage() of the current class
processExport() //Export the function
processDelete() //The function to delete the current class object
processSave() //Call the right method for creating or updating object
processAdd() //The function to add the current class object
processUpdate() //The function to update the current class object
processUpdateFields() //Change object required fields
processStatus() //Change object status (active, inactive)
processPosition() //Change object position
processResetFilters() //Cancel all filters for this tab
processUpdateOptions() //Update options and preferences
initToolbar() //Assign default action in toolbar_btn smarty var, if they are not set. Override to specifically add, modify or remove items
loadObject($opt = false) //Load class object using identifier in $_GET (if possible)  otherwise return an empty object, or die
checkAccess() //Check if the token is valid, else display a warning page
filterToField() //Filter by field
displayNoSmarty() //Does not have implementation
displayAjax() //The function uses the layout-ajax.tpl template to display data 
redirect() // Header('Location: '.$this->redirect_after);
Display() //Display content for the class $this- >layout
displayWarning($msg) //Add a warning message to be displayed at the top of the page
displayInformation($msg) //Add an info message to be displayed at the top of the page
initHeader() //Assign smarty variables for the header
addRowAction($action) //Declare an action to use for each row in the list
addRowActionSkipList($action, $list) //Add  an action to use for each row in the list
initContent() //Assign smarty variables for all default views, list and form, then call other init functions
initTabModuleList() //Init tab modules list and add button in toolbar
addToolBarModulesListButton() //Add the button «Modules List» to the page with modules
initCursedPage() //Initialize the invalid doom page of death
initFooter() //Assign smarty variables for the footer
renderModulesList() //Return $helper->renderModulesList($this->modules_list);
renderList() //Function used to render the list to display for this controller
renderView() //Override to render the view page
renderForm() //Function used to render the form for this controller
renderOptions() //Function used to render the options for this controller
setHelperDisplay(Helper $helper) //This function sets various display option for a helper list
setMedia() //This function connects css and js files
l($string, $class = 'AdminTab', $addslashes = false, $htmlentities = true) //Non-static method which uses AdminController::translate()
init() //Init context and dependencies, handles POST and GET
initShopContext() //Init context for shop context
initProcess() //Retrieve GET and POST value and translate them to actions
getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false) //Get the current objects' list form the database
getModulesList($filter_modules_list) //Get the list of modules
getLanguages()
getFieldsValue($obj) //Return the list of fields value
getFieldValue($obj, $key, $id_lang = null) //Return field value if possible (both classical and multilingual fields)
validateRules($class_name = false) //Manage page display (form, list...)
_childValidation() // Overload this method for custom checking
viewDetails() // Display object details
beforeDelete($object) // Called before deletion
afterDelete($object, $oldId) //Called before deletion
afterAdd($object) //The function is executed  after  it is added
afterUpdate($object) //The function is executed after it is updated
afterImageUpload() //Check rights to view the current tab
copyFromPost(&$object, $table) //Copy datas from $_POST to object
getSelectedAssoShop($table) //Returns an array with selected shops and type (group or boutique shop)
updateAssoShop($id_object) //Update the associations of shops
validateField($value, $field) //If necessary, the verifications goes via the Validate class
beforeUpdateOptions() //The method, executed prior to options update function, does not have implementation by default
postImage($id) //Overload this method for custom checking
uploadImage($id, $name, $dir, $ext = false, $width = null, $height = null) //Image upload function
processBulkDelete() //Delete multiple items
processBulkEnableSelection() //Enable multiple items
processBulkDisableSelection() //Disable multiple items
processBulkStatusSelection($status) //Toggle status of multiple items
processBulkAffectZone() //Execute the method affectZoneToSelection() of the current object
beforeAdd($object) //Called before Add
displayRequiredFields() //Prepare the view to display the required fields form
createTemplate($tpl_name) //Create a template from the override file, else from the base file.
jsonConfirmation($message) //Shortcut to set up a json success payload
jsonError($message) //Shortcut to set up a json error payload
isFresh($file, $timeout = 604800000) //Verify the necessity of updating cache
refresh($file_to_refresh, $external_file) //Update the file $file_to_refresh with the new data from 
fillModuleData(&$module, $output_type = 'link', $back = null) //Fill the variables of the class Module
displayModuleOptions($module, $output_type = 'link', $back = null) //Display modules list */
//require_once (dirname(__file__) . '/quizz.php');
require_once (dirname(__FILE__). '/../../classes/Faq.php');
class AdminFaqController extends ModuleAdminControllerCore
{
    protected $_module = NULL;
	public $controller_type='admin';   
    protected $position_identifier = 'id_belvg_faq'; //this filed is required if you use position for sorting

    public function __construct()
    {
		$this->display = 'options';
        $this->context = Context::getContext();
		$this->bootstrap = true;
        $this->table = 'belvg_faq';
        $this->identifier = 'id_belvg_faq';
        $this->className = 'Faq';
        $this->_defaultOrderBy = 'id_belvg_faq';
        $this->lang = TRUE;
		$this->allow_export = true;
        //$this->addRowAction('edit');
        //$this->addRowAction('delete');
        Shop::addTableAssociation($this->table, array('type' => 'shop'));
		
        $this->fields_list = array(
            'id_belvg_faq' => array('title' => ('ID'), 'filter_key' => 'a!id_belvg_faq', 'align' => 'center', 'width' => 25,'class' => 'fixed-width-xs','remove_onclick' => true),            
            'title' => array('title' => ('Title'), 'width' => '300', 'filter_key' => 'b!title','lang' => true,'remove_onclick' => true),
            'content' => array('title' => ('Content'), 'width' => '300','lang' => true,'remove_onclick' => true),
			'position' => array('title' => ('Position'), 'position' => 'position','remove_onclick' => true),
			'manual_position' => array('title' => ('Position Manuelle'), 'type' => 'editable', 'id' => $this->id, 'callback' => 'editableCombinationPriceMethod','remove_onclick' => true),			
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
		$this->context->smarty->assign('id', (int)$question['id_belvg_faq']);
		return $this->context->smarty->fetch($this->getTemplatePath().'editable_field.tpl');
    }	
	public function renderList()
	{
		//https://www.prestashop.com/forums/topic/1063433-param%C3%A8tre-champs-en-fonction-dun-autre-dans-admin-controller-module/
		$list = parent::renderList();	
		$this->context->smarty->assign(
		array(		  
		  'ajaxUrl' => $this->context->link->getAdminLink('AdminFaq')
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
                'title' => $this->module->l('Edit FAQ'),
                'icon' => 'icon-cog'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => ('Title:'),
                    'name' => 'title',
                    'id' => 'title', 
                    'lang' => TRUE,
                    'required' => TRUE,
                    'hint' => ('Invalid characters:').' <>;=#{}',
                    'size' => 50,
				],
                [
					'type' => 'textarea',
					'label' => $this->l('content MULTILANG'),
					'name' => 'content',
					'cols' => 60,
					'required' => false,
					'lang' => true,
					'rows' => 10,
					'class' => 'rte',
					'autoload_rte' => true,
					'hint' => ('Invalid characters:').' <>;=#{}',
				],
				/* [                    
                    'label' => ('Position'),
                    'name' => 'position',
                    'required' => FALSE,
                ], */
				[
                    'type' => 'text',
                    'label' => ('Position Manuelle'),
                    'name' => 'manual_position',
                    'required' => FALSE,
					'size' => 20,
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

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = array(
                'type' => 'shop',
                'label' => ('Shop association:'),
                'name' => 'checkBoxShopAsso',
                );
        }
		$helper = parent::renderForm();
        return $helper;
    }
	public function ajaxProcessManualPosition() {		
		
		$id=Tools::getValue('id'); 
		$valeur=Tools::getValue('valeur');
		$data[]=$id;
		$data[]=$valeur;
		
		$sql="UPDATE `"._DB_PREFIX_."belvg_faq` set manual_position=".$valeur." where id_belvg_faq=".$id;
		$manual_position = Db::getInstance()->query($sql); 		
		echo json_encode($data);//something you want to return 
		exit; 
	}
	/* public function postProcess()
	{
		//if ((Tools::isSubmit('manual_position_post')) == true) {
			print_r($_POST);
			$id_belvg_faq = (int)Tools::getValue('id_belvg_faq_ajax');
			$manual_position = (int)Tools::getValue('manual_position_ajax');
			$sql="UPDATE `"._DB_PREFIX_."belvg_faq` set manual_position=".$manual_position." where id_belvg_faq=".$id_belvg_faq;
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