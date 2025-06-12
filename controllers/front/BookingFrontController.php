<?php

require_once (dirname(__FILE__). '/../classes/Booker.php');
require_once (dirname(__FILE__). '/../classes/BookerAuth.php');
require_once (dirname(__FILE__). '/../classes/BookerAuthReserved.php');
require_once (dirname(__FILE__). '/../classes/BookingProductIntegration.php');

class BookingModuleFrontController extends ModuleFrontController  {
    
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;
    
    public function initContent(){
        parent::initContent();
        
        // Traitement des actions AJAX
        if (Tools::isSubmit('ajax')) {
            $this->processAjax();
            return;
        }
        
        // Traitement du formulaire de réservation
        if (Tools::isSubmit('submitReservation')) {
            $this->processReservation();
        }
        
        // Récupérer les données pour l'affichage
        $bookers = $this->getAvailableBookers();
        $selected_booker = Tools::getValue('booker_id');
        $selected_date = Tools::getValue('date');
        
        $this->context->smarty->assign([
            'bookers' => $bookers,
            'selected_booker' => $selected_booker,
            'selected_date' => $selected_date,
            'ajax_url' => $this->context->link->getModuleLink('booking', 'booking'),
            'current_date' => date('Y-m-d'),
            'min_date' => date('Y-m-d'),
            'max_date' => date('Y-m-d', strtotime('+3 months'))
        ]);
        
        $this->setTemplate('module:booking/views/templates/front/booking.tpl');
    }
    
    public function setMedia()
    {
        parent::setMedia();
        
        $this->registerStylesheet(
            'module-booking-style',
            'modules/'.$this->module->name.'/views/css/booking-front.css',
            [
              'media' => 'all',
              'priority' => 200,
            ]
        );

        $this->registerJavascript(
            'module-booking-script',
            'modules/'.$this->module->name.'/views/js/booking-front.js',
            [
              'priority' => 200,
            ]
        );
    }
    
    /**
     * Traitement des requêtes AJAX
     */
    private function processAjax()
    {
        $action = Tools::getValue('action');
        
        switch ($action) {
            case 'getAvailableSlots':
                $this->getAvailableSlots();
                break;
            case 'checkAvailability':
                $this->checkAvailability();
                break;
            case 'createReservation':
                $this->createReservation();
                break;
            case 'getBookerInfo':
                $this->getBookerInfo();
                break;
            default:
                $this->ajaxResponse(['success' => false, 'error' => 'Action non reconnue']);
        }
    }
    
    /**
     * Obtenir les bookers disponibles
     */
    private function getAvailableBookers()
    {
        $bookers = Booker::getActiveBookers($this->context->language->id);
        
        // Ajouter des informations supplémentaires pour chaque booker
        foreach ($bookers as &$booker) {
            // Calculer le prix de base
            $booker['base_price'] = $this->getBookerBasePrice($booker['id_booker']);
            
            // Vérifier s'il y a des disponibilités dans les 3 prochains mois
            $booker['has_availability'] = $this->hasUpcomingAvailability($booker['id_booker']);
            
            // URL de l'image (à adapter selon votre structure)
            $booker['image_url'] = $this->getBookerImageUrl($booker['id_booker']);
        }
        
        return $bookers;
    }
    
    /**
     * AJAX: Obtenir les créneaux disponibles pour une date donnée
     */
    private function getAvailableSlots()
    {
        $booker_id = (int)Tools::getValue('booker_id');
        $date = Tools::getValue('date');
        
        if (!$booker_id || !$date) {
            $this->ajaxResponse(['success' => false, 'error' => 'Paramètres manquants']);
            return;
        }
        
        // Valider la date
        if (!$this->isValidDate($date)) {
            $this->ajaxResponse(['success' => false, 'error' => 'Date invalide']);
            return;
        }
        
        // Vérifier que la date n'est pas dans le passé
        if (strtotime($date) < strtotime('today')) {
            $this->ajaxResponse(['success' => false, 'error' => 'Impossible de réserver dans le passé']);
            return;
        }
        
        // Vérifier qu'il y a une disponibilité pour ce booker à cette date
        if (!$this->hasAvailabilityForDate($booker_id, $date)) {
            $this->ajaxResponse(['success' => false, 'error' => 'Aucune disponibilité pour cette date']);
            return;
        }
        
        // Obtenir les créneaux disponibles
        $slots = BookerAuthReserved::getAvailableSlots($booker_id, $date);
        
        // Formater les créneaux pour l'affichage
        $formatted_slots = [];
        foreach ($slots as $slot) {
            $formatted_slots[] = [
                'hour_from' => $slot['hour_from'],
                'hour_to' => $slot['hour_to'],
                'label' => sprintf('%02d:00 - %02d:00', $slot['hour_from'], $slot['hour_to']),
                'duration' => $slot['hour_to'] - $slot['hour_from'],
                'price' => $this->calculateSlotPrice($booker_id, $slot['hour_from'], $slot['hour_to'])
            ];
        }
        
        $this->ajaxResponse([
            'success' => true,
            'slots' => $formatted_slots,
            'date' => $date,
            'booker_id' => $booker_id
        ]);
    }
    
    /**
     * AJAX: Vérifier la disponibilité d'un créneau spécifique
     */
    private function checkAvailability()
    {
        $booker_id = (int)Tools::getValue('booker_id');
        $date = Tools::getValue('date');
        $hour_from = (int)Tools::getValue('hour_from');
        $hour_to = (int)Tools::getValue('hour_to');
        
        if (!$booker_id || !$date || !$hour_from || !$hour_to) {
            $this->ajaxResponse(['success' => false, 'error' => 'Paramètres manquants']);
            return;
        }
        
        // Vérifications de base
        if ($hour_from >= $hour_to) {
            $this->ajaxResponse(['success' => false, 'error' => 'Créneau horaire invalide']);
            return;
        }
        
        // Vérifier la disponibilité
        $available = $this->isSlotAvailable($booker_id, $date, $hour_from, $hour_to);
        
        if ($available) {
            $price = $this->calculateSlotPrice($booker_id, $hour_from, $hour_to);
            $this->ajaxResponse([
                'success' => true,
                'available' => true,
                'price' => $price,
                'formatted_price' => Tools::displayPrice($price)
            ]);
        } else {
            $this->ajaxResponse([
                'success' => true,
                'available' => false,
                'message' => 'Ce créneau n\'est plus disponible'
            ]);
        }
    }
    
    /**
     * AJAX: Créer une réservation
     */
    private function createReservation()
    {
        $booker_id = (int)Tools::getValue('booker_id');
        $date = Tools::getValue('date');
        $hour_from = (int)Tools::getValue('hour_from');
        $hour_to = (int)Tools::getValue('hour_to');
        $customer_name = Tools::getValue('customer_name');
        $customer_email = Tools::getValue('customer_email');
        $customer_phone = Tools::getValue('customer_phone');
        $notes = Tools::getValue('notes', '');
        
        // Validation des données
        $validation_error = $this->validateReservationData([
            'booker_id' => $booker_id,
            'date' => $date,
            'hour_from' => $hour_from,
            'hour_to' => $hour_to,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone
        ]);
        
        if ($validation_error) {
            $this->ajaxResponse(['success' => false, 'error' => $validation_error]);
            return;
        }
        
        // Vérifier une dernière fois la disponibilité
        if (!$this->isSlotAvailable($booker_id, $date, $hour_from, $hour_to)) {
            $this->ajaxResponse(['success' => false, 'error' => 'Ce créneau n\'est plus disponible']);
            return;
        }
        
        // Créer la réservation
        $reservation = new BookerAuthReserved();
        $reservation->id_booker = $booker_id;
        $reservation->date_reserved = $date;
        $reservation->hour_from = $hour_from;
        $reservation->hour_to = $hour_to;
        $reservation->status = BookerAuthReserved::STATUS_PENDING;
        $reservation->active = 1;
        $reservation->date_add = date('Y-m-d H:i:s');
        $reservation->date_upd = date('Y-m-d H:i:s');
        
        if ($reservation->add()) {
            // Sauvegarder les informations client
            $reservation->saveCustomerInfo($customer_name, $customer_email, $customer_phone, $notes);
            
            // Envoyer un email de confirmation
            $this->sendReservationConfirmationEmail($reservation);
            
            $this->ajaxResponse([
                'success' => true,
                'reservation_id' => $reservation->id,
                'message' => 'Votre demande de réservation a été enregistrée. Vous recevrez une confirmation par email.',
                'status' => 'pending'
            ]);
        } else {
            $this->ajaxResponse(['success' => false, 'error' => 'Erreur lors de l\'enregistrement de la réservation']);
        }
    }
    
    /**
     * AJAX: Obtenir les informations d'un booker
     */
    private function getBookerInfo()
    {
        $booker_id = (int)Tools::getValue('booker_id');
        
        if (!$booker_id) {
            $this->ajaxResponse(['success' => false, 'error' => 'ID booker manquant']);
            return;
        }
        
        $booker = new Booker($booker_id);
        if (!Validate::isLoadedObject($booker)) {
            $this->ajaxResponse(['success' => false, 'error' => 'Booker introuvable']);
            return;
        }
        
        $info = [
            'id' => $booker->id,
            'name' => $booker->name,
            'description' => $booker->description[$this->context->language->id] ?? '',
            'base_price' => $this->getBookerBasePrice($booker->id),
            'image_url' => $this->getBookerImageUrl($booker->id),
            'available_days' => $this->getBookerAvailableDays($booker->id)
        ];
        
        $this->ajaxResponse(['success' => true, 'booker' => $info]);
    }
    
    /**
     * Valider les données de réservation
     */
    private function validateReservationData($data)
    {
        if (empty($data['booker_id'])) {
            return 'Veuillez sélectionner un élément à réserver';
        }
        
        if (empty($data['date']) || !$this->isValidDate($data['date'])) {
            return 'Date invalide';
        }
        
        if (strtotime($data['date']) < strtotime('today')) {
            return 'Impossible de réserver dans le passé';
        }
        
        if ($data['hour_from'] >= $data['hour_to']) {
            return 'Créneau horaire invalide';
        }
        
        if (empty($data['customer_name'])) {
            return 'Le nom est obligatoire';
        }
        
        if (empty($data['customer_email']) || !Validate::isEmail($data['customer_email'])) {
            return 'Email invalide';
        }
        
        if (!empty($data['customer_phone']) && !Validate::isPhoneNumber($data['customer_phone'])) {
            return 'Numéro de téléphone invalide';
        }
        
        return null; // Pas d'erreur
    }
    
    /**
     * Vérifier si un créneau est disponible
     */
    private function isSlotAvailable($booker_id, $date, $hour_from, $hour_to)
    {
        // 1. Vérifier qu'il y a une disponibilité pour ce booker à cette date
        if (!$this->hasAvailabilityForDate($booker_id, $date)) {
            return false;
        }
        
        // 2. Vérifier qu'il n'y a pas de conflit avec d'autres réservations
        $conflict_count = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth_reserved`
            WHERE id_booker = ' . (int)$booker_id . '
            AND date_reserved = "' . pSQL($date) . '"
            AND status IN (' . BookerAuthReserved::STATUS_ACCEPTED . ', ' . BookerAuthReserved::STATUS_PAID . ', ' . BookerAuthReserved::STATUS_PENDING . ')
            AND active = 1
            AND hour_from < ' . (int)$hour_to . '
            AND hour_to > ' . (int)$hour_from
        );
        
        return !$conflict_count;
    }
    
    /**
     * Vérifier s'il y a une disponibilité pour une date donnée
     */
    private function hasAvailabilityForDate($booker_id, $date)
    {
        $count = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth`
            WHERE id_booker = ' . (int)$booker_id . '
            AND active = 1
            AND DATE(date_from) <= "' . pSQL($date) . '"
            AND DATE(date_to) >= "' . pSQL($date) . '"
        ');
        
        return (bool)$count;
    }
    
    /**
     * Vérifier s'il y a des disponibilités à venir
     */
    private function hasUpcomingAvailability($booker_id)
    {
        $count = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'booker_auth`
            WHERE id_booker = ' . (int)$booker_id . '
            AND active = 1
            AND date_to >= CURDATE()
        ');
        
        return (bool)$count;
    }
    
    /**
     * Calculer le prix d'un créneau
     */
    private function calculateSlotPrice($booker_id, $hour_from, $hour_to)
    {
        $duration = $hour_to - $hour_from;
        $base_price = $this->getBookerBasePrice($booker_id);
        
        // Logique de tarification (à adapter selon vos besoins)
        $hourly_rate = $base_price / 4; // Si base_price est pour 4h par exemple
        $total_price = $duration * $hourly_rate;
        
        // Appliquer des modificateurs selon l'heure, le jour, etc.
        $total_price = $this->applyPriceModifiers($total_price, $hour_from, $hour_to);
        
        return round($total_price, 2);
    }
    
    /**
     * Appliquer des modificateurs de prix
     */
    private function applyPriceModifiers($base_price, $hour_from, $hour_to)
    {
        // Exemple : majoration pour les créneaux de soirée
        if ($hour_from >= 18) {
            $base_price *= 1.2; // +20% pour les soirées
        }
        
        // Exemple : réduction pour les longues durées
        $duration = $hour_to - $hour_from;
        if ($duration >= 8) {
            $base_price *= 0.9; // -10% pour journée complète
        }
        
        return $base_price;
    }
    
    /**
     * Obtenir le prix de base d'un booker
     */
    private function getBookerBasePrice($booker_id)
    {
        // Pour l'instant, prix fixe. Vous pouvez stocker cela en base de données
        return 50.00; // Prix de base
    }
    
    /**
     * Obtenir l'URL de l'image d'un booker
     */
    private function getBookerImageUrl($booker_id)
    {
        // Logique pour obtenir l'image du booker
        // Vous pouvez stocker les images dans un dossier spécifique
        $image_path = _PS_MODULE_DIR_ . 'booking/views/img/bookers/' . $booker_id . '.jpg';
        
        if (file_exists($image_path)) {
            return $this->context->link->getMediaLink(__PS_BASE_URI__ . 'modules/booking/views/img/bookers/' . $booker_id . '.jpg');
        }
        
        // Image par défaut
        return $this->context->link->getMediaLink(__PS_BASE_URI__ . 'modules/booking/views/img/default-booker.jpg');
    }
    
    /**
     * Obtenir les jours disponibles pour un booker
     */
    private function getBookerAvailableDays($booker_id)
    {
        $availabilities = Db::getInstance()->executeS('
            SELECT DATE(date_from) as date_from, DATE(date_to) as date_to
            FROM `' . _DB_PREFIX_ . 'booker_auth`
            WHERE id_booker = ' . (int)$booker_id . '
            AND active = 1
            AND date_to >= CURDATE()
            ORDER BY date_from ASC
        ');
        
        $available_dates = [];
        foreach ($availabilities as $availability) {
            $start = new DateTime($availability['date_from']);
            $end = new DateTime($availability['date_to']);
            
            for ($date = clone $start; $date <= $end; $date->add(new DateInterval('P1D'))) {
                $available_dates[] = $date->format('Y-m-d');
            }
        }
        
        return array_unique($available_dates);
    }
    
    /**
     * Envoyer un email de confirmation de réservation
     */
    private function sendReservationConfirmationEmail($reservation)
    {
        $customer_info = $reservation->getCustomerInfo();
        if (!$customer_info || !$customer_info['customer_email']) {
            return false;
        }
        
        $booker = new Booker($reservation->id_booker);
        
        $template_vars = [
            'reservation_id' => $reservation->id,
            'booker_name' => $booker->name,
            'date' => $reservation->date_reserved,
            'time_from' => sprintf('%02d:00', $reservation->hour_from),
            'time_to' => sprintf('%02d:00', $reservation->hour_to),
            'customer_name' => $customer_info['customer_name'],
            'status' => 'En attente de validation'
        ];
        
        try {
            Mail::Send(
                $this->context->language->id,
                'booking_confirmation',
                'Confirmation de votre demande de réservation',
                $template_vars,
                $customer_info['customer_email'],
                $customer_info['customer_name'],
                Configuration::get('PS_SHOP_EMAIL'),
                Configuration::get('PS_SHOP_NAME'),
                null,
                null,
                _PS_MODULE_DIR_ . 'booking/mails/'
            );
            
            return true;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Erreur envoi email réservation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Traitement du formulaire (non-AJAX)
     */
    private function processReservation()
    {
        // Logique similaire à createReservation() mais avec redirection
        // À implémenter si vous voulez supporter les formulaires non-AJAX
    }
    
    /**
     * Vérifier si une date est valide
     */
    private function isValidDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Réponse AJAX standardisée
     */
    private function ajaxResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}