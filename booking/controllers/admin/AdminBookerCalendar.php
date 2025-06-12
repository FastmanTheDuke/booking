<?php
require_once (dirname(__FILE__). '/../../classes/Booker.php');
require_once (dirname(__FILE__). '/../../classes/BookerAuth.php');
require_once (dirname(__FILE__). '/../../classes/BookerAuthReserved.php');

class AdminBookerCalendarController extends ModuleAdminControllerCore
{
    protected $_module = NULL;
    public $controller_type = 'admin';   

    public function __construct()
    {		
        $this->display = 'options';
        $this->displayName = 'Calendrier des Disponibilités';
        $this->bootstrap = true;
        parent::__construct();
    }
    
    public function renderOptions()
    {
        $this->addJS('modules/'.$this->module->name.'/js/booking-calendar.js');
        $this->addCSS('modules/'.$this->module->name.'/css/booking-calendar.css');
        
        $output = $this->renderCalendarInterface();
        return $output;
    }
    
    private function renderCalendarInterface()
    {
        // Récupérer tous les bookers actifs
        $bookers = Booker::getActiveBookers();
        
        // Récupérer les disponibilités du mois courant
        $currentMonth = date('Y-m');
        $availabilities = $this->getMonthAvailabilities($currentMonth);
        
        $this->context->smarty->assign([
            'bookers' => $bookers,
            'currentMonth' => $currentMonth,
            'availabilities' => $availabilities,
            'ajaxUrl' => $this->context->link->getAdminLink('AdminBookerCalendar')
        ]);
        
        return $this->context->smarty->fetch($this->getTemplatePath().'calendar_availability.tpl');
    }
    
    private function getMonthAvailabilities($month)
    {
        $sql = 'SELECT ba.*, b.name as booker_name
                FROM `' . _DB_PREFIX_ . 'booker_auth` ba
                LEFT JOIN `' . _DB_PREFIX_ . 'booker` b ON (ba.id_booker = b.id_booker)
                WHERE ba.active = 1
                AND (DATE_FORMAT(ba.date_from, "%Y-%m") = "' . pSQL($month) . '"
                     OR DATE_FORMAT(ba.date_to, "%Y-%m") = "' . pSQL($month) . '"
                     OR (ba.date_from <= "' . pSQL($month) . '-01" AND ba.date_to >= LAST_DAY("' . pSQL($month) . '-01")))
                ORDER BY ba.date_from ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * AJAX - Sauvegarder une nouvelle disponibilité
     */
    public function ajaxProcessSaveAvailability()
    {
        $id_booker = (int)Tools::getValue('id_booker');
        $date_from = Tools::getValue('date_from');
        $date_to = Tools::getValue('date_to');
        $time_from = Tools::getValue('time_from', '09:00');
        $time_to = Tools::getValue('time_to', '18:00');
        
        if (!$id_booker || !$date_from || !$date_to) {
            die(json_encode(['success' => false, 'error' => 'Paramètres manquants']));
        }
        
        $availability = new BookerAuth();
        $availability->id_booker = $id_booker;
        $availability->date_from = $date_from . ' ' . $time_from . ':00';
        $availability->date_to = $date_to . ' ' . $time_to . ':00';
        $availability->active = 1;
        
        if ($availability->add()) {
            die(json_encode(['success' => true, 'id' => $availability->id]));
        } else {
            die(json_encode(['success' => false, 'error' => 'Erreur lors de la sauvegarde']));
        }
    }
    
    /**
     * AJAX - Supprimer une disponibilité
     */
    public function ajaxProcessDeleteAvailability()
    {
        $id_auth = (int)Tools::getValue('id_auth');
        
        if (!$id_auth) {
            die(json_encode(['success' => false, 'error' => 'ID manquant']));
        }
        
        $availability = new BookerAuth($id_auth);
        if ($availability->delete()) {
            die(json_encode(['success' => true]));
        } else {
            die(json_encode(['success' => false, 'error' => 'Erreur lors de la suppression']));
        }
    }
    
    /**
     * AJAX - Charger les disponibilités d'un mois
     */
    public function ajaxProcessLoadMonth()
    {
        $month = Tools::getValue('month');
        if (!$month) {
            die(json_encode(['success' => false, 'error' => 'Mois manquant']));
        }
        
        $availabilities = $this->getMonthAvailabilities($month);
        die(json_encode(['success' => true, 'availabilities' => $availabilities]));
    }
    
    /**
     * AJAX - Dupliquer une disponibilité
     */
    public function ajaxProcessDuplicateAvailability()
    {
        $id_auth = (int)Tools::getValue('id_auth');
        $new_date_from = Tools::getValue('new_date_from');
        $new_date_to = Tools::getValue('new_date_to');
        
        if (!$id_auth || !$new_date_from || !$new_date_to) {
            die(json_encode(['success' => false, 'error' => 'Paramètres manquants']));
        }
        
        $original = new BookerAuth($id_auth);
        if (!Validate::isLoadedObject($original)) {
            die(json_encode(['success' => false, 'error' => 'Disponibilité introuvable']));
        }
        
        $duplicate = new BookerAuth();
        $duplicate->id_booker = $original->id_booker;
        $duplicate->date_from = $new_date_from;
        $duplicate->date_to = $new_date_to;
        $duplicate->active = 1;
        
        if ($duplicate->add()) {
            die(json_encode(['success' => true, 'id' => $duplicate->id]));
        } else {
            die(json_encode(['success' => false, 'error' => 'Erreur lors de la duplication']));
        }
    }
}