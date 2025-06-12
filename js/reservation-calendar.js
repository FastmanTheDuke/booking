/**
 * Gestionnaire du calendrier des réservations
 * Gestion complète des événements, multi-sélection et actions en lot
 */

document.addEventListener('DOMContentLoaded', function() {
    
    let calendar;
    let currentModal = null;
    let pendingAction = null;
    
    // Initialisation du calendrier
    initializeCalendar();
    
    // Gestionnaires d'événements
    setupEventHandlers();
    
    /**
     * Initialisation du calendrier FullCalendar
     */
    function initializeCalendar() {
        const calendarEl = document.getElementById('calendar');
        
        if (!calendarEl) {
            console.error('Élément calendrier non trouvé');
            return;
        }
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: BookingCalendar.config.locale || 'fr',
            initialView: 'timeGridWeek',
            initialDate: BookingCalendar.currentDate,
            
            headerToolbar: {
                left: '',
                center: 'title',
                right: ''
            },
            
            height: 'auto',
            
            businessHours: BookingCalendar.config.business_hours,
            
            slotMinTime: '06:00:00',
            slotMaxTime: '24:00:00',
            slotDuration: '01:00:00',
            
            allDaySlot: false,
            nowIndicator: true,
            
            selectable: true,
            selectMirror: true,
            
            eventClick: function(info) {
                handleEventClick(info);
            },
            
            select: function(info) {
                handleTimeSlotSelection(info);
            },
            
            eventDrop: function(info) {
                handleEventDrop(info);
            },
            
            eventResize: function(info) {
                handleEventResize(info);
            },
            
            events: function(info, successCallback, failureCallback) {
                loadEvents(info.startStr, info.endStr, successCallback, failureCallback);
            },
            
            eventDidMount: function(info) {
                // Ajouter des tooltips et la sélection
                setupEventTooltip(info);
                setupEventSelection(info);
            }
        });
        
        calendar.render();
    }
    
    /**
     * Configuration des gestionnaires d'événements
     */
    function setupEventHandlers() {
        // Navigation du calendrier
        document.getElementById('today-btn')?.addEventListener('click', () => {
            calendar.today();
        });
        
        document.getElementById('prev-btn')?.addEventListener('click', () => {
            calendar.prev();
        });
        
        document.getElementById('next-btn')?.addEventListener('click', () => {
            calendar.next();
        });
        
        // Changement de vue
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const view = e.target.getAttribute('data-view');
                calendar.changeView(view);
                
                // Mettre à jour les boutons actifs
                document.querySelectorAll('[data-view]').forEach(b => b.classList.remove('btn-info'));
                e.target.classList.add('btn-info');
            });
        });
        
        // Filtres
        document.getElementById('booker-filter')?.addEventListener('change', () => {
            refreshCalendar();
        });
        
        document.getElementById('status-filter')?.addEventListener('change', () => {
            refreshCalendar();
        });
        
        // Nouvelle réservation
        document.getElementById('new-reservation-btn')?.addEventListener('click', () => {
            openReservationModal();
        });
        
        // Actions en lot
        document.getElementById('bulk-accept')?.addEventListener('click', () => {
            performBulkAction('accept');
        });
        
        document.getElementById('bulk-refuse')?.addEventListener('click', () => {
            performBulkAction('refuse');
        });
        
        document.getElementById('bulk-delete')?.addEventListener('click', () => {
            performBulkAction('delete');
        });
        
        document.getElementById('clear-selection')?.addEventListener('click', () => {
            clearSelection();
        });
        
        // Modal de réservation
        document.getElementById('save-reservation-btn')?.addEventListener('click', () => {
            saveReservation();
        });
        
        document.getElementById('delete-reservation-btn')?.addEventListener('click', () => {
            deleteReservation();
        });
        
        // Modal de confirmation
        document.getElementById('confirm-action-btn')?.addEventListener('click', () => {
            if (pendingAction) {
                pendingAction();
                $('#confirm-modal').modal('hide');
                pendingAction = null;
            }
        });
        
        // Auto-remplissage du prix selon le booker
        document.getElementById('modal-booker')?.addEventListener('change', (e) => {
            const option = e.target.selectedOptions[0];
            const price = option?.getAttribute('data-price');
            if (price) {
                document.getElementById('modal-price').value = price;
            }
        });
        
        // Validation des heures
        document.getElementById('modal-hour-from')?.addEventListener('change', validateTimeRange);
        document.getElementById('modal-hour-to')?.addEventListener('change', validateTimeRange);
    }
    
    /**
     * Charger les événements du calendrier
     */
    function loadEvents(start, end, successCallback, failureCallback) {
        const bookerFilter = document.getElementById('booker-filter')?.value || 'all';
        const statusFilter = document.getElementById('status-filter')?.value || 'all';
        
        const params = new URLSearchParams({
            start: start,
            end: end,
            booker_id: bookerFilter,
            status: statusFilter
        });
        
        fetch(`${BookingCalendar.ajaxUrls.get_events}&${params}`)
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    successCallback(data);
                } else {
                    console.error('Format de données invalide:', data);
                    failureCallback();
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des événements:', error);
                failureCallback();
                showNotification(BookingCalendar.messages.error, 'error');
            });
    }
    
    /**
     * Gestion du clic sur un événement
     */
    function handleEventClick(info) {
        const event = info.event;
        
        // Si Ctrl/Cmd enfoncé, gérer la sélection multiple
        if (info.jsEvent.ctrlKey || info.jsEvent.metaKey) {
            toggleEventSelection(event);
            return;
        }
        
        // Sinon, ouvrir le modal d'édition
        openReservationModal(event);
    }
    
    /**
     * Gestion de la sélection d'un créneau temporel
     */
    function handleTimeSlotSelection(info) {
        openReservationModal(null, info);
    }
    
    /**
     * Gestion du déplacement d'événement
     */
    function handleEventDrop(info) {
        const event = info.event;
        
        updateReservationDateTime(
            event.extendedProps.reservation_id,
            event.startStr.split('T')[0], // Date
            event.start.getHours(), // Heure début
            event.end.getHours() // Heure fin
        );
    }
    
    /**
     * Gestion du redimensionnement d'événement
     */
    function handleEventResize(info) {
        const event = info.event;
        
        updateReservationDateTime(
            event.extendedProps.reservation_id,
            event.startStr.split('T')[0],
            event.start.getHours(),
            event.end.getHours()
        );
    }
    
    /**
     * Configuration du tooltip pour un événement
     */
    function setupEventTooltip(info) {
        const event = info.event;
        const props = event.extendedProps;
        
        let tooltipContent = `
            <strong>${props.customer_firstname} ${props.customer_lastname}</strong><br>
            ${props.booker_name}<br>
            ${event.startStr.split('T')[1].substr(0,5)} - ${event.endStr.split('T')[1].substr(0,5)}
        `;
        
        if (props.customer_email) {
            tooltipContent += `<br>📧 ${props.customer_email}`;
        }
        
        if (props.customer_phone) {
            tooltipContent += `<br>📞 ${props.customer_phone}`;
        }
        
        if (props.total_price) {
            tooltipContent += `<br>💰 ${props.total_price}€`;
        }
        
        if (props.booking_reference) {
            tooltipContent += `<br>🔗 ${props.booking_reference}`;
        }
        
        info.el.setAttribute('title', tooltipContent.replace(/<br>/g, '\n').replace(/<[^>]*>/g, ''));
        info.el.setAttribute('data-toggle', 'tooltip');
        info.el.setAttribute('data-html', 'true');
        info.el.setAttribute('data-placement', 'top');
    }
    
    /**
     * Configuration de la sélection pour un événement
     */
    function setupEventSelection(info) {
        const event = info.event;
        
        // Ajouter un indicateur si l'événement est sélectionné
        if (BookingCalendar.selectedEvents.includes(event.extendedProps.reservation_id)) {
            info.el.classList.add('fc-event-selected');
        }
    }
    
    /**
     * Basculer la sélection d'un événement
     */
    function toggleEventSelection(event) {
        const reservationId = event.extendedProps.reservation_id;
        const index = BookingCalendar.selectedEvents.indexOf(reservationId);
        
        if (index > -1) {
            // Désélectionner
            BookingCalendar.selectedEvents.splice(index, 1);
            event.setProp('classNames', event.classNames.filter(c => c !== 'fc-event-selected'));
        } else {
            // Sélectionner
            BookingCalendar.selectedEvents.push(reservationId);
            event.setProp('classNames', [...event.classNames, 'fc-event-selected']);
        }
        
        updateSelectionUI();
    }
    
    /**
     * Mettre à jour l'interface de sélection
     */
    function updateSelectionUI() {
        const count = BookingCalendar.selectedEvents.length;
        
        document.getElementById('selected-count').textContent = count;
        
        if (count > 0) {
            document.getElementById('bulk-actions-panel').style.display = 'block';
        } else {
            document.getElementById('bulk-actions-panel').style.display = 'none';
        }
    }
    
    /**
     * Effacer la sélection
     */
    function clearSelection() {
        BookingCalendar.selectedEvents = [];
        
        // Retirer la classe de sélection de tous les événements
        calendar.getEvents().forEach(event => {
            event.setProp('classNames', event.classNames.filter(c => c !== 'fc-event-selected'));
        });
        
        updateSelectionUI();
    }
    
    /**
     * Ouvrir le modal de réservation
     */
    function openReservationModal(event = null, timeSelection = null) {
        const modal = $('#reservation-modal');
        const form = document.getElementById('reservation-form');
        
        // Réinitialiser le formulaire
        form.reset();
        
        if (event) {
            // Mode édition
            const props = event.extendedProps;
            
            document.getElementById('reservation-modal-title').textContent = 'Modifier la réservation';
            document.getElementById('reservation-id').value = props.reservation_id;
            document.getElementById('modal-booker').value = props.booker_id;
            document.getElementById('modal-date').value = event.startStr.split('T')[0];
            document.getElementById('modal-hour-from').value = event.start.getHours();
            document.getElementById('modal-hour-to').value = event.end.getHours();
            document.getElementById('modal-firstname').value = props.customer_firstname || '';
            document.getElementById('modal-lastname').value = props.customer_lastname || '';
            document.getElementById('modal-email').value = props.customer_email || '';
            document.getElementById('modal-phone').value = props.customer_phone || '';
            document.getElementById('modal-status').value = props.status || 0;
            document.getElementById('modal-price').value = props.total_price || '';
            document.getElementById('modal-message').value = props.customer_message || '';
            
            document.getElementById('delete-reservation-btn').style.display = 'inline-block';
            
        } else {
            // Mode création
            document.getElementById('reservation-modal-title').textContent = 'Nouvelle réservation';
            document.getElementById('delete-reservation-btn').style.display = 'none';
            
            if (timeSelection) {
                document.getElementById('modal-date').value = timeSelection.startStr.split('T')[0];
                document.getElementById('modal-hour-from').value = timeSelection.start.getHours();
                document.getElementById('modal-hour-to').value = timeSelection.end.getHours();
            } else {
                // Valeurs par défaut
                document.getElementById('modal-date').value = new Date().toISOString().split('T')[0];
                document.getElementById('modal-hour-from').value = '9';
                document.getElementById('modal-hour-to').value = '10';
            }
            
            document.getElementById('modal-status').value = '0'; // Pending par défaut
        }
        
        currentModal = modal;
        modal.modal('show');
    }
    
    /**
     * Sauvegarder une réservation
     */
    function saveReservation() {
        const form = document.getElementById('reservation-form');
        
        if (!validateReservationForm()) {
            return;
        }
        
        const formData = new FormData(form);
        const reservationId = formData.get('reservation_id');
        
        const url = reservationId ? 
            BookingCalendar.ajaxUrls.update_reservation : 
            BookingCalendar.ajaxUrls.create_reservation;
        
        showLoading(true);
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            
            if (data.success) {
                showNotification(
                    reservationId ? BookingCalendar.messages.success_update : BookingCalendar.messages.success_create,
                    'success'
                );
                
                $('#reservation-modal').modal('hide');
                refreshCalendar();
            } else {
                showNotification(data.message || BookingCalendar.messages.error, 'error');
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('Erreur lors de la sauvegarde:', error);
            showNotification(BookingCalendar.messages.error, 'error');
        });
    }
    
    /**
     * Supprimer une réservation
     */
    function deleteReservation() {
        const reservationId = document.getElementById('reservation-id').value;
        
        if (!reservationId) return;
        
        showConfirmation(
            BookingCalendar.messages.confirm_delete,
            () => executeDeleteReservation(reservationId)
        );
    }
    
    /**
     * Exécuter la suppression d'une réservation
     */
    function executeDeleteReservation(reservationId) {
        const formData = new FormData();
        formData.append('id_reserved', reservationId);
        
        showLoading(true);
        
        fetch(BookingCalendar.ajaxUrls.delete_reservation, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            
            if (data.success) {
                showNotification(BookingCalendar.messages.success_delete, 'success');
                $('#reservation-modal').modal('hide');
                refreshCalendar();
            } else {
                showNotification(data.message || BookingCalendar.messages.error, 'error');
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('Erreur lors de la suppression:', error);
            showNotification(BookingCalendar.messages.error, 'error');
        });
    }
    
    /**
     * Mettre à jour la date/heure d'une réservation
     */
    function updateReservationDateTime(reservationId, newDate, newHourFrom, newHourTo) {
        const formData = new FormData();
        formData.append('id_reserved', reservationId);
        formData.append('new_date', newDate);
        formData.append('new_hour_from', newHourFrom);
        formData.append('new_hour_to', newHourTo);
        
        fetch(BookingCalendar.ajaxUrls.update_reservation, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(BookingCalendar.messages.success_update, 'success');
            } else {
                showNotification(data.message || BookingCalendar.messages.error, 'error');
                // Revert the change
                refreshCalendar();
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour:', error);
            showNotification(BookingCalendar.messages.error, 'error');
            refreshCalendar();
        });
    }
    
    /**
     * Exécuter une action en lot
     */
    function performBulkAction(action) {
        if (BookingCalendar.selectedEvents.length === 0) {
            showNotification(BookingCalendar.messages.no_selection, 'warning');
            return;
        }
        
        let message;
        switch (action) {
            case 'accept':
                message = BookingCalendar.messages.confirm_bulk_accept;
                break;
            case 'refuse':
                message = BookingCalendar.messages.confirm_bulk_refuse;
                break;
            case 'delete':
                message = BookingCalendar.messages.confirm_bulk_delete;
                break;
            default:
                return;
        }
        
        showConfirmation(message, () => executeBulkAction(action));
    }
    
    /**
     * Exécuter l'action en lot
     */
    function executeBulkAction(action) {
        const formData = new FormData();
        formData.append('bulk_action', action);
        formData.append('reservation_ids', JSON.stringify(BookingCalendar.selectedEvents));
        
        showLoading(true);
        
        fetch(BookingCalendar.ajaxUrls.bulk_action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            
            if (data.success) {
                showNotification(data.message, 'success');
                clearSelection();
                refreshCalendar();
            } else {
                showNotification(data.message || BookingCalendar.messages.error, 'error');
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('Erreur lors de l\'action en lot:', error);
            showNotification(BookingCalendar.messages.error, 'error');
        });
    }
    
    /**
     * Valider le formulaire de réservation
     */
    function validateReservationForm() {
        const requiredFields = ['modal-booker', 'modal-date', 'modal-hour-from', 'modal-hour-to', 'modal-firstname', 'modal-lastname'];
        
        for (const fieldId of requiredFields) {
            const field = document.getElementById(fieldId);
            if (!field || !field.value.trim()) {
                showNotification(BookingCalendar.messages.validation_required, 'error');
                field?.focus();
                return false;
            }
        }
        
        return validateTimeRange();
    }
    
    /**
     * Valider la plage horaire
     */
    function validateTimeRange() {
        const hourFrom = parseInt(document.getElementById('modal-hour-from').value);
        const hourTo = parseInt(document.getElementById('modal-hour-to').value);
        
        if (hourFrom >= hourTo) {
            showNotification(BookingCalendar.messages.validation_time, 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Rafraîchir le calendrier
     */
    function refreshCalendar() {
        if (calendar) {
            calendar.refetchEvents();
        }
    }
    
    /**
     * Afficher une notification
     */
    function showNotification(message, type = 'info') {
        // Utiliser le système de notification de PrestaShop si disponible
        if (typeof $.growl === 'function') {
            $.growl({ message: message }, { type: type });
        } else {
            alert(message);
        }
    }
    
    /**
     * Afficher une confirmation
     */
    function showConfirmation(message, callback) {
        document.getElementById('confirm-message').textContent = message;
        pendingAction = callback;
        $('#confirm-modal').modal('show');
    }
    
    /**
     * Afficher/masquer le loading
     */
    function showLoading(show) {
        // Implementation dépendante de l'UI de PrestaShop
        if (show) {
            document.body.style.cursor = 'wait';
        } else {
            document.body.style.cursor = 'default';
        }
    }
    
    // Initialiser les tooltips Bootstrap
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
});