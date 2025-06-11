<?php 
if (!defined('_PS_VERSION_')) {
    exit;
}
error_reporting(E_ALL & ~E_NOTICE);
ini_set('error_reporting', E_ALL & ~E_NOTICE);
require_once (dirname(__FILE__). '/classes/Faq.php');
require_once (dirname(__FILE__). '/classes/Booker.php');
require_once (dirname(__FILE__). '/classes/BookerAuth.php');
require_once (dirname(__FILE__). '/classes/BookerAuthReserved.php');
class Quizz extends Module  {
	protected $token = "";
	static $base = _DB_NAME_;
	
	public function __construct()
    {
        $this->name = 'quizz';
        $this->tab = 'others';
        $this->version = '0.1.0';
        $this->author = 'BBb';
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Quizz');
        $this->description = $this->l('Testez vos clients !');
		$this->set_token();		
    }

    /**
     * Installation du module
     * @return bool
     */
    public function install()
    {
        if (!parent::install()
            || !$this->registerHook([
                'displayCMSDisputeInformation',
                'filterCmsContent',
                'actionFrontControllerSetMedia',
                'displayBackOfficeHeader',
            ]) 
			|| !$this->installSql()
			|| !$this->installTab()
        ) {
            return false;
        }
        return true;
    }
    public function uninstall()
    {
        if (
            !parent::uninstall()
        ) {
            return false;
        }
		$this->uninstallTab();
        return true;
    }
	public function installSql()
    {
        try {
            //Création de la table avec les champs communs
            $createTable = Db::getInstance()->execute(
                "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."belvg_faq`(
					`id_belvg_faq` bigint(9) NOT NULL,
				  `active` tinyint(1) NOT NULL DEFAULT '1',
				  `id_lang` tinyint(1) NOT NULL,
				  `position` int(11) DEFAULT NULL,
				  `title` varchar(1000) DEFAULT NULL,
				  `content` varchar(1000) DEFAULT NULL,
					PRIMARY KEY (`id_belvg_faq`)
                ) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
				ALTER TABLE `"._DB_PREFIX_."belvg_faq` ADD INDEX(`active`);
				ALTER TABLE `"._DB_PREFIX_."belvg_faq` ADD INDEX(`id_lang`);
				ALTER TABLE `"._DB_PREFIX_."belvg_faq` CHANGE `id` `id_belvg_faq` BIGINT(9) NOT NULL AUTO_INCREMENT;
				"
            );
			require_once (dirname(__FILE__). '/sql/booker.php');
			require_once (dirname(__FILE__). '/sql/bookerauth.php');
			require_once (dirname(__FILE__). '/sql/bookerauthreserved.php');
        } catch (PrestaShopException $e) {
            return false;
        }
 
        //return $createTable && $createTableLang;
        return $createTable;
    }	
	private function installTab()
	{
		$tabs = Tab::getTabs(1);
		foreach ($tabs as $tab) {
			$position=$tab["position"];
		}
		$tab_id = Tab::getIdFromClassName('QUIZZ');
		$languages = Language::getLanguages(false);

		if ($tab_id == false)
		{
			$tab = new Tab();
			$tab->class_name = 'QUIZZ';
			$tab->position = $position;
			$tab->id_parent = 0;
			$tab->module = null;
			$tab->wording = "QUIZZ";
			$tab->wording_domain = "Admin.Navigation.Menu";
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "QUIZZ";
			}
			$tab->add();
		}	
		
		/* $tab_id = Tab::getIdFromClassName('AdminQuizzParams');
		if ($tab_id == false)
		{
			$tab = new Tab();
			$tab->class_name = 'AdminQuizzParams';
			$tab->position = 1;
			$tab->id_parent = (int)Tab::getIdFromClassName('QUIZZ');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Paramètres";
			}
			$tab->add();
		} */
		
		$tab_id = Tab::getIdFromClassName('AdminQuizz');	
		if ($tab_id == false)
		{
			$tab = new Tab();
			$tab->class_name = 'AdminQuizz';
			$tab->position = 1;
			$tab->id_parent = (int)Tab::getIdFromClassName('QUIZZ');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Quizz";
			}
			$tab->add();
		}
		
		/* $tab_id = Tab::getIdFromClassName('AdminQuizzList');	
		if ($tab_id == false)
		{
			$tab = new Tab();
			$tab->class_name = 'AdminQuizzList';
			$tab->position = 1;
			$tab->id_parent = (int)Tab::getIdFromClassName('QUIZZ');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Quizz List";
			}
			$tab->add();
		} */
		
		$tab_id = Tab::getIdFromClassName('AdminFaq');	
		if ($tab_id == false)
		{
			$tab = new Tab();
			$tab->class_name = 'AdminFaq';
			$tab->position = 1;
			$tab->id_parent = (int)Tab::getIdFromClassName('QUIZZ');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "FAQ List";
			}
			$tab->add();
		}
		$tab_id = Tab::getIdFromClassName('AdminBooker');	
		if ($tab_id == false)
		{
			$tab = new Tab();
			$tab->class_name = 'AdminBooker';
			$tab->position = 1;
			$tab->id_parent = (int)Tab::getIdFromClassName('QUIZZ');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Bookers";
			}
			$tab->add();
		}
		$tab_id = Tab::getIdFromClassName('AdminBookerAuth');	
		if ($tab_id == false)
		{
			$tab = new Tab();
			$tab->class_name = 'AdminBookerAuth';
			$tab->position = 1;
			$tab->id_parent = (int)Tab::getIdFromClassName('QUIZZ');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Booker Auth";
			}
			$tab->add();
		}
		$tab_id = Tab::getIdFromClassName('AdminBookerAuthReserved');	
		if ($tab_id == false)
		{
			$tab = new Tab();
			$tab->class_name = 'AdminBookerAuthReserved';
			$tab->position = 1;
			$tab->id_parent = (int)Tab::getIdFromClassName('QUIZZ');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Booker Auth Reserved";
			}
			$tab->add();
		}
		$tab_id = Tab::getIdFromClassName('AdminBookerView');	
		if ($tab_id == false)
		{
			$tab = new Tab();
			$tab->class_name = 'AdminBookerView';
			$tab->position = 1;
			$tab->id_parent = (int)Tab::getIdFromClassName('QUIZZ');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Booker View";
			}
			$tab->add();
		}
		
		return true;
	}
	private function uninstallTab()
	{
		$tab = (int)Tab::getIdFromClassName('QUIZZ');
		if ($tab) {
			$mainTab = new Tab($tab);

			try {
				$mainTab->delete();
			} catch (Exception $e) {
				echo $e->getMessage();

				return false;
			}
		}
		
		$tab = (int)Tab::getIdFromClassName('AdminQuizzParams');
		if ($tab) {
			$mainTab = new Tab($tab);

			try {
				$mainTab->delete();
			} catch (Exception $e) {
				echo $e->getMessage();

				return false;
			}
		}
		
		$tab = (int)Tab::getIdFromClassName('AdminQuizz');
		if ($tab) {
			$mainTab = new Tab($tab);

			try {
				$mainTab->delete();
			} catch (Exception $e) {
				echo $e->getMessage();

				return false;
			}
		}
		
		/* $tab = (int)Tab::getIdFromClassName('AdminQuizzList');
		if ($tab) {
			$mainTab = new Tab($tab);

			try {
				$mainTab->delete();
			} catch (Exception $e) {
				echo $e->getMessage();

				return false;
			}
		} */
		
		$tab = (int)Tab::getIdFromClassName('AdminFAQ');
		if ($tab) {
			$mainTab = new Tab($tab);

			try {
				$mainTab->delete();
			} catch (Exception $e) {
				echo $e->getMessage();

				return false;
			}
		}
		$tab = (int)Tab::getIdFromClassName('AdminBooker');
		if ($tab) {
			$mainTab = new Tab($tab);

			try {
				$mainTab->delete();
			} catch (Exception $e) {
				echo $e->getMessage();

				return false;
			}
		}
		$tab = (int)Tab::getIdFromClassName('AdminBookerAuth');
		if ($tab) {
			$mainTab = new Tab($tab);

			try {
				$mainTab->delete();
			} catch (Exception $e) {
				echo $e->getMessage();

				return false;
			}
		}
		$tab = (int)Tab::getIdFromClassName('AdminBookerAuthReserved');
		if ($tab) {
			$mainTab = new Tab($tab);

			try {
				$mainTab->delete();
			} catch (Exception $e) {
				echo $e->getMessage();

				return false;
			}
		}
		$tab = (int)Tab::getIdFromClassName('AdminBookerView');
		if ($tab) {
			$mainTab = new Tab($tab);

			try {
				$mainTab->delete();
			} catch (Exception $e) {
				echo $e->getMessage();

				return false;
			}
		}
		return true;
	}
	
	private function set_token()
	{
		$this->token=md5("Petit#FastHerbier4ward%Happe|0308Smok81");
		return $this->token;
	}
	
	public function hookDisplayCMSDisputeInformation(){
		//avant le chargement du contenu - body content	
		if ('cms' === $this->context->controller->php_self && $this->context->controller->cms->id_cms==6) {
			if (Tools::isSubmit('results')) {
				$result_quizz = $this->getResults();
				//print_r($result_quizz);
				$questions=$this->getQuestions();
				$this->context->smarty->assign(
				array(
				  'questions' => $questions,
				  'result_quizz' => $result_quizz,
				));
			}else{
				$questions=$this->getQuestions();
				$this->context->smarty->assign(
				array(
				  'questions' => $questions,
				));
			}	
			return $this->display(__FILE__, 'views/templates/hook/quizz.tpl');
		}
	}	
	public function getContent()
    {
		$output = '';
		/* if (((bool)Tools::isSubmit('createOriginSupplier')) == true) {
            $msg = '';
			$tmp = Shop::getShops();
			$shops = array();
			foreach($tmp  as $t)$shops[] = $t['id_shop'];		
			$output .= $this->displayConfirmation($msg);
        }
		if (Tools::isSubmit('submit' . $this->name)) {
			//API KEY
			$configValue = (string) Tools::getValue('APIKEY_CONFIG');
			// check that the value is valid
			if (empty($configValue) || !Validate::isGenericName($configValue)) {				
				$output = $this->displayError($this->l('Invalid Configuration value'));
			} else {				
				Configuration::updateValue('APIKEY_CONFIG', $configValue);
				$output = $this->displayConfirmation($this->l('Settings updated'));
			}
			//CMS OR NOT
			$cms = (int) Tools::getValue('cms');
			// check that the value is valid
			if (empty($cms)) {				
				//$output = $this->displayError($this->l('Invalid Configuration value'));
				if (Configuration::get('CMS_LOCATOR')!=false && Configuration::get('CMS_LOCATOR')!="") {
					$cms=new CMS(Configuration::get('CMS_LOCATOR'));
					$cms->delete();
					Configuration::deleteByName('CMS_LOCATOR');
				}
			} else {
				if ($cms!=0 && (Configuration::get('CMS_LOCATOR')==false || Configuration::get('CMS_LOCATOR')=="")) {
					$cms=new CMS();
					$cms->meta_title[1]=$this->l("TROUVER UN REVENDEUR");  //1 is lang id
					$cms->link_rewrite[1] = "trouver-un-revendeur";  //1 is lang id
					$cms->head_seo_title = $this->l("TROUVER UN REVENDEUR");
					$cms->meta_description = $this->l("TROUVER UN REVENDEUR");
					$cms->content = "Recherchez une boutique ou un revendeur en tapant une ville dans le champs de recherche";
					$cms->id_cms_category=1;
					$cms->indexation=1;
					$cms->active=1;
					$cms->add();
					$id_cms=$cms->id;
					Configuration::updateValue('CMS_LOCATOR', $id_cms);
				}
			}
			//CATEGORY CMS OR NOT
			$category_cms = (int) Tools::getValue('category_cms');
			// check that the value is valid
			if (empty($category_cms)) {				
				//$output = $this->displayError($this->l('Invalid Configuration value'));
				if (Configuration::get('CATEGORYCMS_LOCATOR')!=false && Configuration::get('CMS_LOCATOR')!="") {
					$category_cms=new CMSCategory(Configuration::get('CATEGORYCMS_LOCATOR'));
					$category_cms->delete();
					Configuration::deleteByName('CATEGORYCMS_LOCATOR');
				}
			} else {
				if ($category_cms!=0 && (Configuration::get('CATEGORYCMS_LOCATOR')==false || Configuration::get('CATEGORYCMS_LOCATOR')=="")) {
					$category_cms=new CMSCategory();
					$category_cms->name[1]="Liste des revendeurs / boutiques";  //1 is lang id
					$category_cms->meta_title[1]="";  //1 is lang id
					$category_cms->link_rewrite[1] = "liste-revendeurs";  //1 is lang id
					$category_cms->head_seo_title = "";
					$category_cms->meta_description = "";
					$category_cms->id_parent=1;
					$category_cms->active=0;
					$category_cms->add();
					$id_category_cms=$category_cms->id;
					Configuration::updateValue('CATEGORYCMS_LOCATOR', $id_category_cms);
				}
			}
		}
        $this->context->smarty->assign('module_dir', $this->_path);
		$this->context->smarty->assign('import_controller_link', $this->context->link->getAdminLink('AdminDealerlocator'));
		$this->context->smarty->assign('module_link', $this->context->link->getModuleLink('dealerlocator','display'));
		
        
		if(Configuration::get('CMS_LOCATOR')==false || Configuration::get('CMS_LOCATOR')==""){
			$this->context->smarty->assign('cms_id', 0);
		}else{			
			$this->context->smarty->assign('cms_id', Configuration::get('CMS_LOCATOR'));
			$this->context->smarty->assign('cms_link', $this->context->link->getCMSLink(Configuration::get('CMS_LOCATOR')));
		}
		if(Configuration::get('CATEGORYCMS_LOCATOR')==false || Configuration::get('CATEGORYCMS_LOCATOR')==""){
			$this->context->smarty->assign('category_cms_id', 0);
		}else{
			$this->context->smarty->assign('category_cms_id', Configuration::get('CATEGORYCMS_LOCATOR'));
		}formulariospain
		$this->context->smarty->assign('champsmultilangue', ""); */
		//$output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
		
		//$output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/config_tabs.tpl');			
		$type="fr";
		$this->context->smarty->assign('type1', $type);				
		$this->context->smarty->assign('form'.$type, $this->renderFormTab("$type"));
		$type="en";				
		$this->context->smarty->assign('form'.$type, $this->renderFormTab("$type"));
		$output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/config.tpl');
		
        return $output . $this->displayForm();
        //return $this->displayForm().$this->renderFormTab("$type1");
    }
	public function renderFormTab($type)
	{
		// Init Fields form array
		$form = [
			'form' => [
				//Main tabs top
				/* 'tabs' => [
					
					'config'.$type => "Configuración principal".$type,
					'pedidos'.$type => "Configuración pedidos".$type,
					'transportes'.$type => "Configuración Transportes".$type,
				], */
				'input' => [
					[
						'col' => 3,
						//'tab' => 'config'.$type,
						'type' => 'text',
						'prefix' => '<i class="icon icon-key 1"></i>',
						'desc' => $this->l('Enter API Key'),
						'name' => 'submitMirakl_marketplace_api_API_KEY_CFSPAIN1'.$type,
						'label' => $this->l('API Key'),
					],
					[
						'col' => 3,
						//'tab' => 'pedidos'.$type,
						'type' => 'text',
						'prefix' => '<i class="icon icon-key 2"></i>',
						'desc' => $this->l('Enter API Key'),
						'name' => 'submitMirakl_marketplace_api_API_KEY_CFSPAIN2'.$type,
						'label' => $this->l('API Key'),
					],
					[
						'col' => 3,
						//'tab' => 'transportes'.$type,
						'type' => 'text',
						'prefix' => '<i class="icon icon-key"></i>',
						'desc' => $this->l('Enter API Key 3'),
						'name' => 'submitMirakl_marketplace_api_API_KEY_CFSPAIN3'.$type,
						'label' => $this->l('API Key'),
					],
				],
				'submit' => [
					'name' => 'submit'.$this->name.$type,
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right',
				],
			],
		];

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->table = $this->table;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
		//$helper->show_toolbar = false;        // false -> remove toolbar
		//$helper->toolbar_scroll = false;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit' . $this->name.$type;
		
		// Languages
		/* $languages = Language::getLanguages(true);
		foreach ($languages as $k => $language){
			$helper->fields_value['champsmultilangue'][(int)$language['id_lang']]="";
		}
		// Default language
		$languages = Language::getLanguages(false);
		foreach ($languages as $k => $language){
			$languages[$k]['is_default'] = (int)($language['id_lang'] == Configuration::get('PS_LANG_DEFAULT'));
		} */
		
		$helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
		$helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
		$helper->languages = $languages;

		$helper->toolbar_btn = array(
			'save' =>
			array(
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.$type.
				'&token='.Tools::getAdminTokenLite('AdminModules'),
			),
			'back' => array(
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);
		// Load current value into the form
		//$print = new PrintTabObject($this->context->shop->id);
		
		//$helper->fields_value['APIKEY_CONFIG'] = Tools::getValue('APIKEY_CONFIG', Configuration::get('APIKEY_CONFIG'));
		/* if(Configuration::get('CMS_LOCATOR')==false || Configuration::get('CMS_LOCATOR')==""){
			$cms=0;
		}else{
			$cms=1;
		}
		$helper->fields_value['cms'] = Tools::getValue('CMS_LOCATOR', $cms);
		if(Configuration::get('CATEGORYCMS_LOCATOR')==false || Configuration::get('CATEGORYCMS_LOCATOR')==""){
			$categorycms=0;
		}else{
			$categorycms=1;
		}
		$helper->fields_value['category_cms'] = Tools::getValue('CATEGORYCMS_LOCATOR', $categorycms);  */
		
		return $helper->generateForm([$form]);
		//return "cool";
	}
	public function displayForm()
	{
		// Init Fields form array
		/* $form = [
			'form' => [
				'legend' => [
					'title' => $this->l('Settings'),
				],
				'input' => [
					[
						'type' => 'text',
						'label' => $this->l('GOOGLE API KEY'),
						'name' => 'APIKEY_CONFIG',
						'size' => 20,
						'required' => true,
					],
					[
						'type' => 'textarea',
						'label' => $this->l('TEST MULTILANG'),
						'name' => 'champsmultilangue',
						'cols' => 60,
						'required' => false,
						'lang' => true,
						'rows' => 10,
						'class' => 'rte',
						'autoload_rte' => true,
					],				
					[
						'name' => 'cms',
						'label' => $this->l('Utiliser une PAGE CMS'),
						'hint' => $this->l('En plus de la page du module, vous pouvez créer et utiliser une PAGE CMS (Apparence->pages) comme page d\'atterissage du LOCATOR. 	Si vous cochez OUI, cela créera une page si cela n\'a pas encore été fait par le module. Si vous passez de Oui à Non, elle sera supprimée.'),
						'type' => 'switch',
						'is_bool' => true,
						'values' => $this->getBooleanValues(),
						
					],
					[
						'name' => 'category_cms',
						'label' => $this->l('Générer une page CMS par boutique/revendeur'),
						'hint' => $this->l('En plus de la page du module, vous pouvez créer et utiliser une PAGE CMS par ligne insérée. Utile pour le SEO, à condition de les alimenter. Pour cela, allez directement dans Apparence->pages->Catégories->Locator, vous trouverez autant de pages que de ligne importées via votre CSV !'),
						'type' => 'switch',
						'is_bool' => true,
						'values' => $this->getBooleanValues(),
						
					],
				],
				'submit' => [
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right',
				],
			],
		];

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->table = $this->table;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
		//$helper->show_toolbar = false;        // false -> remove toolbar
		//$helper->toolbar_scroll = false;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit' . $this->name;
		
		// Languages
		$languages = Language::getLanguages(true);
		foreach ($languages as $k => $language){
			$helper->fields_value['champsmultilangue'][(int)$language['id_lang']]="";
		}
		// Default language
		$languages = Language::getLanguages(false);
		foreach ($languages as $k => $language){
			$languages[$k]['is_default'] = (int)($language['id_lang'] == Configuration::get('PS_LANG_DEFAULT'));
		}
		
		$helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
		$helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
		$helper->languages = $languages;

		$helper->toolbar_btn = array(
			'save' =>
			array(
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
				'&token='.Tools::getAdminTokenLite('AdminModules'),
			),
			'back' => array(
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);
		// Load current value into the form
		//$print = new PrintTabObject($this->context->shop->id);
		
		$helper->fields_value['APIKEY_CONFIG'] = Tools::getValue('APIKEY_CONFIG', Configuration::get('APIKEY_CONFIG'));
		if(Configuration::get('CMS_LOCATOR')==false || Configuration::get('CMS_LOCATOR')==""){
			$cms=0;
		}else{
			$cms=1;
		}
		$helper->fields_value['cms'] = Tools::getValue('CMS_LOCATOR', $cms);
		if(Configuration::get('CATEGORYCMS_LOCATOR')==false || Configuration::get('CATEGORYCMS_LOCATOR')==""){
			$categorycms=0;
		}else{
			$categorycms=1;
		}
		$helper->fields_value['category_cms'] = Tools::getValue('CATEGORYCMS_LOCATOR', $categorycms); 
		
		return $helper->generateForm([$form]);*/
		return "cool";
	}
	public function _postProcess()
    {       
        return $this->getResults();        
    }
	public function getResults()
	{
		$quizz = [];
		
		$id_lang=$this->context->language->id;
		
		$questions_sql = 'SELECT section,max_points FROM `'._DB_PREFIX_.'quizz_questions` group by section';
		$questionsbase = $questions = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($questions_sql);
		
		if($questions){
			foreach($questions as $question){
				$niveau="";
				$description="";
				if($question["max_points"]==0){
					$question["max_points"]="";
					${"total_section_".$question["section"]}="";
					$name_section = "";
					$niveau = "";
					$description = "";
					$image="";
				}else{
					$results_names_sql = 'SELECT * FROM `'._DB_PREFIX_.'quizz_results_names` q
					INNER JOIN `'._DB_PREFIX_.'quizz_results_names_lang` ql on ql.id_result=q.id_result and ql.id_lang='.$id_lang.'
					where q.section="'. $question["section"] .'"';
					$results_names = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($results_names_sql);
					if($results_names){
						foreach($results_names as $results_name){
							if((float)${"total_section_".$question["section"]}>=(float)$results_name["delimiter1"] && (float)${"total_section_".$question["section"]}<=(float)$results_name["delimiter2"]){
								$name_section = $results_name["name_section"];
								$niveau = $results_name["niveau"];
								$description = $results_name["content"];
							}
						}
					}
				}
				$quizz["total_section_".$question["section"]]=["points"=>${"total_section_".$question["section"]}, "name_section"=>$name_section, "max_points"=>$question["max_points"], "niveau"=>$niveau, "description"=>$description];
			}
		}
		$eco=0;
		//$questions_sql = 'SELECT * FROM `'._DB_PREFIX_.'quizz_questions` where id_lang="'. $id_lang .'"';
		$questions_sql = 'SELECT q.id_question,q.section,q.type,ql.content FROM `'._DB_PREFIX_.'quizz_questions` q 
		INNER JOIN `'._DB_PREFIX_.'quizz_questions_lang` ql on ql.id_question=q.id_question and ql.id_lang='.$id_lang;
		
		$questions = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($questions_sql);		
		if($questions){
			$total_points=0;
			${"total_section_1"}=0;
			${"total_section_2"}=0;
			${"total_section_3"}=0;
			${"total_section_4"}=0;
			$current_points=0;
			$nb_clopes=0;
			$type_clopes=0;
			$nb_annees=0;
			$nb_minutes=0;			
			foreach($questions as $question){					
				$answer=$_POST["question_".$question["id_question"]];
				
				$choix_sql = 'SELECT c.*,cl.content FROM `'._DB_PREFIX_.'quizz_choices` c 
				INNER JOIN `'._DB_PREFIX_.'quizz_choices_lang` cl on cl.id_choice=c.id_choice and cl.id_lang='.$id_lang.'
				where id_question ="'. $question["id_question"] .'"';				
				$choix = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($choix_sql);
				
				if($choix){
					$finalchoices = [];
					foreach($choix as $choice){
						if($question["id_question"]==1){
							$nb_clopes = $answer;
							$eco_sql = 'SELECT content FROM `'._DB_PREFIX_.'quizz_ecos` where delimiter1<="'. $nb_clopes .'" and delimiter2>="'. $nb_clopes .'"';
							$montant_eco = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($eco_sql);						
						}
						
						if($question["id_question"]==2){
							$id_type_clopes = $answer;
							$type_clopes = $choice["content"];
						}
						if($question["id_question"]==3){
							$nb_annees = $choice["content"];
						}
						if($question["id_question"]==4){
							$nb_minutes = $choice["content"];
						}
						if($choice["content"]=="numeric" || $choice["content"]=="decimal"){
							if($choice["points"]==0){
								//echo $id_type_clopes;
								if($id_type_clopes==7){
									$specialpoints=$choice["specialpoints"];
								}else{
									$specialpoints=$answer;
								}
								/* echo $nb_clopes;
								echo $montant_eco; */
								//Si tu gruges 
								$eco = ((($specialpoints/20) * $nb_clopes) * 365) - $montant_eco;
								$prixannuel = ((($specialpoints/20) * $nb_clopes) * 365);
								${"total_section_".$question["section"]}=$eco;
								${"prixannuel_".$question["section"]}=$prixannuel;							
								
								$finalchoices[$choice["id_choice"]] = ["id_choice"=>$choice["id_choice"],"content"=>"numeric","delimiter1"=>$choice["delimiter1"],"delimiter2"=>$choice["delimiter2"],"points"=>$choice["points"],"current_points"=>$eco,"prix"=>$answer,"nb_clopesperday"=>$nb_clopes,"answer"=>$eco];
							}else{
								if($answer>=$choice["delimiter1"] && $answer<=$choice["delimiter2"]){
									$current_points=$choice["points"];
									${"total_section_".$question["section"]}+=$choice["points"];									
									$finalchoices[$choice["id_choice"]] = ["id_choice"=>$choice["id_choice"],"content"=>"numeric","delimiter1"=>$choice["delimiter1"],"delimiter2"=>$choice["delimiter2"],"points"=>$choice["points"],"current_points"=>$current_points,"answer"=>round($answer,0)];
								}else{
									$current_points=0;								
								}								
							}
						}else{
							
							if((float)$answer == (float)$choice["id_choice"]){
								$current_points=$choice["points"];
								${"total_section_".$question["section"]}+=$choice["points"];
								$finalchoices[$choice["id_choice"]] = ["id_choice"=>$choice["id_choice"],"content"=>$choice["content"],"delimiter1"=>0,"delimiter2"=>0,"points"=>$choice["points"],"current_points"=>$current_points,"answer"=>$choice["content"]];
							}else{
								$current_points=0;
							}													
						}						
					}
					$quizz[$question["id_question"]]=["id_question"=>$question["id_question"],"content"=>$question["content"],"type"=>$question["type"],"choix"=>$finalchoices];
				}				
			}
		}		
		$count=0;
		if($questionsbase){			
			foreach($questionsbase as $question){
				$description="";	
				$niveau="";
				$eco="";
				$taux="";
				$nicotinedesc="";
				$name_section="";
				if($question["max_points"]=="0"){
					//$results_names_sql = 'SELECT description,name_section FROM `'._DB_PREFIX_.'quizz_results_names` where id_lang="'. $id_lang .'" and section="'. $question["section"] .'"';
					$results_names_sql = 'SELECT * FROM `'._DB_PREFIX_.'quizz_results_names` q
					INNER JOIN `'._DB_PREFIX_.'quizz_results_names_lang` ql on ql.id_result=q.id_result and ql.id_lang='.$id_lang.'
					where q.section="'. $question["section"] .'"';
					$results_names = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($results_names_sql);
					$name_section = $results_names["name_section"];
					$description = $results_names["content"];
					
					$question["max_points"]=${"prixannuel_".$question["section"]}."€";
					//echo ${"total_section_".$question["section"]};
					if(${"total_section_".$question["section"]}<0){${"total_section_".$question["section"]}=0;}
					${"total_section_".$question["section"]}= $eco = ${"total_section_".$question["section"]}."€";
					
					
					$nicotine_sql = 'SELECT n.id_nicotine,level,content FROM `'._DB_PREFIX_.'quizz_nicotine` n
					INNER JOIN `'._DB_PREFIX_.'quizz_nicotine_lang` nl on nl.id_nicotine=n.id_nicotine and id_lang='.$id_lang.'
					where delimiter1<="'. $nb_clopes .'" and delimiter2>="'. $nb_clopes .'"';
					$nicotine = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($nicotine_sql);
					if($nicotine){	
						$taux=$nicotine["level"];
						$nicotinedesc=$nicotine["content"];
					}							
					
					
					$niveau = "";
					$image=0;
				}else{
					//$results_names_sql = 'SELECT * FROM `'._DB_PREFIX_.'quizz_results_names` where id_lang="'. $id_lang .'" and section="'. $question["section"] .'"';			
					$results_names_sql = 'SELECT * FROM `'._DB_PREFIX_.'quizz_results_names` q
					INNER JOIN `'._DB_PREFIX_.'quizz_results_names_lang` ql on ql.id_result=q.id_result and ql.id_lang='.$id_lang.'
					where q.section="'. $question["section"] .'"';
					$results_names = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($results_names_sql);
					if($results_names){
						foreach($results_names as $results_name){
							if((float)${"total_section_".$question["section"]}>=(float)$results_name["delimiter1"] && (float)${"total_section_".$question["section"]}<=(float)$results_name["delimiter2"]){
								$name_section = $results_name["name_section"];
								$niveau = $results_name["niveau"];
								$description = $results_name["content"];
							}
						}
					}
					$image=1;
				}
				if($id_type_clopes==7){$mg=$nb_clopes * 2;}else{$mg=$nb_clopes;}
				$description = str_replace("%nb_clopes%","<b>$nb_clopes</b>",str_replace("%nb_annees%","<b>$nb_annees</b>",str_replace("%type_clopes%","<b>$type_clopes</b>",str_replace("%mg%","<b>$mg mg</b>",str_replace("%nb_minutes%","<b>$nb_minutes</b>",str_replace("%eco%",'<span class="eco"><b>'.$eco.'</b></span>',$description))))));
				
				$percent=round(${"total_section_".$question["section"]}/$question["max_points"] * 100,0);
				
				$quizz["total_section_".$question["section"]]=["points"=>${"total_section_".$question["section"]}, "name_section"=>$name_section, "section"=>$question["section"], "max_points"=>$question["max_points"], "max_points"=>$question["max_points"], "niveau"=>$niveau, "description"=>$description, "percent"=>$percent];
							
				if($eco!=""){$quizz["total_section_".$question["section"]]["eco"]=$eco;}
				if($image!=0){$quizz["total_section_".$question["section"]]["image"]=1;}
				if($taux!=""){$quizz["total_section_".$question["section"]]["taux"]=$taux;}
				if($nicotinedesc!=""){$quizz["total_section_".$question["section"]]["nicotinedesc"]=$nicotinedesc;}
				
			}
		}
		return $quizz;
	}
	public function getQuestions()
	{
		
		$quizz = [];
		$id_lang=$this->context->language->id;
		$questions_sql = 'SELECT q.id_question,q.type,ql.content FROM `'._DB_PREFIX_.'quizz_questions` q 
		INNER JOIN `'._DB_PREFIX_.'quizz_questions_lang` ql on ql.id_question=q.id_question and ql.id_lang='.$id_lang;
		//$ids = Db::getInstance()->getValue($size_id_sql);
		$questions = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($questions_sql);
		if($questions){
			foreach($questions as $question){
				$choix_sql = 'SELECT c.*,cl.content FROM `'._DB_PREFIX_.'quizz_choices` c 
				INNER JOIN `'._DB_PREFIX_.'quizz_choices_lang` cl on cl.id_choice=c.id_choice and cl.id_lang='.$id_lang.'
				where c.id_question ="'. $question["id_question"] .'"';				
				$choix = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($choix_sql);
				if($choix){
					$finalchoices = [];
					foreach($choix as $choice){
						if($choice["content"]=="numeric" || $choice["content"]=="decimal"){
							$finalchoices[$choice["id_choice"]] = ["id_choice"=>$choice["id_choice"],"content"=>"numeric","delimiter1"=>$choice["delimiter1"],"delimiter2"=>$choice["delimiter2"],"points"=>$choice["points"]];
						}else{
							$finalchoices[$choice["id_choice"]] = ["id_choice"=>$choice["id_choice"],"content"=>$choice["content"],"delimiter1"=>0,"delimiter2"=>0,"points"=>$choice["points"]];
						}
						
					}
					$quizz[$question["id_question"]]=["id_question"=>$question["id_question"],"content"=>$question["content"],"type"=>$question["type"],"choix"=>$finalchoices];
				}
			}
		}
		//print_r($quizz);
		return $quizz;
	}	
	public function hookActionFrontControllerSetMedia($params)
	{
		// Only on product page
		
		if ('cms' === $this->context->controller->php_self && $this->context->controller->cms->id_cms==6) {
			$this->context->controller->registerStylesheet(
				'module-quizz-style',
				'modules/'.$this->name.'/css/quizz.css',
				[
				  'media' => 'all',
				  'priority' => 200,
				]
			);

			$this->context->controller->registerJavascript(
				'module-quizz-simple-lib',
				'modules/'.$this->name.'/js/quizz.js',
				[
				  'priority' => 200,
				  'attribute' => 'async',
				]
			);
		}

		// On every pages
		/* $this->context->controller->registerJavascript(
			'google-analytics',
			'modules/'.$this->name.'/ga.js',
			[
			  'position' => 'head',
			  'inline' => true,
			  'priority' => 10,
			]
		); */
	}
	public function hookActionAdminControllerSetMedia($params)
	{
		if(Tools::getValue("controller")=="AdminBookerView"){
			$this->context->controller->addCSS('modules/'.$this->name.'/views/css/AdminBookerView.css');
			
		}
	}
	public function hookDisplayBackOfficeHeader($params)
	{		
		$this->context->controller->addJS('modules/'.$this->name.'/js/tabs.js');
	}
}
?>