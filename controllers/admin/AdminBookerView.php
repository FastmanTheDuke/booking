<?php
/**
 * Contrôleur administrateur pour la vue calendrier des réservations
 * Calendrier principal avec gestion des réservations et multi-sélection
 */

require_once(dirname(__FILE__) . '/../../classes/Booker.php');
require_once(dirname(__FILE__) . '/../../classes/BookerAuth.php');
require_once(dirname(__FILE__) . '/../../classes/BookerAuthReserved.php');

class AdminBookerViewController extends ModuleAdminController
{
    public function __construct()
    {		
        $this->display = 'view';
        $this->bootstrap = true;
        parent::__construct();
    }

    public function renderView()
    {
        // Charger FullCalendar depuis CDN (plus fiable)
        $this->context->controller->addJS('https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.11.5/main.min.js');
        $this->context->controller->addCSS('https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.11.5/main.min.css');
        
        // Charger les scripts locaux après FullCalendar
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/js/reservation-calendar.js');
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
        
        // Ajouter les variables JavaScript nécessaires
        Media::addJSDef(array(
            'BookingCalendar' => array(
                'config' => array(
                    'locale' => $this->context->language->iso_code,
                    'business_hours' => array(
                        'daysOfWeek' => array(1, 2, 3, 4, 5, 6), // Lundi à Samedi
                        'startTime' => '08:00',
                        'endTime' => '18:00'
                    )
                ),
                'currentDate' => date('Y-m-d'),
                'ajax_urls' => $ajax_urls,
                'bookers' => $bookers,
                'statuses' => BookerAuthReserved::getStatuses(),
                'texts' => array(
                    'loading' => $this->l('Chargement...'),
                    'no_events' => $this->l('Aucune réservation'),
                    'confirm_delete' => $this->l('Confirmer la suppression ?'),
                    'error_occurred' => $this->l('Une erreur est survenue')
                )
            )
        ));
        
        $this->context->smarty->assign([
            'bookers' => $bookers,
            'ajax_urls' => $ajax_urls,
            'current_date' => date('Y-m-d'),
            'module_dir' => _MODULE_DIR_ . $this->module->name . '/'
        ]);
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/calendar_reservations.tpl');
    }
    
    /**
     * Récupérer les bookers actifs
     */
    private function getActiveBookers()
    {
        return Db::getInstance()->executeS('
            SELECT b.id_booker, b.name
            FROM `' . _DB_PREFIX_ . 'booker` b
            WHERE b.active = 1
            ORDER BY b.name ASC
        ');
    }
    
    /**
     * Traitement AJAX pour récupérer les événements
     */
    public function ajaxProcessGetEvents()
    {
        $start = Tools::getValue('start');
        $end = Tools::getValue('end');
        $booker_id = (int)Tools::getValue('booker_id');
        
        $events = $this->getCalendarEvents($start, $end, $booker_id);
        
        header('Content-Type: application/json');
        echo json_encode($events);
        exit;
    }
    
    /**
     * Récupérer les événements pour le calendrier
     */
    private function getCalendarEvents($start, $end, $booker_id = null)
    {
        $where_conditions = array();
        $where_conditions[] = 'r.active = 1';
        $where_conditions[] = 'r.date_reserved >= "' . pSQL($start) . '"';
        $where_conditions[] = 'r.date_reserved <= "' . pSQL($end) . '"';
        
        if ($booker_id) {
            $where_conditions[] = 'r.id_booker = ' . (int)$booker_id;
        }
        
        $sql = 'SELECT r.*, b.name as booker_name
                FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` r
                LEFT JOIN `' . _DB_PREFIX_ . 'booker` b ON (r.id_booker = b.id_booker)
                WHERE ' . implode(' AND ', $where_conditions) . '
                ORDER BY r.date_reserved ASC, r.hour_from ASC';
        
        $reservations = Db::getInstance()->executeS($sql);
        $events = array();
        
        foreach ($reservations as $reservation) {
            $start_time = $reservation['date_reserved'] . 'T' . sprintf('%02d:00:00', $reservation['hour_from']);
            $end_time = $reservation['date_reserved'] . 'T' . sprintf('%02d:00:00', $reservation['hour_to']);
            
            $color = $this->getStatusColor($reservation['status']);
            
            $events[] = array(
                'id' => $reservation['id_reserved'],
                'title' => $reservation['customer_firstname'] . ' ' . $reservation['customer_lastname'],
                'start' => $start_time,
                'end' => $end_time,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => array(
                    'booker_name' => $reservation['booker_name'],
                    'customer_email' => $reservation['customer_email'],
                    'customer_phone' => $reservation['customer_phone'],
                    'status' => $reservation['status'],
                    'booking_reference' => $reservation['booking_reference'],
                    'total_price' => $reservation['total_price']
                )
            );
        }
        
        return $events;
    }
    
    /**
     * Obtenir la couleur selon le statut
     */
    private function getStatusColor($status)
    {
        switch ($status) {
            case BookerAuthReserved::STATUS_PENDING:
                return '#ffc107'; // Jaune
            case BookerAuthReserved::STATUS_ACCEPTED:
                return '#17a2b8'; // Bleu
            case BookerAuthReserved::STATUS_PAID:
                return '#28a745'; // Vert
            case BookerAuthReserved::STATUS_CANCELLED:
                return '#dc3545'; // Rouge
            case BookerAuthReserved::STATUS_EXPIRED:
                return '#6c757d'; // Gris
            default:
                return '#007bff'; // Bleu par défaut
        }
    }
}
?>