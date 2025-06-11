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
        $this->version = '0.2.0';
        $this->author = 'BBb';
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Réservations');
        $this->description = $this->l('Module de système de réservations avancé');
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
                'actionCronJob', // Pour nettoyer les réservations expirées
            ]) 
			|| !$this->installSql()
			|| !$this->installTab()
        ) {
            return false;
        }
        
        // Ajouter une tâche cron pour nettoyer les réservations expirées
        $this->addCronTask();
        
        return true;
    }
    
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        
        $this->uninstallTab();
        $this->removeCronTask();
        
        return true;
    }
    
    /**
     * Ajouter une tâche cron pour nettoyer les réservations expirées
     */
    private function addCronTask()
    {
        // Enregistrer la tâche cron dans la configuration
        Configuration::updateValue('QUIZZ_CRON_CLEAN_RESERVATIONS', 1);
    }
    
    /**
     * Supprimer la tâche cron
     */
    private function removeCronTask()
    {
        Configuration::deleteByName('QUIZZ_CRON_CLEAN_RESERVATIONS');
    }
    
    /**
     * Hook pour les tâches cron
     */
    public function hookActionCronJob($params)
    {
        if (Configuration::get('QUIZZ_CRON_CLEAN_RESERVATIONS')) {
            // Nettoyer les réservations expirées (plus de 24h)
            BookerAuthReserved::cancelExpiredReservations(24);
            
            // Log de l'action
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

	public function installSql()
    {
        try {
              
            // Création des tables du système de réservation
			require_once (dirname(__FILE__). '/sql/booker.php');
			require_once (dirname(__FILE__). '/sql/bookerauth.php');
			require_once (dirname(__FILE__). '/sql/bookerauthreserved.php');
			
        } catch (PrestaShopException $e) {
            PrestaShopLogger::addLog('Erreur installation SQL module Resa: ' . $e->getMessage());
            return false;
        }
 
        return $createTable;
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
		// Onglets Réservations
		$tab_id = Tab::getIdFromClassName('AdminBooker');	
		if ($tab_id == false) {
			$tab = new Tab();
			$tab->class_name = 'AdminBooker';
			$tab->position = 3;
			$tab->id_parent = (int)Tab::getIdFromClassName('BOOKING');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Éléments à réserver";
			}
			$tab->add();
		}
		
		$tab_id = Tab::getIdFromClassName('AdminBookerAuth');	
		if ($tab_id == false) {
			$tab = new Tab();
			$tab->class_name = 'AdminBookerAuth';
			$tab->position = 4;
			$tab->id_parent = (int)Tab::getIdFromClassName('BOOKING');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Disponibilités";
			}
			$tab->add();
		}
		
		$tab_id = Tab::getIdFromClassName('AdminBookerAuthReserved');	
		if ($tab_id == false) {
			$tab = new Tab();
			$tab->class_name = 'AdminBookerAuthReserved';
			$tab->position = 5;
			$tab->id_parent = (int)Tab::getIdFromClassName('BOOKING');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Réservations";
			}
			$tab->add();
		}
		
		$tab_id = Tab::getIdFromClassName('AdminBookerView');	
		if ($tab_id == false) {
			$tab = new Tab();
			$tab->class_name = 'AdminBookerView';
			$tab->position = 6;
			$tab->id_parent = (int)Tab::getIdFromClassName('BOOKING');
			$tab->module = $this->name;
			foreach ($languages as $language) {
				$tab->name[$language['id_lang']] = "Calendrier de réservations";
			}
			$tab->add();
		}
		
		return true;
	}
	
	private function uninstallTab()
	{
		$tabs_to_remove = [
			'BOOKING', 'AdminBooker', 
			'AdminBookerAuth', 'AdminBookerAuthReserved', 'AdminBookerView'
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
	
	private function set_token()
	{
		$this->token = md5("Petit#FastHerbier4ward%Happe|0308Smok81");
		return $this->token;
	}
	
	/**
	 * Configuration du module
	 */
	public function getContent()
    {
		$output = '';
		
		if (Tools::isSubmit('submit' . $this->name)) {
			// Traitement de la configuration
			$output .= $this->displayConfirmation($this->l('Configuration mise à jour'));
		}
		
		// Formulaire de configuration
		$output .= $this->displayForm();
		
		// Statistiques des réservations
		$output .= $this->displayReservationStats();
		
        return $output;
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
		
		$this->context->smarty->assign('reservation_stats', $stats);
		
		return '<div class="panel">
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
			
			$output .= '<div class="col-lg-3 col-md-6">
				<div class="alert ' . $alert_class . '">
					<div class="text-center">
						<div style="font-size: 2em; font-weight: bold;">' . $stat['count'] . '</div>
						<div>' . $stat['label'] . '</div>
					</div>
				</div>
			</div>';
		}
		
		return $output . '</div></div></div>';
    }
    
    public function displayForm()
    {
		$form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Configuration'),
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Nettoyage automatique des réservations expirées'),
						'name' => 'QUIZZ_CRON_CLEAN_RESERVATIONS',
						'desc' => $this->l('Active le nettoyage automatique des demandes de réservation de plus de 24h'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Oui')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Non')
							)
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

		$helper->fields_value['QUIZZ_CRON_CLEAN_RESERVATIONS'] = Configuration::get('QUIZZ_CRON_CLEAN_RESERVATIONS');

		return $helper->generateForm(array($form));
    }
	
	// ... (le reste des méthodes du quiz restent inchangées)
	public function hookDisplayCMSDisputeInformation(){
		//avant le chargement du contenu - body content	
		if ('cms' === $this->context->controller->php_self && $this->context->controller->cms->id_cms==6) {
			if (Tools::isSubmit('results')) {
				$result_quizz = $this->getResults();
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
	
	// Méthodes du quiz (getResults, getQuestions, etc.) restent identiques...
	// [Le code existant pour le quiz serait ici]
	
	public function hookActionFrontControllerSetMedia($params)
	{
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
	
	/**
	 * Obtenir les réservations en conflit
	 */
	public function getConflictingReservations($id_booker, $date_reserved, $hour_from, $hour_to, $exclude_id = null)
	{
		$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
				WHERE `id_booker` = ' . (int)$id_booker . '
				AND `date_reserved` = "' . pSQL($date_reserved) . '"
				AND `status` IN (' . BookerAuthReserved::STATUS_ACCEPTED . ', ' . BookerAuthReserved::STATUS_PAID . ')
				AND `active` = 1
				AND `hour_from` < ' . (int)$hour_to . '
				AND `hour_to` > ' . (int)$hour_from;
		
		if ($exclude_id) {
			$sql .= ' AND `id_reserved` != ' . (int)$exclude_id;
		}
		
		return Db::getInstance()->executeS($sql);
	}
}