<?php
/**
 * Contrôleur administrateur pour la vue calendrier des réservations
 * Calendrier principal avec gestion des réservations et multi-sélection
 */

require_once(dirname(__FILE__) . '/../../classes/Booker.php');
require_once(dirname(__FILE__) . '/../../classes/BookerAuth.php');
require_once(dirname(__FILE__) . '/../../classes/BookerAuthReserved.php');

class AdminBookerViewController extends ModuleAdminControllerCore
{
    protected $_module = NULL;
    public $controller_type = 'admin';   

    public function __construct()
    {		
        $this->display = 'options';
        $this->displayName = 'Calendrier des Réservations';
        $this->bootstrap = true;
        parent::__construct();
    }

    public function renderOptions()
    {
        // Ajouter les ressources CSS/JS nécessaires
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/js/fullcalendar.min.js');
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/js/reservation-calendar.js');
        $this->addCSS(_MODULE_DIR_ . $this->module->name . '/css/fullcalendar.min.css');
        $this->addCSS(_MODULE_DIR_ . $this->module->name . '/css/admin-calendar.css');
        
        // Récupérer la liste des bookers pour le filtre
        $bookers = $this->getActiveBookers();
        
        // URLs AJAX pour les actions
        $ajax_urls = array(
            'get_events' => $this->context->link->getAdminLink('AdminBookerView') . '&ajax=1&action=getEvents',
            'update_reservation' => $this->context->link->getAdminLink('AdminBookerView') . '&ajax=1&action=updateReservation',
            'create_reservation' => $this->context->link->getAdminLink('AdminBookerView') . '&ajax=1&action=createReservation',
            'delete_reservation' => $this->context->link->getAdminLink('AdminBookerView') . '&ajax=1&action=deleteReservation',
            'bulk_action' => $this->context->link->getAdminLink('AdminBookerView') . '&ajax=1&action=bulkAction'
        );
        
        // Configuration du calendrier
        $calendar_config = array(
            'multi_select' => Configuration::get('BOOKING_MULTI_SELECT', 1),
            'auto_confirm' => Configuration::get('BOOKING_AUTO_CONFIRM', 0),
            'default_duration' => Configuration::get('BOOKING_DEFAULT_DURATION', 60),
            'business_hours' => array(
                'start' => '08:00',
                'end' => '19:00'
            ),
            'locale' => $this->context->language->iso_code
        );
        
        // Statistiques rapides
        $stats = $this->getCalendarStats();
        
        $this->context->smarty->assign(array(
            'bookers' => $bookers,
            'ajax_urls' => $ajax_urls,
            'calendar_config' => $calendar_config,
            'stats' => $stats,
            'token' => $this->token,
            'current_date' => date('Y-m-d'),
            'statuses' => BookerAuthReserved::getStatuses(),
            'payment_statuses' => $this->getPaymentStatuses()
        ));
        
        $this->setTemplate('booker_view.tpl');
        return '';
    }
    
    /**
     * Gestion des requêtes AJAX
     */
    public function ajaxProcess()
    {
        $action = Tools::getValue('action');
        
        switch ($action) {
            case 'getEvents':
                $this->ajaxProcessGetEvents();
                break;
                
            case 'updateReservation':
                $this->ajaxProcessUpdateReservation();
                break;
                
            case 'createReservation':
                $this->ajaxProcessCreateReservation();
                break;
                
            case 'deleteReservation':
                $this->ajaxProcessDeleteReservation();
                break;
                
            case 'bulkAction':
                $this->ajaxProcessBulkAction();
                break;
                
            default:
                $this->ajaxDie(json_encode(array(
                    'success' => false,
                    'message' => 'Action inconnue'
                )));
        }
    }
    
    /**
     * Récupérer les événements du calendrier
     */
    private function ajaxProcessGetEvents()
    {
        $start_date = Tools::getValue('start');
        $end_date = Tools::getValue('end');
        $booker_filter = Tools::getValue('booker_id');
        $status_filter = Tools::getValue('status');
        
        if (!$start_date || !$end_date) {
            $this->ajaxDie(json_encode(array()));
        }
        
        $where_conditions = array();
        $where_conditions[] = 'r.date_reserved >= "' . pSQL($start_date) . '"';
        $where_conditions[] = 'r.date_reserved <= "' . pSQL($end_date) . '"';
        $where_conditions[] = 'r.active = 1';
        
        if ($booker_filter && $booker_filter !== 'all') {
            $where_conditions[] = 'r.id_booker = ' . (int)$booker_filter;
        }
        
        if ($status_filter && $status_filter !== 'all') {
            $where_conditions[] = 'r.status = ' . (int)$status_filter;
        }
        
        $sql = 'SELECT r.*, b.name as booker_name, b.price as booker_price
                FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` r
                LEFT JOIN `' . _DB_PREFIX_ . 'booker` b ON (r.id_booker = b.id_booker)
                WHERE ' . implode(' AND ', $where_conditions) . '
                ORDER BY r.date_reserved ASC, r.hour_from ASC';
        
        $reservations = Db::getInstance()->executeS($sql);
        $events = array();
        
        foreach ($reservations as $reservation) {
            $events[] = $this->formatEventForCalendar($reservation);
        }
        
        $this->ajaxDie(json_encode($events));
    }
    
    /**
     * Formater une réservation pour le calendrier
     */
    private function formatEventForCalendar($reservation)
    {
        $start_datetime = $reservation['date_reserved'] . ' ' . sprintf('%02d:00:00', $reservation['hour_from']);
        $end_datetime = $reservation['date_reserved'] . ' ' . sprintf('%02d:00:00', $reservation['hour_to']);
        
        // Couleur selon le statut
        $color = $this->getStatusColor($reservation['status']);
        $text_color = $this->getStatusTextColor($reservation['status']);
        
        // Titre de l'événement
        $title = $reservation['customer_firstname'] . ' ' . $reservation['customer_lastname'];
        if ($reservation['booker_name']) {
            $title .= ' - ' . $reservation['booker_name'];
        }
        
        // Informations supplémentaires
        $description = array();
        if ($reservation['customer_email']) {
            $description[] = 'Email: ' . $reservation['customer_email'];
        }
        if ($reservation['customer_phone']) {
            $description[] = 'Tél: ' . $reservation['customer_phone'];
        }
        if ($reservation['total_price']) {
            $description[] = 'Prix: ' . number_format($reservation['total_price'], 2) . '€';
        }
        
        return array(
            'id' => $reservation['id_reserved'],
            'title' => $title,
            'start' => $start_datetime,
            'end' => $end_datetime,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => $text_color,
            'description' => implode('\n', $description),
            'extendedProps' => array(
                'reservation_id' => $reservation['id_reserved'],
                'booker_id' => $reservation['id_booker'],
                'booker_name' => $reservation['booker_name'],
                'customer_firstname' => $reservation['customer_firstname'],
                'customer_lastname' => $reservation['customer_lastname'],
                'customer_email' => $reservation['customer_email'],
                'customer_phone' => $reservation['customer_phone'],
                'status' => $reservation['status'],
                'payment_status' => $reservation['payment_status'],
                'total_price' => $reservation['total_price'],
                'booking_reference' => $reservation['booking_reference']
            )
        );
    }
    
    /**
     * Mettre à jour une réservation
     */
    private function ajaxProcessUpdateReservation()
    {
        $id_reserved = (int)Tools::getValue('id_reserved');
        $new_date = Tools::getValue('new_date');
        $new_hour_from = (int)Tools::getValue('new_hour_from');
        $new_hour_to = (int)Tools::getValue('new_hour_to');
        $new_status = (int)Tools::getValue('new_status');
        
        if (!$id_reserved) {
            $this->ajaxDie(json_encode(array(
                'success' => false,
                'message' => 'ID de réservation manquant'
            )));
        }
        
        $reservation = new BookerAuthReserved($id_reserved);
        
        if (!Validate::isLoadedObject($reservation)) {
            $this->ajaxDie(json_encode(array(
                'success' => false,
                'message' => 'Réservation introuvable'
            )));
        }
        
        // Vérifier les disponibilités si on change la date/heure
        if ($new_date && ($new_date !== $reservation->date_reserved || 
            $new_hour_from !== $reservation->hour_from || 
            $new_hour_to !== $reservation->hour_to)) {
            
            if (!$this->checkAvailability($reservation->id_booker, $new_date, $new_hour_from, $new_hour_to, $id_reserved)) {
                $this->ajaxDie(json_encode(array(
                    'success' => false,
                    'message' => 'Créneau non disponible'
                )));
            }
        }
        
        // Mettre à jour les champs
        if ($new_date) {
            $reservation->date_reserved = $new_date;
        }
        if ($new_hour_from) {
            $reservation->hour_from = $new_hour_from;
        }
        if ($new_hour_to) {
            $reservation->hour_to = $new_hour_to;
        }
        if ($new_status !== null) {
            $reservation->status = $new_status;
        }
        
        $success = $reservation->update();
        
        $this->ajaxDie(json_encode(array(
            'success' => $success,
            'message' => $success ? 'Réservation mise à jour' : 'Erreur lors de la mise à jour',
            'reservation' => $this->formatEventForCalendar($reservation->getFields())
        )));
    }
    
    /**
     * Créer une nouvelle réservation
     */
    private function ajaxProcessCreateReservation()
    {
        $booker_id = (int)Tools::getValue('booker_id');
        $date = Tools::getValue('date');
        $hour_from = (int)Tools::getValue('hour_from');
        $hour_to = (int)Tools::getValue('hour_to');
        $customer_firstname = Tools::getValue('customer_firstname');
        $customer_lastname = Tools::getValue('customer_lastname');
        $customer_email = Tools::getValue('customer_email');
        $customer_phone = Tools::getValue('customer_phone');
        
        // Validations
        if (!$booker_id || !$date || !$hour_from || !$hour_to || !$customer_firstname || !$customer_lastname) {
            $this->ajaxDie(json_encode(array(
                'success' => false,
                'message' => 'Champs obligatoires manquants'
            )));
        }
        
        // Vérifier la disponibilité
        if (!$this->checkAvailability($booker_id, $date, $hour_from, $hour_to)) {
            $this->ajaxDie(json_encode(array(
                'success' => false,
                'message' => 'Créneau non disponible'
            )));
        }
        
        // Créer la réservation
        $reservation = new BookerAuthReserved();
        $reservation->id_booker = $booker_id;
        $reservation->date_reserved = $date;
        $reservation->hour_from = $hour_from;
        $reservation->hour_to = $hour_to;
        $reservation->customer_firstname = $customer_firstname;
        $reservation->customer_lastname = $customer_lastname;
        $reservation->customer_email = $customer_email;
        $reservation->customer_phone = $customer_phone;
        $reservation->booking_reference = $this->generateBookingReference();
        $reservation->status = Configuration::get('BOOKING_AUTO_CONFIRM') ? 
            BookerAuthReserved::STATUS_ACCEPTED : BookerAuthReserved::STATUS_PENDING;
        $reservation->active = 1;
        
        // Prix par défaut
        $booker = new Booker($booker_id);
        if (Validate::isLoadedObject($booker) && $booker->price) {
            $reservation->total_price = $booker->price;
        } else {
            $reservation->total_price = Configuration::get('BOOKING_DEFAULT_PRICE', 50);
        }
        
        $success = $reservation->add();
        
        if ($success) {
            $this->ajaxDie(json_encode(array(
                'success' => true,
                'message' => 'Réservation créée',
                'reservation' => $this->formatEventForCalendar($reservation->getFields())
            )));
        } else {
            $this->ajaxDie(json_encode(array(
                'success' => false,
                'message' => 'Erreur lors de la création'
            )));
        }
    }
    
    /**
     * Supprimer une réservation
     */
    private function ajaxProcessDeleteReservation()
    {
        $id_reserved = (int)Tools::getValue('id_reserved');
        
        if (!$id_reserved) {
            $this->ajaxDie(json_encode(array(
                'success' => false,
                'message' => 'ID manquant'
            )));
        }
        
        $reservation = new BookerAuthReserved($id_reserved);
        
        if (!Validate::isLoadedObject($reservation)) {
            $this->ajaxDie(json_encode(array(
                'success' => false,
                'message' => 'Réservation introuvable'
            )));
        }
        
        $success = $reservation->delete();
        
        $this->ajaxDie(json_encode(array(
            'success' => $success,
            'message' => $success ? 'Réservation supprimée' : 'Erreur lors de la suppression'
        )));
    }
    
    /**
     * Actions en lot
     */
    private function ajaxProcessBulkAction()
    {
        $action = Tools::getValue('bulk_action');
        $reservation_ids = Tools::getValue('reservation_ids');
        
        if (!$action || !$reservation_ids || !is_array($reservation_ids)) {
            $this->ajaxDie(json_encode(array(
                'success' => false,
                'message' => 'Paramètres manquants'
            )));
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($reservation_ids as $id) {
            $reservation = new BookerAuthReserved((int)$id);
            
            if (!Validate::isLoadedObject($reservation)) {
                $error_count++;
                continue;
            }
            
            switch ($action) {
                case 'accept':
                    $reservation->status = BookerAuthReserved::STATUS_ACCEPTED;
                    break;
                    
                case 'refuse':
                    $reservation->status = BookerAuthReserved::STATUS_CANCELLED;
                    $reservation->cancellation_reason = 'Refusée en lot';
                    break;
                    
                case 'delete':
                    if ($reservation->delete()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                    continue 2;
                    
                default:
                    $error_count++;
                    continue 2;
            }
            
            if ($reservation->update()) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        $this->ajaxDie(json_encode(array(
            'success' => $success_count > 0,
            'message' => $success_count . ' réservation(s) traitée(s), ' . $error_count . ' erreur(s)',
            'success_count' => $success_count,
            'error_count' => $error_count
        )));
    }
    
    /**
     * Vérifier la disponibilité d'un créneau
     */
    private function checkAvailability($booker_id, $date, $hour_from, $hour_to, $exclude_id = null)
    {
        // Vérifier qu'il existe une autorisation pour ce booker à cette date
        $auth_count = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth`
            WHERE id_booker = ' . (int)$booker_id . '
            AND active = 1
            AND date_from <= "' . pSQL($date) . '"
            AND date_to >= "' . pSQL($date) . '"
        ');
        
        if (!$auth_count) {
            return false;
        }
        
        // Vérifier qu'il n'y a pas de conflit avec d'autres réservations
        $where = 'id_booker = ' . (int)$booker_id . '
                  AND date_reserved = "' . pSQL($date) . '"
                  AND active = 1
                  AND status IN (' . BookerAuthReserved::STATUS_ACCEPTED . ', ' . BookerAuthReserved::STATUS_PAID . ')
                  AND ((hour_from < ' . (int)$hour_to . ' AND hour_to > ' . (int)$hour_from . '))';
        
        if ($exclude_id) {
            $where .= ' AND id_reserved != ' . (int)$exclude_id;
        }
        
        $conflict_count = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
            WHERE ' . $where
        );
        
        return $conflict_count == 0;
    }
    
    /**
     * Générer une référence de réservation unique
     */
    private function generateBookingReference()
    {
        do {
            $reference = 'BK' . date('Y') . strtoupper(Tools::substr(md5(uniqid()), 0, 6));
        } while (Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
            WHERE booking_reference = "' . pSQL($reference) . '"
        '));
        
        return $reference;
    }
    
    /**
     * Récupérer les bookers actifs
     */
    private function getActiveBookers()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'booker`
                WHERE active = 1
                ORDER BY name ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Statistiques du calendrier
     */
    private function getCalendarStats()
    {
        return array(
            'today_reservations' => $this->getTodayReservationsCount(),
            'week_reservations' => $this->getWeekReservationsCount(),
            'pending_reservations' => $this->getPendingReservationsCount(),
            'revenue_today' => $this->getTodayRevenue(),
            'revenue_week' => $this->getWeekRevenue()
        );
    }
    
    private function getTodayReservationsCount()
    {
        return (int)Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
            WHERE active = 1 AND DATE(date_reserved) = CURDATE()
        ');
    }
    
    private function getWeekReservationsCount()
    {
        return (int)Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
            WHERE active = 1 
            AND date_reserved BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) 
            AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
        ');
    }
    
    private function getPendingReservationsCount()
    {
        return (int)Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
            WHERE active = 1 AND status = ' . BookerAuthReserved::STATUS_PENDING
        );
    }
    
    private function getTodayRevenue()
    {
        $result = Db::getInstance()->getValue('
            SELECT SUM(total_price) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
            WHERE active = 1 AND DATE(date_reserved) = CURDATE() AND status = ' . BookerAuthReserved::STATUS_PAID
        );
        
        return $result ? (float)$result : 0;
    }
    
    private function getWeekRevenue()
    {
        $result = Db::getInstance()->getValue('
            SELECT SUM(total_price) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
            WHERE active = 1 AND status = ' . BookerAuthReserved::STATUS_PAID.' 
            AND date_reserved BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) 
            AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)'
        );
        
        return $result ? (float)$result : 0;
    }
    
    /**
     * Couleurs selon le statut
     */
    private function getStatusColor($status)
    {
        switch ((int)$status) {
            case BookerAuthReserved::STATUS_PENDING:
                return '#ffc107'; // Warning
            case BookerAuthReserved::STATUS_ACCEPTED:
                return '#17a2b8'; // Info
            case BookerAuthReserved::STATUS_PAID:
                return '#28a745'; // Success
            case BookerAuthReserved::STATUS_CANCELLED:
            case BookerAuthReserved::STATUS_EXPIRED:
                return '#dc3545'; // Danger
            default:
                return '#6c757d'; // Secondary
        }
    }
    
    private function getStatusTextColor($status)
    {
        switch ((int)$status) {
            case BookerAuthReserved::STATUS_PENDING:
                return '#212529'; // Dark text for yellow background
            default:
                return '#ffffff'; // White text for other backgrounds
        }
    }
    
    /**
     * Statuts de paiement
     */
    private function getPaymentStatuses()
    {
        return array(
            BookerAuthReserved::PAYMENT_PENDING => 'En attente',
            BookerAuthReserved::PAYMENT_PARTIAL => 'Partiel',
            BookerAuthReserved::PAYMENT_COMPLETED => 'Complet',
            BookerAuthReserved::PAYMENT_REFUNDED => 'Remboursé'
        );
    }
}