<?php 
if (!defined('_PS_VERSION_')) {
    exit;
}
error_reporting(E_ALL & ~E_NOTICE);
ini_set('error_reporting', E_ALL & ~E_NOTICE);

require_once (dirname(__FILE__). '/classes/Booker.php');
require_once (dirname(__FILE__). '/classes/BookerAuth.php');
require_once (dirname(__FILE__). '/classes/BookerAuthReserved.php');

class Booking extends Module  {
	protected $token = "";
	static $base = _DB_NAME_;
	
	public function __construct()
    {
        $this->name = 'booking';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'BBb';
        $this->bootstrap = true;
        $this->need_instance = 0;
        
        parent::__construct();

        $this->displayName = $this->l('Système de Réservations Avancé');
        $this->description = $this->l('Module complet de gestion de réservations avec calendriers interactifs, statuts avancés et intégration e-commerce');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ? Toutes les données de réservation seront perdues.');
        
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
		$this->set_token();		
    }

    /**
     * Installation du module
     */
    public function install()
    {
        if (!parent::install()
            || !$this->registerHook([
                'displayCMSDisputeInformation',
                'filterCmsContent',
                'actionFrontControllerSetMedia',
                'displayBackOfficeHeader',
                'actionCronJob',
                'displayHeader',
                'displayProductAdditionalInfo',
                'actionValidateOrder',
                'displayAdminOrderTabContent',
                'displayAdminOrderContentOrder',
                'actionOrderStatusUpdate',
                'displayShoppingCartFooter',
                'actionCartSave',
            ]) 
			|| !$this->installSql()
			|| !$this->installTab()
			|| !$this->installConfiguration()
        ) {
            return false;
        }
        
        // Ajouter les tâches cron
        $this->addCronTask();
        
        // Créer la page CMS de réservation
        $this->createBookingCMSPage();
        
        return true;
    }
    
    public function uninstall()
	{
		if (!parent::uninstall()) {
			return false;
		}
		
		// Désinstaller les onglets admin
		$this->uninstallTab();
		
		// Supprimer les tâches cron
		$this->removeCronTask();
		
		// Supprimer toutes les configurations
		$this->uninstallConfiguration();
		
		// ACTIVER la suppression des tables pour un nettoyage complet
		$this->uninstallSql();
		
		return true;
	}
    private function uninstallConfiguration()
	{
		$configs = [
			'BOOKING_CRON_CLEAN_RESERVATIONS',
			'BOOKING_DEFAULT_PRICE',
			'BOOKING_DEPOSIT_AMOUNT',
			'BOOKING_PAYMENT_ENABLED',
			'BOOKING_STRIPE_ENABLED',
			'BOOKING_AUTO_CONFIRM',
			'BOOKING_EXPIRY_HOURS',
			'BOOKING_MULTI_SELECT',
			'BOOKING_EMERGENCY_PHONE',
			'BOOKING_CMS_ID'
		];
		
		foreach ($configs as $config) {
			Configuration::deleteByName($config);
		}
		
		return true;
	}
	private function uninstallSql()
	{
		$tables = [
			'booker_auth_reserved',
			'booker_auth', 
			'booker_lang',
			'booker'
		];
		
		foreach ($tables as $table) {
			Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`');
		}
		
		return true;
	}
    /**
     * Installation de la configuration par défaut
     */
    private function installConfiguration()
    {
        return Configuration::updateGlobalValue('BOOKING_CRON_CLEAN_RESERVATIONS', 1)
            && Configuration::updateGlobalValue('BOOKING_DEFAULT_PRICE', 50)
            && Configuration::updateGlobalValue('BOOKING_DEPOSIT_AMOUNT', 100)
            && Configuration::updateGlobalValue('BOOKING_PAYMENT_ENABLED', 0)
            && Configuration::updateGlobalValue('BOOKING_STRIPE_ENABLED', 0)
            && Configuration::updateGlobalValue('BOOKING_AUTO_CONFIRM', 0)
            && Configuration::updateGlobalValue('BOOKING_EXPIRY_HOURS', 24)
            && Configuration::updateGlobalValue('BOOKING_MULTI_SELECT', 1)
            && Configuration::updateGlobalValue('BOOKING_EMERGENCY_PHONE', Configuration::get('PS_SHOP_PHONE'));
    }

	public function installSql()
	{
		try {
			$success = true;
			
			// Tables existantes
			require_once (dirname(__FILE__). '/sql/booker.php');
			if (!$booker || !$booker_lang) {
				PrestaShopLogger::addLog('Erreur création table booker');
				$success = false;
			}
			
			require_once (dirname(__FILE__). '/sql/bookerauth.php');
			if (!$bookerauth) {
				PrestaShopLogger::addLog('Erreur création table booker_auth');
				$success = false;
			}
			
			require_once (dirname(__FILE__). '/sql/bookerauthreserved.php');
			if (!$bookerauthreserved) {
				PrestaShopLogger::addLog('Erreur création table booker_auth_reserved');
				$success = false;
			}
			
			// Nouvelles colonnes pour les réservations
			$this->updateReservationTable();
			
			if (!$success) {
				throw new PrestaShopException('Erreur lors de la création des tables');
			}
			
			return true;
			
		} catch (Exception $e) {
			PrestaShopLogger::addLog('Erreur installation SQL module Booking: ' . $e->getMessage());
			return false;
		}
	}
	
	/**
	 * Mise à jour de la table des réservations
	 */
	private function updateReservationTable()
	{
		$alterQueries = [
			"ALTER TABLE `"._DB_PREFIX_."booker` 
			 ADD COLUMN IF NOT EXISTS `price` DECIMAL(10,2) DEFAULT 50.00 AFTER `google_account`,
			 ADD COLUMN IF NOT EXISTS `deposit_required` TINYINT(1) DEFAULT 1 AFTER `price`,
			 ADD COLUMN IF NOT EXISTS `auto_confirm` TINYINT(1) DEFAULT 0 AFTER `deposit_required`,
			 ADD COLUMN IF NOT EXISTS `booking_duration` INT(3) DEFAULT 60 AFTER `auto_confirm`"
		];
		
		foreach ($alterQueries as $query) {
			try {
				Db::getInstance()->execute($query);
			} catch (Exception $e) {
				PrestaShopLogger::addLog('Erreur mise à jour table: ' . $e->getMessage());
			}
		}
	}
    
	private function installTab()
	{
		$tabs = Tab::getTabs(1);
		$position = 0;
		foreach ($tabs as $tab) {
			$position = max($position, $tab["position"]);
		}
		$position++;
		
		$tab_id = Tab::getIdFromClassName('BOOKING');
		$languages = Language::getLanguages(false);

		if ($tab_id == false) {
			$tab = new Tab();
			$tab->class_name = 'BOOKING';
			$tab->position = $position;
			$tab->id_parent = 0;
			$tab->module = null;
			$tab->wording = "RESERVATIONS";
			$tab->wording_domain = "Admin.Navigation.Menu";
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "RESERVATIONS";
			}
			$tab->add();
		}	
		
		// Onglets du système de réservation
		$tabsToCreate = [
			'AdminBooker' => ['Éléments à réserver', 3],
			'AdminBookerAuth' => ['Disponibilités', 4],
			'AdminBookerAuthReserved' => ['Réservations', 5],
			'AdminBookerView' => ['Calendrier des réservations', 6],
			'AdminBookerCalendar' => ['Calendrier des disponibilités', 7],
		];
		
		foreach ($tabsToCreate as $className => $tabInfo) {
			$tab_id = Tab::getIdFromClassName($className);	
			if ($tab_id == false) {
				$tab = new Tab();
				$tab->class_name = $className;
				$tab->position = $tabInfo[1];
				$tab->id_parent = (int)Tab::getIdFromClassName('BOOKING');
				$tab->module = $this->name;
				foreach ($languages as $language) {
					$tab->name[$language['id_lang']] = $tabInfo[0];
				}
				$tab->add();
			}
		}
		
		return true;
	}
	
	private function uninstallTab()
	{
		$tabs_to_remove = [
			'BOOKING', 'AdminBooker', 'AdminBookerAuth', 
			'AdminBookerAuthReserved', 'AdminBookerView', 'AdminBookerCalendar'
		];
		
		foreach ($tabs_to_remove as $tab_class) {
			$tab_id = (int)Tab::getIdFromClassName($tab_class);
			if ($tab_id) {
				$tab = new Tab($tab_id);
				try {
					$tab->delete();
				} catch (Exception $e) {
					PrestaShopLogger::addLog('Erreur suppression onglet ' . $tab_class . ': ' . $e->getMessage());
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Créer la page CMS de réservation
	 */
	private function createBookingCMSPage()
	{
		$cms = new CMS();
		$cms->id_cms_category = 1; // Catégorie racine
		$cms->active = 1;
		$cms->indexation = 1;
		
		$languages = Language::getLanguages(false);
		foreach ($languages as $language) {
			$cms->meta_title[$language['id_lang']] = 'Réservation en ligne';
			$cms->meta_description[$language['id_lang']] = 'Réservez vos créneaux en ligne facilement et rapidement';
			$cms->meta_keywords[$language['id_lang']] = 'réservation, booking, créneau';
			$cms->content[$language['id_lang']] = '<p>Page de réservation en ligne. Le module se chargera automatiquement sur cette page.</p>';
			$cms->link_rewrite[$language['id_lang']] = 'reservation-en-ligne';
		}
		
		if ($cms->add()) {
			Configuration::updateValue('BOOKING_CMS_ID', $cms->id);
			return true;
		}
		
		return false;
	}
	
	private function set_token()
	{
		$this->token = md5("Booking#System4Advanced%Reservations|2024");
		return $this->token;
	}
	
	/**
	 * Configuration du module
	 */
	public function getContent()
    {
		$output = '';
		
		if (Tools::isSubmit('submit' . $this->name)) {
			$this->processConfiguration();
			$output .= $this->displayConfirmation($this->l('Configuration mise à jour'));
		}
		
		// Liens rapides
		$output .= $this->renderQuickLinks();
		
		// Formulaire de configuration
		$output .= $this->displayForm();
		
		// Statistiques des réservations
		$output .= $this->displayReservationStats();
		
        return $output;
    }
    
    /**
     * Traitement de la configuration
     */
    private function processConfiguration()
    {
    	$configFields = [
    		'BOOKING_DEFAULT_PRICE',
    		'BOOKING_DEPOSIT_AMOUNT', 
    		'BOOKING_PAYMENT_ENABLED',
    		'BOOKING_STRIPE_ENABLED',
    		'BOOKING_AUTO_CONFIRM',
    		'BOOKING_EXPIRY_HOURS',
    		'BOOKING_MULTI_SELECT',
    		'BOOKING_EMERGENCY_PHONE',
    		'BOOKING_CRON_CLEAN_RESERVATIONS'
    	];
    	
    	foreach ($configFields as $field) {
    		Configuration::updateValue($field, Tools::getValue($field));
    	}
    }
    
    /**
     * Liens rapides d'administration
     */
    private function renderQuickLinks()
    {
    	$links = [
    		[
    			'title' => 'Gérer les éléments',
    			'desc' => 'Créer et modifier les éléments à réserver',
    			'href' => $this->context->link->getAdminLink('AdminBooker'),
    			'icon' => 'icon-cog'
    		],
    		[
    			'title' => 'Disponibilités',
    			'desc' => 'Définir les créneaux de disponibilité',
    			'href' => $this->context->link->getAdminLink('AdminBookerAuth'),
    			'icon' => 'icon-calendar'
    		],
    		[
    			'title' => 'Réservations',
    			'desc' => 'Gérer les demandes de réservation',
    			'href' => $this->context->link->getAdminLink('AdminBookerAuthReserved'),
    			'icon' => 'icon-list'
    		],
    		[
    			'title' => 'Calendrier',
    			'desc' => 'Vue calendrier des réservations',
    			'href' => $this->context->link->getAdminLink('AdminBookerView'),
    			'icon' => 'icon-calendar-o'
    		]
    	];
    	
    	$html = '<div class="panel"><div class="panel-heading">
    		<i class="icon-cogs"></i> Accès rapide
    	</div><div class="panel-body">
    		<div class="row">';
    	
    	foreach ($links as $link) {
    		$html .= '<div class="col-lg-3 col-md-6">
    			<div class="media">
    				<div class="media-left">
    					<i class="' . $link['icon'] . ' icon-2x"></i>
    				</div>
    				<div class="media-body">
    					<h6 class="media-heading">
    						<a href="' . $link['href'] . '">' . $link['title'] . '</a>
    					</h6>
    					<p class="text-muted">' . $link['desc'] . '</p>
    				</div>
    			</div>
    		</div>';
    	}
    	
    	$html .= '</div></div></div>';
    	
    	// Afficher l'URL de la page de réservation
    	$cms_id = Configuration::get('BOOKING_CMS_ID');
    	if ($cms_id) {
    		$booking_url = $this->context->link->getCMSLink($cms_id);
    		$html .= '<div class="alert alert-info">
    			<strong>URL de réservation :</strong> 
    			<a href="' . $booking_url . '" target="_blank">' . $booking_url . '</a>
    		</div>';
    	}
    	
    	return $html;
    }
    
    public function displayForm()
    {
		$form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Configuration du système de réservation'),
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Prix par défaut (€)'),
						'name' => 'BOOKING_DEFAULT_PRICE',
						'desc' => $this->l('Prix par défaut pour un créneau de réservation'),
						'class' => 'fixed-width-sm',
					),
					array(
						'type' => 'text',
						'label' => $this->l('Montant de la caution (€)'),
						'name' => 'BOOKING_DEPOSIT_AMOUNT',
						'desc' => $this->l('Montant de caution à prélever lors du paiement'),
						'class' => 'fixed-width-sm',
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Activer les paiements'),
						'name' => 'BOOKING_PAYMENT_ENABLED',
						'desc' => $this->l('Permettre le paiement en ligne des réservations'),
						'is_bool' => true,
						'values' => array(
							array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')),
							array('id' => 'active_off', 'value' => 0, 'label' => $this->l('Non'))
						),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Intégration Stripe'),
						'name' => 'BOOKING_STRIPE_ENABLED',
						'desc' => $this->l('Utiliser Stripe pour les paiements (module Stripe requis)'),
						'is_bool' => true,
						'values' => array(
							array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')),
							array('id' => 'active_off', 'value' => 0, 'label' => $this->l('Non'))
						),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Confirmation automatique'),
						'name' => 'BOOKING_AUTO_CONFIRM',
						'desc' => $this->l('Confirmer automatiquement les réservations sans validation manuelle'),
						'is_bool' => true,
						'values' => array(
							array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')),
							array('id' => 'active_off', 'value' => 0, 'label' => $this->l('Non'))
						),
					),
					array(
						'type' => 'text',
						'label' => $this->l('Expiration des demandes (heures)'),
						'name' => 'BOOKING_EXPIRY_HOURS',
						'desc' => $this->l('Nombre d\'heures avant qu\'une demande non traitée expire'),
						'class' => 'fixed-width-sm',
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Sélection multiple'),
						'name' => 'BOOKING_MULTI_SELECT',
						'desc' => $this->l('Permettre la sélection de plusieurs créneaux en une fois'),
						'is_bool' => true,
						'values' => array(
							array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')),
							array('id' => 'active_off', 'value' => 0, 'label' => $this->l('Non'))
						),
					),
					array(
						'type' => 'text',
						'label' => $this->l('Téléphone d\'urgence'),
						'name' => 'BOOKING_EMERGENCY_PHONE',
						'desc' => $this->l('Numéro à contacter en cas d\'urgence le jour de la réservation'),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Nettoyage automatique'),
						'name' => 'BOOKING_CRON_CLEAN_RESERVATIONS',
						'desc' => $this->l('Nettoyer automatiquement les réservations expirées'),
						'is_bool' => true,
						'values' => array(
							array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')),
							array('id' => 'active_off', 'value' => 0, 'label' => $this->l('Non'))
						),
					),
				),
				'submit' => array(
					'title' => $this->l('Sauvegarder'),
					'class' => 'btn btn-default pull-right',
				),
			),
		);

		$helper = new HelperForm();
		$helper->table = $this->table;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(array('configure' => $this->name));
		$helper->submit_action = 'submit' . $this->name;
		$helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
		$helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');

		$helper->fields_value['BOOKING_DEFAULT_PRICE'] = Configuration::get('BOOKING_DEFAULT_PRICE');
		$helper->fields_value['BOOKING_DEPOSIT_AMOUNT'] = Configuration::get('BOOKING_DEPOSIT_AMOUNT');
		$helper->fields_value['BOOKING_PAYMENT_ENABLED'] = Configuration::get('BOOKING_PAYMENT_ENABLED');
		$helper->fields_value['BOOKING_STRIPE_ENABLED'] = Configuration::get('BOOKING_STRIPE_ENABLED');
		$helper->fields_value['BOOKING_AUTO_CONFIRM'] = Configuration::get('BOOKING_AUTO_CONFIRM');
		$helper->fields_value['BOOKING_EXPIRY_HOURS'] = Configuration::get('BOOKING_EXPIRY_HOURS');
		$helper->fields_value['BOOKING_MULTI_SELECT'] = Configuration::get('BOOKING_MULTI_SELECT');
		$helper->fields_value['BOOKING_EMERGENCY_PHONE'] = Configuration::get('BOOKING_EMERGENCY_PHONE');
		$helper->fields_value['BOOKING_CRON_CLEAN_RESERVATIONS'] = Configuration::get('BOOKING_CRON_CLEAN_RESERVATIONS');

		return $helper->generateForm(array($form));
    }
    
    /**
     * Afficher les statistiques des réservations
     */
    private function displayReservationStats()
    {
		$stats = array();
		$statuses = BookerAuthReserved::getStatuses();
		
		foreach ($statuses as $status_id => $status_label) {
			$count = Db::getInstance()->getValue('
				SELECT COUNT(*) 
				FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
				WHERE `status` = ' . (int)$status_id . ' 
				AND `active` = 1
			');
			$stats[] = array(
				'label' => $status_label,
				'count' => (int)$count,
				'status_id' => $status_id
			);
		}
		
		$html = '<div class="panel">
			<div class="panel-heading">
				<i class="icon-bar-chart"></i> Statistiques des réservations
			</div>
			<div class="panel-body">
				<div class="row">';
		
		foreach ($stats as $stat) {
			$alert_class = 'alert-info';
			switch ($stat['status_id']) {
				case BookerAuthReserved::STATUS_PENDING:
					$alert_class = 'alert-warning';
					break;
				case BookerAuthReserved::STATUS_ACCEPTED:
					$alert_class = 'alert-info';
					break;
				case BookerAuthReserved::STATUS_PAID:
					$alert_class = 'alert-success';
					break;
				case BookerAuthReserved::STATUS_CANCELLED:
				case BookerAuthReserved::STATUS_EXPIRED:
					$alert_class = 'alert-danger';
					break;
			}
			
			$html .= '<div class="col-lg-2 col-md-4 col-sm-6">
				<div class="alert ' . $alert_class . ' text-center">
					<div style="font-size: 2em; font-weight: bold;">' . $stat['count'] . '</div>
					<div>' . $stat['label'] . '</div>
				</div>
			</div>';
		}
		
		$html .= '</div></div></div>';
		
		return $html;
    }
    
    // Ajouter/supprimer tâches cron
    private function addCronTask()
    {
        Configuration::updateValue('BOOKING_CRON_CLEAN_RESERVATIONS', 1);
    }
    
    private function removeCronTask()
    {
        Configuration::deleteByName('BOOKING_CRON_CLEAN_RESERVATIONS');
    }
    
    /**
     * Hook pour les tâches cron
     */
    public function hookActionCronJob($params)
    {
        if (Configuration::get('BOOKING_CRON_CLEAN_RESERVATIONS')) {
            $expiry_hours = Configuration::get('BOOKING_EXPIRY_HOURS') ?: 24;
            BookerAuthReserved::cancelExpiredReservations($expiry_hours);
            
            PrestaShopLogger::addLog(
                'Nettoyage automatique des réservations expirées effectué',
                1,
                null,
                'BookerAuthReserved',
                null,
                true
            );
        }
    }
	
	/**
	 * Hook pour afficher l'interface de réservation sur la page CMS
	 */
	public function hookDisplayCMSDisputeInformation($params)
	{
		$cms_id = Configuration::get('BOOKING_CMS_ID');
		if ('cms' === $this->context->controller->php_self && 
			$this->context->controller->cms->id_cms == $cms_id) {
			
			// Rediriger vers le contrôleur de réservation
			Tools::redirect($this->context->link->getModuleLink('booking', 'booking'));
		}
	}
	
	/**
	 * Hook pour ajouter les médias sur les pages front
	 */
	public function hookActionFrontControllerSetMedia($params)
	{
		// Ajouter les médias sur toutes les pages pour les notifications
		$this->context->controller->registerStylesheet(
			'booking-notifications',
			'modules/'.$this->name.'/views/css/booking-notifications.css',
			['media' => 'all', 'priority' => 100]
		);
	}
	
	/**
	 * Hook pour ajouter des médias dans le header admin
	 */
	public function hookDisplayBackOfficeHeader($params)
	{		
		$this->context->controller->addJS('modules/'.$this->name.'/js/tabs.js');
		
		// Ajouter les styles admin selon le contrôleur
		$controller = Tools::getValue("controller");
		if (strpos($controller, 'AdminBooker') !== false) {
			$this->context->controller->addCSS('modules/'.$this->name.'/views/css/AdminBookerView.css');
		}
	}
	
	/**
	 * Hook header pour ajouter des variables JS globales
	 */
	public function hookDisplayHeader($params)
	{
		// Variables globales JavaScript
		Media::addJsDef([
			'booking_module_url' => $this->context->link->getModuleLink('booking', 'booking'),
			'booking_ajax_url' => $this->context->link->getModuleLink('booking', 'booking'),
		]);
	}
}
