/**
 * Gestionnaire du calendrier des réservations
 * Compatible avec FullCalendar v5
 */

document.addEventListener('DOMContentLoaded', function() {
    
    let calendar;
    let currentBookerId = null;
    
    // Vérifier que FullCalendar est chargé
    if (typeof FullCalendar === 'undefined') {
        console.error('FullCalendar n\'est pas chargé. Vérifiez que le CDN est accessible.');
        return;
    }
    
    // Vérifier que les variables JavaScript sont définies
    if (typeof BookingCalendar === 'undefined') {
        console.error('Variables BookingCalendar non définies');
        return;
    }
    
    // Initialisation
    initializeCalendar();
    setupEventHandlers();
    
    /**
     * Initialisation du calendrier FullCalendar v5
     */
    function initializeCalendar() {
        const calendarEl = document.getElementById('calendar');
        
        if (!calendarEl) {
            console.error('Élément calendrier (#calendar) non trouvé');
            return;
        }
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: BookingCalendar.config.locale || 'fr',
            initialView: 'timeGridWeek',
            initialDate: BookingCalendar.currentDate,
            
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            
            height: 'auto',
            
            businessHours: BookingCalendar.config.business_hours,
            
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
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
                // Ajouter des tooltips
                setupEventTooltip(info);
            },
            
            loading: function(bool) {
                const loadingEl = document.getElementById('calendar-loading');
                if (loadingEl) {
                    loadingEl.style.display = bool ? 'block' : 'none';
                }
            }
        });
        
        calendar.render();
    }
    
    /**
     * Configuration des gestionnaires d'événements
     */
    function setupEventHandlers() {
        // Filtre par booker
        const bookerFilter = document.getElementById('booker-filter');
        if (bookerFilter) {
            bookerFilter.addEventListener('change', function() {
                currentBookerId = this.value;
                calendar.refetchEvents();
            });
        }
        
        // Boutons de vue
        document.getElementById('btn-month-view')?.addEventListener('click', () => {
            calendar.changeView('dayGridMonth');
            updateActiveViewButton('btn-month-view');
        });
        
        document.getElementById('btn-week-view')?.addEventListener('click', () => {
            calendar.changeView('timeGridWeek');
            updateActiveViewButton('btn-week-view');
        });
        
        document.getElementById('btn-day-view')?.addEventListener('click', () => {
            calendar.changeView('timeGridDay');
            updateActiveViewButton('btn-day-view');
        });
        
        // Bouton actualiser
        document.getElementById('btn-refresh')?.addEventListener('click', () => {
            calendar.refetchEvents();
        });
        
        // Bouton nouvelle réservation
        document.getElementById('btn-add-reservation')?.addEventListener('click', () => {
            openNewReservationModal();
        });
    }
    
    /**
     * Charger les événements via AJAX
     */
    function loadEvents(start, end, successCallback, failureCallback) {
        const params = new URLSearchParams({
            start: start,
            end: end,
            ajax: 1,
            action: 'getEvents'
        });
        
        if (currentBookerId) {
            params.append('booker_id', currentBookerId);
        }
        
        fetch(BookingCalendar.ajax_urls.get_events + '&' + params.toString())
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
            });
    }
    
    /**
     * Gestion du clic sur un événement
     */
    function handleEventClick(info) {
        const event = info.event;
        const props = event.extendedProps;
        
        // Construire le contenu du modal
        const details = `
            <div class="row">
                <div class="col-sm-6"><strong>Référence:</strong></div>
                <div class="col-sm-6">${props.booking_reference || 'N/A'}</div>
            </div>
            <div class="row">
                <div class="col-sm-6"><strong>Client:</strong></div>
                <div class="col-sm-6">${event.title}</div>
            </div>
            <div class="row">
                <div class="col-sm-6"><strong>Email:</strong></div>
                <div class="col-sm-6">${props.customer_email || 'N/A'}</div>
            </div>
            <div class="row">
                <div class="col-sm-6"><strong>Téléphone:</strong></div>
                <div class="col-sm-6">${props.customer_phone || 'N/A'}</div>
            </div>
            <div class="row">
                <div class="col-sm-6"><strong>Élément:</strong></div>
                <div class="col-sm-6">${props.booker_name}</div>
            </div>
            <div class="row">
                <div class="col-sm-6"><strong>Date/Heure:</strong></div>
                <div class="col-sm-6">${formatEventDateTime(event)}</div>
            </div>
            <div class="row">
                <div class="col-sm-6"><strong>Statut:</strong></div>
                <div class="col-sm-6">${getStatusLabel(props.status)}</div>
            </div>
            <div class="row">
                <div class="col-sm-6"><strong>Prix:</strong></div>
                <div class="col-sm-6">${props.total_price || '0'}€</div>
            </div>
        `;
        
        document.getElementById('reservation-details').innerHTML = details;
        
        // Stocker l'ID de l'événement pour les actions
        document.getElementById('btn-edit-reservation').dataset.eventId = event.id;
        document.getElementById('btn-delete-reservation').dataset.eventId = event.id;
        
        // Afficher le modal
        $('#reservation-modal').modal('show');
    }
    
    /**
     * Gestion de la sélection d'un créneau
     */
    function handleTimeSlotSelection(info) {
        const startDate = info.start;
        const endDate = info.end;
        
        // Ouvrir le modal de nouvelle réservation avec les heures pré-remplies
        openNewReservationModal(startDate, endDate);
        
        // Désélectionner
        calendar.unselect();
    }
    
    /**
     * Gestion du déplacement d'événement
     */
    function handleEventDrop(info) {
        const event = info.event;
        
        if (confirm('Déplacer cette réservation ?')) {
            updateReservationDateTime(event.id, event.start, event.end);
        } else {
            info.revert();
        }
    }
    
    /**
     * Gestion du redimensionnement d'événement
     */
    function handleEventResize(info) {
        const event = info.event;
        
        if (confirm('Modifier la durée de cette réservation ?')) {
            updateReservationDateTime(event.id, event.start, event.end);
        } else {
            info.revert();
        }
    }
    
    /**
     * Ajouter un tooltip à un événement
     */
    function setupEventTooltip(info) {
        const event = info.event;
        const props = event.extendedProps;
        
        info.el.title = `${event.title}
Élément: ${props.booker_name}
Heure: ${formatEventDateTime(event)}
Statut: ${getStatusLabel(props.status)}`;
    }
    
    /**
     * Ouvrir le modal de nouvelle réservation
     */
    function openNewReservationModal(startDate = null, endDate = null) {
        // TODO: Implémenter le modal de création
        alert('Modal de création en cours de développement');
    }
    
    /**
     * Mettre à jour une réservation
     */
    function updateReservationDateTime(eventId, start, end) {
        const params = new URLSearchParams({
            ajax: 1,
            action: 'updateReservation',
            id: eventId,
            start: start.toISOString(),
            end: end.toISOString()
        });
        
        fetch(BookingCalendar.ajax_urls.update_reservation, {
            method: 'POST',
            body: params
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Réservation mise à jour', 'success');
            } else {
                showNotification('Erreur: ' + (data.error || 'Mise à jour échouée'), 'error');
                calendar.refetchEvents();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur de communication', 'error');
            calendar.refetchEvents();
        });
    }
    
    /**
     * Formater la date/heure d'un événement
     */
    function formatEventDateTime(event) {
        const start = event.start;
        const end = event.end;
        
        if (!start) return 'N/A';
        
        const dateStr = start.toLocaleDateString('fr-FR');
        const startTime = start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        const endTime = end ? end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '';
        
        return `${dateStr} ${startTime}${endTime ? ' - ' + endTime : ''}`;
    }
    
    /**
     * Obtenir le libellé d'un statut
     */
    function getStatusLabel(statusId) {
        const statuses = BookingCalendar.statuses || {};
        return statuses[statusId] || 'Inconnu';
    }
    
    /**
     * Mettre à jour le bouton de vue active
     */
    function updateActiveViewButton(activeButtonId) {
        document.querySelectorAll('#btn-month-view, #btn-week-view, #btn-day-view').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById(activeButtonId)?.classList.add('active');
    }
    
    /**
     * Afficher une notification
     */
    function showNotification(message, type = 'info') {
        // Utiliser le système de notifications de PrestaShop si disponible
        if (typeof showSuccessMessage === 'function' && type === 'success') {
            showSuccessMessage(message);
        } else if (typeof showErrorMessage === 'function' && type === 'error') {
            showErrorMessage(message);
        } else {
            // Fallback simple
            alert(message);
        }
    }
    
    // Exposer des fonctions utiles globalement
    window.BookingCalendarManager = {
        refreshEvents: () => calendar.refetchEvents(),
        goToDate: (date) => calendar.gotoDate(date),
        changeView: (view) => calendar.changeView(view)
    };
});