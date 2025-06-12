<?php 
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__). '/../../classes/Booker.php');
require_once (dirname(__FILE__). '/../../classes/BookerAuth.php');
require_once (dirname(__FILE__). '/../../classes/BookerAuthReserved.php');

class BookingBookingModuleFrontController extends ModuleFrontController  {
    
    public function initContent(){
        parent::initContent();
        
        // Récupérer les bookers disponibles
        $bookers = Booker::getActiveBookers($this->context->language->id);
        
        // Récupérer l'ID du booker sélectionné
        $selected_booker = Tools::getValue('id_booker');
        
        $this->context->smarty->assign([
            'bookers' => $bookers,
            'selected_booker' => $selected_booker,
            'ajax_url' => $this->context->link->getModuleLink('booking', 'booking'),
            'current_date' => date('Y-m-d'),
            'customer_logged' => $this->context->customer->isLogged(),
            'customer_info' => $this->getCustomerInfo()
        ]);
        
        $this->setTemplate('module:booking/views/templates/front/booking.tpl');
    }
    
    public function setMedia()
    {
        parent::setMedia();
        
        $this->registerStylesheet(
            'booking-front-style',
            'modules/'.$this->module->name.'/views/css/booking-front.css',
            [
                'media' => 'all',
                'priority' => 200,
            ]
        );

        $this->registerJavascript(
            'booking-front-script',
            'modules/'.$this->module->name.'/views/js/booking-front.js',
            [
                'priority' => 200,
                'attribute' => 'defer',
            ]
        );
        
        // Variables JavaScript
        Media::addJsDef([
            'bookingAjaxUrl' => $this->context->link->getModuleLink('booking', 'booking'),
            'bookingCurrentLang' => $this->context->language->id,
            'bookingCurrency' => $this->context->currency->sign,
        ]);
    }
    
    /**
     * Traitement AJAX pour récupérer les données du calendrier
     */
    public function displayAjaxGetCalendarData()
    {
        $month = Tools::getValue('month');
        $id_booker = (int)Tools::getValue('id_booker');
        
        if (!$month || !$id_booker) {
            die(json_encode(['success' => false, 'error' => 'Paramètres manquants']));
        }
        
        try {
            $availabilities = $this->getMonthAvailabilities($id_booker, $month);
            $reservations = $this->getMonthReservations($id_booker, $month);
            
            $calendarData = $this->processCalendarData($availabilities, $reservations, $month);
            
            die(json_encode([
                'success' => true,
                'data' => [
                    'availabilities' => $calendarData,
                    'timeSlots' => []
                ]
            ]));
            
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
    
    /**
     * Traitement AJAX pour récupérer les créneaux horaires
     */
    public function displayAjaxGetTimeSlots()
    {
        $dates = Tools::getValue('dates');
        $id_booker = (int)Tools::getValue('id_booker');
        
        if (!$dates || !$id_booker || !is_array($dates)) {
            die(json_encode(['success' => false, 'error' => 'Paramètres manquants']));
        }
        
        try {
            $timeSlots = [];
            
            foreach ($dates as $date) {
                $daySlots = $this->getDayTimeSlots($id_booker, $date);
                $timeSlots = array_merge($timeSlots, $daySlots);
            }
            
            die(json_encode([
                'success' => true,
                'timeSlots' => $timeSlots
            ]));
            
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
    
    /**
     * Traitement AJAX pour créer une réservation
     */
    public function displayAjaxCreateBooking()
    {
        $id_booker = (int)Tools::getValue('id_booker');
        $time_slots = Tools::getValue('time_slots');
        $customer = Tools::getValue('customer');
        
        if (!$id_booker || !$time_slots || !$customer || !is_array($time_slots)) {
            die(json_encode(['success' => false, 'error' => 'Paramètres manquants']));
        }
        
        try {
            // Valider les données client
            if (!$this->validateCustomerData($customer)) {
                die(json_encode(['success' => false, 'error' => 'Données client invalides']));
            }
            
            // Vérifier la disponibilité des créneaux
            if (!$this->validateTimeSlots($id_booker, $time_slots)) {
                die(json_encode(['success' => false, 'error' => 'Certains créneaux ne sont plus disponibles']));
            }
            
            // Créer les réservations
            $booking_reference = $this->generateBookingReference();
            $total_price = 0;
            $reservations = [];
            
            foreach ($time_slots as $slot) {
                $reservation = new BookerAuthReserved();
                $reservation->id_booker = $id_booker;
                $reservation->date_reserved = $slot['date'];
                $reservation->hour_from = (int)$slot['hour_from'];
                $reservation->hour_to = (int)$slot['hour_to'];
                $reservation->status = BookerAuthReserved::STATUS_PENDING;
                $reservation->active = 1;
                $reservation->booking_reference = $booking_reference;
                $reservation->customer_firstname = pSQL($customer['firstname']);
                $reservation->customer_lastname = pSQL($customer['lastname']);
                $reservation->customer_email = pSQL($customer['email']);
                $reservation->customer_phone = pSQL($customer['phone']);
                $reservation->customer_message = pSQL($customer['message']);
                
                if ($reservation->add()) {
                    $reservations[] = $reservation;
                    $total_price += $this->getSlotPrice($id_booker, $slot);
                }
            }
            
            if (empty($reservations)) {
                die(json_encode(['success' => false, 'error' => 'Erreur lors de la création des réservations']));
            }
            
            // Envoyer l'email de confirmation
            $this->sendBookingConfirmationEmail($reservations[0], $reservations, $total_price);
            
            // Notifier l'admin
            $this->sendAdminNotificationEmail($reservations[0], $reservations, $total_price);
            
            die(json_encode([
                'success' => true,
                'booking_reference' => $booking_reference,
                'total_price' => $total_price,
                'payment_url' => null // À implémenter avec Stripe
            ]));
            
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
    
    /**
     * Récupérer les disponibilités d'un mois
     */
    private function getMonthAvailabilities($id_booker, $month)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'booker_auth` 
                WHERE id_booker = ' . (int)$id_booker . '
                AND active = 1
                AND (DATE_FORMAT(date_from, "%Y-%m") = "' . pSQL($month) . '"
                     OR DATE_FORMAT(date_to, "%Y-%m") = "' . pSQL($month) . '"
                     OR (date_from <= "' . pSQL($month) . '-01" AND date_to >= LAST_DAY("' . pSQL($month) . '-01")))
                ORDER BY date_from ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Récupérer les réservations d'un mois
     */
    private function getMonthReservations($id_booker, $month)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
                WHERE id_booker = ' . (int)$id_booker . '
                AND active = 1
                AND status IN (' . BookerAuthReserved::STATUS_ACCEPTED . ', ' . BookerAuthReserved::STATUS_PAID . ')
                AND DATE_FORMAT(date_reserved, "%Y-%m") = "' . pSQL($month) . '"
                ORDER BY date_reserved ASC, hour_from ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Traiter les données du calendrier
     */
    private function processCalendarData($availabilities, $reservations, $month)
    {
        $calendarData = [];
        
        // Créer un tableau des réservations par date
        $reservationsByDate = [];
        foreach ($reservations as $reservation) {
            $date = $reservation['date_reserved'];
            if (!isset($reservationsByDate[$date])) {
                $reservationsByDate[$date] = [];
            }
            $reservationsByDate[$date][] = $reservation;
        }
        
        // Traiter chaque jour du mois
        $firstDay = new DateTime($month . '-01');
        $lastDay = clone $firstDay;
        $lastDay->modify('last day of this month');
        
        $currentDay = clone $firstDay;
        while ($currentDay <= $lastDay) {
            $dateStr = $currentDay->format('Y-m-d');
            
            $isAvailable = $this->isDayAvailable($dateStr, $availabilities);
            $dayReservations = isset($reservationsByDate[$dateStr]) ? $reservationsByDate[$dateStr] : [];
            
            $calendarData[$dateStr] = [
                'available' => $isAvailable,
                'hasReservations' => !empty($dayReservations),
                'slotsCount' => $isAvailable ? $this->getAvailableSlotsCount($dateStr, $dayReservations) : 0
            ];
            
            $currentDay->modify('+1 day');
        }
        
        return $calendarData;
    }
    
    /**
     * Vérifier si un jour est disponible
     */
    private function isDayAvailable($date, $availabilities)
    {
        foreach ($availabilities as $availability) {
            if ($date >= $availability['date_from'] && $date <= $availability['date_to']) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Obtenir le nombre de créneaux disponibles pour un jour
     */
    private function getAvailableSlotsCount($date, $reservations)
    {
        // Calculer les heures disponibles (exemple: 9h-18h = 9 créneaux d'1h)
        $totalSlots = 9; // À adapter selon votre logique
        $reservedSlots = count($reservations);
        
        return max(0, $totalSlots - $reservedSlots);
    }
    
    /**
     * Récupérer les créneaux horaires d'un jour
     */
    private function getDayTimeSlots($id_booker, $date)
    {
        // Vérifier que le jour est disponible
        $availabilities = $this->getMonthAvailabilities($id_booker, substr($date, 0, 7));
        if (!$this->isDayAvailable($date, $availabilities)) {
            return [];
        }
        
        // Récupérer les réservations existantes
        $reservations = Db::getInstance()->executeS('
            SELECT hour_from, hour_to FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
            WHERE id_booker = ' . (int)$id_booker . '
            AND date_reserved = "' . pSQL($date) . '"
            AND status IN (' . BookerAuthReserved::STATUS_ACCEPTED . ', ' . BookerAuthReserved::STATUS_PAID . ')
            AND active = 1
            ORDER BY hour_from ASC
        ');
        
        $reservedSlots = [];
        foreach ($reservations as $reservation) {
            for ($h = $reservation['hour_from']; $h < $reservation['hour_to']; $h++) {
                $reservedSlots[] = $h;
            }
        }
        
        // Générer les créneaux disponibles (9h-18h par exemple)
        $slots = [];
        for ($hour = 9; $hour < 18; $hour++) {
            $slots[] = [
                'date' => $date,
                'hour_from' => $hour,
                'hour_to' => $hour + 1,
                'available' => !in_array($hour, $reservedSlots),
                'reserved' => in_array($hour, $reservedSlots),
                'price' => $this->getSlotPrice($id_booker, ['hour_from' => $hour, 'hour_to' => $hour + 1])
            ];
        }
        
        return $slots;
    }
    
    /**
     * Valider les données client
     */
    private function validateCustomerData($customer)
    {
        if (empty($customer['firstname']) || empty($customer['lastname']) || empty($customer['email'])) {
            return false;
        }
        
        if (!Validate::isEmail($customer['email'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valider les créneaux horaires
     */
    private function validateTimeSlots($id_booker, $time_slots)
    {
        foreach ($time_slots as $slot) {
            $conflicts = Db::getInstance()->getValue('
                SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved` 
                WHERE id_booker = ' . (int)$id_booker . '
                AND date_reserved = "' . pSQL($slot['date']) . '"
                AND hour_from < ' . (int)$slot['hour_to'] . '
                AND hour_to > ' . (int)$slot['hour_from'] . '
                AND status IN (' . BookerAuthReserved::STATUS_ACCEPTED . ', ' . BookerAuthReserved::STATUS_PAID . ')
                AND active = 1
            ');
            
            if ($conflicts > 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Générer une référence de réservation
     */
    private function generateBookingReference()
    {
        return 'BK' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * Obtenir le prix d'un créneau
     */
    private function getSlotPrice($id_booker, $slot)
    {
        // À implémenter selon votre logique de prix
        // Peut dépendre du booker, de l'heure, de la date, etc.
        return 50; // Prix par défaut
    }
    
    /**
     * Obtenir les informations du client connecté
     */
    private function getCustomerInfo()
    {
        if (!$this->context->customer->isLogged()) {
            return null;
        }
        
        return [
            'firstname' => $this->context->customer->firstname,
            'lastname' => $this->context->customer->lastname,
            'email' => $this->context->customer->email
        ];
    }
    
    /**
     * Envoyer l'email de confirmation de réservation
     */
    private function sendBookingConfirmationEmail($mainReservation, $allReservations, $totalPrice)
    {
        $booker = new Booker($mainReservation->id_booker, $this->context->language->id);
        
        $templateVars = [
            'firstname' => $mainReservation->customer_firstname,
            'lastname' => $mainReservation->customer_lastname,
            'customer_email' => $mainReservation->customer_email,
            'customer_phone' => $mainReservation->customer_phone,
            'customer_message' => $mainReservation->customer_message,
            'booking_reference' => $mainReservation->booking_reference,
            'booker_name' => $booker->name,
            'booking_dates' => $this->formatBookingDates($allReservations),
            'time_slots' => $this->formatTimeSlots($allReservations),
            'total_price' => number_format($totalPrice, 2),
            'tracking_url' => $this->context->link->getPageLink('history'),
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'shop_email' => Configuration::get('PS_SHOP_EMAIL'),
            'shop_phone' => Configuration::get('PS_SHOP_PHONE'),
            'shop_address' => Configuration::get('PS_SHOP_ADDR1') . ' ' . Configuration::get('PS_SHOP_CITY'),
            'shop_url' => $this->context->shop->getBaseURL(true)
        ];
        
        return Mail::Send(
            $this->context->language->id,
            'booking_confirmation',
            'Confirmation de votre demande de réservation',
            $templateVars,
            $mainReservation->customer_email,
            $mainReservation->customer_firstname . ' ' . $mainReservation->customer_lastname,
            Configuration::get('PS_SHOP_EMAIL'),
            Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            dirname(__FILE__) . '/../../mails/'
        );
    }
    
    /**
     * Envoyer une notification à l'admin
     */
    private function sendAdminNotificationEmail($mainReservation, $allReservations, $totalPrice)
    {
        $booker = new Booker($mainReservation->id_booker, $this->context->language->id);
        
        $templateVars = [
            'customer_name' => $mainReservation->customer_firstname . ' ' . $mainReservation->customer_lastname,
            'customer_email' => $mainReservation->customer_email,
            'customer_phone' => $mainReservation->customer_phone,
            'customer_message' => $mainReservation->customer_message,
            'booking_reference' => $mainReservation->booking_reference,
            'booker_name' => $booker->name,
            'booking_dates' => $this->formatBookingDates($allReservations),
            'time_slots' => $this->formatTimeSlots($allReservations),
            'total_price' => number_format($totalPrice, 2),
            'admin_url' => $this->context->link->getAdminLink('AdminBookerAuthReserved')
        ];
        
        return Mail::Send(
            $this->context->language->id,
            'booking_admin_notification',
            'Nouvelle demande de réservation - ' . $mainReservation->booking_reference,
            $templateVars,
            Configuration::get('PS_SHOP_EMAIL'),
            Configuration::get('PS_SHOP_NAME'),
            Configuration::get('PS_SHOP_EMAIL'),
            Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            dirname(__FILE__) . '/../../mails/'
        );
    }
    
    /**
     * Formater les dates de réservation
     */
    private function formatBookingDates($reservations)
    {
        $dates = [];
        foreach ($reservations as $reservation) {
            $date = new DateTime($reservation->date_reserved);
            $dates[] = $date->format('d/m/Y');
        }
        
        return implode(', ', array_unique($dates));
    }
    
    /**
     * Formater les créneaux horaires
     */
    private function formatTimeSlots($reservations)
    {
        $slots = [];
        foreach ($reservations as $reservation) {
            $date = new DateTime($reservation->date_reserved);
            $slots[] = $date->format('d/m/Y') . ' de ' . 
                      str_pad($reservation->hour_from, 2, '0', STR_PAD_LEFT) . 'h à ' . 
                      str_pad($reservation->hour_to, 2, '0', STR_PAD_LEFT) . 'h';
        }
        
        return implode(', ', $slots);
    }
}