/**
 * Calendrier des Réservations - Interface Interactive
 */
$(document).ready(function() {
    
    // Initialisation
    ReservationCalendar.init();
    
    // Events handlers
    setupEventHandlers();
    
    // Charger le calendrier initial
    loadCalendar();
});

/**
 * Objet principal du calendrier des réservations
 */
ReservationCalendar.init = function() {
    this.currentBookerId = $('#booker-select').val() || '';
    this.currentView = $('#calendar-view').val() || 'month';
    this.statusFilter = $('#status-filter').val() || 'all';
    this.selectionMode = 'single';
    this.selectedReservations = [];
    this.calendarData = null;
    this.currentReservation = null;
    
    // Mettre à jour l'affichage de la période
    this.updatePeriodDisplay();
};

/**
 * Configuration des gestionnaires d'événements
 */
function setupEventHandlers() {
    
    // Sélection du booker
    $('#booker-select').on('change', function() {
        ReservationCalendar.currentBookerId = $(this).val();
        loadCalendar();
    });
    
    // Filtre de statut
    $('#status-filter').on('change', function() {
        ReservationCalendar.statusFilter = $(this).val();
        loadCalendar();
    });
    
    // Changement de vue
    $('#calendar-view').on('change', function() {
        ReservationCalendar.currentView = $(this).val();
        ReservationCalendar.updatePeriodDisplay();
        loadCalendar();
    });
    
    // Navigation
    $('#prev-period').on('click', function() {
        navigatePeriod(-1);
    });
    
    $('#next-period').on('click', function() {
        navigatePeriod(1);
    });
    
    $('#today-btn').on('click', function() {
        goToToday();
    });
    
    $('#refresh-calendar').on('click', function() {
        loadCalendar();
    });
    
    // Mode de sélection
    $('#select-mode, #multi-select-mode').on('click', function() {
        var mode = $(this).data('mode');
        setSelectionMode(mode);
    });
    
    // Nouvelle réservation
    $('#create-reservation').on('click', function() {
        showReservationModal();
    });
    
    // Actions en lot
    $('#bulk-accept').on('click', function() {
        processBulkAction('accept');
    });
    
    $('#bulk-cancel').on('click', function() {
        processBulkAction('cancel');
    });
    
    $('#bulk-delete').on('click', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer les réservations sélectionnées ?')) {
            processBulkAction('delete');
        }
    });
    
    // Modal réservation
    $('#save-reservation').on('click', function() {
        saveReservation();
    });
    
    $('#check-availability').on('click', function() {
        checkAvailability();
    });
    
    // Changement de booker dans la modal - recharger les créneaux
    $('#reservation-booker-id, #reservation-date').on('change', function() {
        var bookerId = $('#reservation-booker-id').val();
        var date = $('#reservation-date').val();
        
        if (bookerId && date) {
            loadAvailableSlots(bookerId, date);
        }
    });
    
    // Modal détails réservation
    $('#edit-reservation').on('click', function() {
        editCurrentReservation();
    });
    
    // Changement de statut
    $('#change-status-pending, #change-status-accepted, #change-status-paid, #change-status-cancelled').on('click', function() {
        var newStatus = $(this).data('status');
        changeReservationStatus(ReservationCalendar.currentReservation.id, newStatus);
    });
    
    // Modal actions en lot
    $('#confirm-bulk-reservations').on('click', function() {
        confirmBulkAction();
    });
}

/**
 * Charger les données du calendrier
 */
function loadCalendar() {
    $('#calendar-loading').show();
    $('#calendar-content').hide();
    
    var params = {
        action: 'loadCalendar',
        booker_id: ReservationCalendar.currentBookerId,
        year: ReservationCalendar.currentYear,
        month: ReservationCalendar.currentMonth,
        view: ReservationCalendar.currentView,
        status_filter: ReservationCalendar.statusFilter
    };
    
    $.ajax({
        url: ReservationCalendar.ajaxUrl,
        type: 'POST',
        data: params,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                ReservationCalendar.calendarData = response.data;
                renderCalendar(response.data);
                updateStatistics(response.data.reservations);
            } else {
                alert('Erreur lors du chargement du calendrier: ' + (response.error || 'Erreur inconnue'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur AJAX:', error);
            alert('Erreur de communication avec le serveur.');
        },
        complete: function() {
            $('#calendar-loading').hide();
            $('#calendar-content').show();
        }
    });
}

/**
 * Rendu du calendrier selon la vue
 */
function renderCalendar(data) {
    var html = '';
    
    switch (ReservationCalendar.currentView) {
        case 'month':
            html = renderMonthView(data);
            break;
        case 'week':
            html = renderWeekView(data);
            break;
        case 'day':
            html = renderDayView(data);
            break;
    }
    
    $('#calendar-content').html(html);
    
    // Attacher les événements
    attachCalendarEvents();
}

/**
 * Rendu de la vue mensuelle
 */
function renderMonthView(data) {
    var monthInfo = data.month_info;
    var reservations = data.reservations || [];
    var availabilities = data.availabilities || [];
    
    // Index des réservations par date
    var reservationIndex = {};
    reservations.forEach(function(res) {
        var dateStr = res.date;
        if (!reservationIndex[dateStr]) {
            reservationIndex[dateStr] = [];
        }
        reservationIndex[dateStr].push(res);
    });
    
    // Index des disponibilités par date
    var availabilityIndex = {};
    availabilities.forEach(function(avail) {
        var startDate = new Date(avail.date_from);
        var endDate = new Date(avail.date_to);
        
        for (var d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
            var dateStr = formatDate(d);
            if (!availabilityIndex[dateStr]) {
                availabilityIndex[dateStr] = [];
            }
            availabilityIndex[dateStr].push(avail);
        }
    });
    
    var html = '<div class="calendar-month">';
    html += '<table class="table table-bordered calendar-table">';
    
    // En-tête des jours
    html += '<thead><tr>';
    var dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    dayNames.forEach(function(day) {
        html += '<th class="text-center">' + day + '</th>';
    });
    html += '</tr></thead>';
    
    html += '<tbody>';
    
    // Calculer les dates
    var firstDay = new Date(monthInfo.year, monthInfo.month - 1, 1);
    var startCalendar = new Date(firstDay);
    startCalendar.setDate(startCalendar.getDate() - (firstDay.getDay() + 6) % 7);
    
    var currentDate = new Date(startCalendar);
    
    // Générer les semaines
    for (var week = 0; week < 6; week++) {
        html += '<tr>';
        
        for (var day = 0; day < 7; day++) {
            var dateStr = formatDate(currentDate);
            var isCurrentMonth = currentDate.getMonth() === monthInfo.month - 1;
            var isToday = isDateToday(currentDate);
            
            var dayReservations = reservationIndex[dateStr] || [];
            var dayAvailabilities = availabilityIndex[dateStr] || [];
            
            var cssClasses = ['calendar-day'];
            if (!isCurrentMonth) cssClasses.push('other-month');
            if (isToday) cssClasses.push('today');
            if (dayAvailabilities.length > 0) cssClasses.push('has-availability');
            
            html += '<td class="' + cssClasses.join(' ') + '" data-date="' + dateStr + '">';
            html += '<div class="day-number">' + currentDate.getDate() + '</div>';
            
            // Afficher les réservations
            dayReservations.forEach(function(reservation) {
                var selected = ReservationCalendar.selectedReservations.includes(reservation.id);
                var cssClass = 'reservation-item ' + reservation.css_class + (selected ? ' selected' : '');
                
                html += '<div class="' + cssClass + '" data-reservation-id="' + reservation.id + '">';
                html += '<div class="reservation-time">' + 
                        String(reservation.hour_from).padStart(2, '0') + ':00 - ' + 
                        String(reservation.hour_to).padStart(2, '0') + ':00</div>';
                if (reservation.customer_name) {
                    html += '<div class="reservation-customer">' + escapeHtml(reservation.customer_name) + '</div>';
                }
                html += '<div class="reservation-status">' + reservation.status_label + '</div>';
                html += '</div>';
            });
            
            html += '</td>';
            
            currentDate.setDate(currentDate.getDate() + 1);
        }
        
        html += '</tr>';
        
        if (currentDate.getMonth() !== monthInfo.month - 1 && week >= 4) {
            break;
        }
    }
    
    html += '</tbody>';
    html += '</table>';
    html += '</div>';
    
    return html;
}

/**
 * Attacher les événements aux éléments du calendrier
 */
function attachCalendarEvents() {
    // Clic sur une réservation
    $('.reservation-item').on('click', function(e) {
        e.stopPropagation();
        var reservationId = $(this).data('reservation-id');
        
        if (ReservationCalendar.selectionMode === 'single') {
            showReservationDetails(reservationId);
        } else {
            toggleReservationSelection(reservationId, this);
        }
    });
    
    // Clic sur un jour (pour créer une nouvelle réservation)
    $('.calendar-day').on('click', function(e) {
        if (ReservationCalendar.selectionMode === 'single') {
            var date = $(this).data('date');
            if ($(this).hasClass('has-availability')) {
                showReservationModal(date);
            } else {
                alert('Aucune disponibilité pour cette date.');
            }
        }
    });
}

/**
 * Basculer la sélection d'une réservation
 */
function toggleReservationSelection(reservationId, element) {
    var index = ReservationCalendar.selectedReservations.indexOf(reservationId);
    
    if (index === -1) {
        ReservationCalendar.selectedReservations.push(reservationId);
        $(element).addClass('selected');
    } else {
        ReservationCalendar.selectedReservations.splice(index, 1);
        $(element).removeClass('selected');
    }
    
    updateSelectionDisplay();
}

/**
 * Définir le mode de sélection
 */
function setSelectionMode(mode) {
    ReservationCalendar.selectionMode = mode;
    
    // Mettre à jour l'interface
    $('#select-mode, #multi-select-mode').removeClass('active');
    $('[data-mode="' + mode + '"]').addClass('active');
    
    // Afficher/masquer les actions en lot
    if (mode === 'multi') {
        $('#bulk-actions').show();
        $('#calendar-content').addClass('multi-select-mode');
    } else {
        $('#bulk-actions').hide();
        $('#calendar-content').removeClass('multi-select-mode');
    }
    
    // Réinitialiser la sélection
    ReservationCalendar.selectedReservations = [];
    $('.reservation-item').removeClass('selected');
    updateSelectionDisplay();
}

/**
 * Mettre à jour l'affichage de la sélection
 */
function updateSelectionDisplay() {
    var count = ReservationCalendar.selectedReservations.length;
    $('#selected-reservations-count').text(count);
    
    // Activer/désactiver les boutons d'action
    $('#bulk-accept, #bulk-cancel, #bulk-delete').prop('disabled', count === 0);
}

/**
 * Afficher la modal de réservation
 */
function showReservationModal(date, reservation) {
    $('#reservation-form')[0].reset();
    
    if (reservation) {
        // Mode édition
        $('.modal-title').text('Modifier la réservation');
        $('#reservation-id').val(reservation.id);
        $('#reservation-booker-id').val(reservation.booker_id);
        $('#reservation-date').val(reservation.date);
        $('#reservation-hour-from').val(reservation.hour_from);
        $('#reservation-hour-to').val(reservation.hour_to);
        $('#reservation-status').val(reservation.status);
        $('#customer-name').val(reservation.customer_name);
        $('#customer-email').val(reservation.customer_email);
        $('#customer-phone').val(reservation.customer_phone);
        $('#reservation-notes').val(reservation.notes);
    } else {
        // Mode création
        $('.modal-title').text('Nouvelle réservation');
        $('#reservation-booker-id').val(ReservationCalendar.currentBookerId);
        if (date) {
            $('#reservation-date').val(date);
        }
        $('#reservation-status').val(0); // Pending par défaut
    }
    
    $('#reservation-modal').modal('show');
}

/**
 * Afficher les détails d'une réservation
 */
function showReservationDetails(reservationId) {
    // Trouver la réservation dans les données
    var reservation = null;
    if (ReservationCalendar.calendarData && ReservationCalendar.calendarData.reservations) {
        reservation = ReservationCalendar.calendarData.reservations.find(r => r.id == reservationId);
    }
    
    if (!reservation) {
        alert('Réservation introuvable.');
        return;
    }
    
    ReservationCalendar.currentReservation = reservation;
    
    var html = '<div class="reservation-details">';
    html += '<div class="row">';
    html += '<div class="col-md-6"><strong>Date:</strong> ' + reservation.date + '</div>';
    html += '<div class="col-md-6"><strong>Horaire:</strong> ' + 
            String(reservation.hour_from).padStart(2, '0') + ':00 - ' + 
            String(reservation.hour_to).padStart(2, '0') + ':00</div>';
    html += '</div>';
    html += '<div class="row">';
    html += '<div class="col-md-6"><strong>Statut:</strong> <span class="label ' + 
            getStatusLabelClass(reservation.status) + '">' + reservation.status_label + '</span></div>';
    html += '<div class="col-md-6"><strong>Créée le:</strong> ' + formatDateTime(reservation.date_add) + '</div>';
    html += '</div>';
    
    if (reservation.customer_name || reservation.customer_email || reservation.customer_phone) {
        html += '<hr><h5>Informations client:</h5>';
        html += '<div class="row">';
        if (reservation.customer_name) {
            html += '<div class="col-md-12"><strong>Nom:</strong> ' + escapeHtml(reservation.customer_name) + '</div>';
        }
        if (reservation.customer_email) {
            html += '<div class="col-md-12"><strong>Email:</strong> ' + escapeHtml(reservation.customer_email) + '</div>';
        }
        if (reservation.customer_phone) {
            html += '<div class="col-md-12"><strong>Téléphone:</strong> ' + escapeHtml(reservation.customer_phone) + '</div>';
        }
        html += '</div>';
    }
    
    if (reservation.notes) {
        html += '<hr><h5>Notes:</h5>';
        html += '<p>' + escapeHtml(reservation.notes) + '</p>';
    }
    
    html += '</div>';
    
    $('#reservation-details-content').html(html);
    $('#reservation-details-modal').modal('show');
}

/**
 * Sauvegarder une réservation
 */
function saveReservation() {
    var formData = {
        action: 'createReservation',
        reservation_id: $('#reservation-id').val(),
        booker_id: $('#reservation-booker-id').val(),
        date_reserved: $('#reservation-date').val(),
        hour_from: $('#reservation-hour-from').val(),
        hour_to: $('#reservation-hour-to').val(),
        status: $('#reservation-status').val(),
        customer_name: $('#customer-name').val(),
        customer_email: $('#customer-email').val(),
        customer_phone: $('#customer-phone').val(),
        notes: $('#reservation-notes').val()
    };
    
    // Validation basique
    if (!formData.booker_id || !formData.date_reserved || !formData.hour_from || !formData.hour_to) {
        alert('Veuillez remplir tous les champs obligatoires.');
        return;
    }
    
    if (parseInt(formData.hour_from) >= parseInt(formData.hour_to)) {
        alert('L\'heure de début doit être antérieure à l\'heure de fin.');
        return;
    }
    
    $.ajax({
        url: ReservationCalendar.ajaxUrl,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#reservation-modal').modal('hide');
                loadCalendar();
                alert('Réservation sauvegardée avec succès.');
            } else {
                alert('Erreur: ' + (response.error || 'Erreur inconnue'));
            }
        },
        error: function() {
            alert('Erreur de communication avec le serveur.');
        }
    });
}

/**
 * Vérifier la disponibilité
 */
function checkAvailability() {
    var bookerId = $('#reservation-booker-id').val();
    var date = $('#reservation-date').val();
    
    if (!bookerId || !date) {
        alert('Veuillez sélectionner un élément et une date.');
        return;
    }
    
    loadAvailableSlots(bookerId, date);
}

/**
 * Charger les créneaux disponibles
 */
function loadAvailableSlots(bookerId, date) {
    $.ajax({
        url: ReservationCalendar.ajaxUrl,
        type: 'POST',
        data: {
            action: 'getAvailableSlots',
            booker_id: bookerId,
            date: date
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayAvailableSlots(response.slots);
            } else {
                alert('Erreur lors de la vérification: ' + (response.error || 'Erreur inconnue'));
            }
        },
        error: function() {
            alert('Erreur de communication avec le serveur.');
        }
    });
}

/**
 * Afficher les créneaux disponibles
 */
function displayAvailableSlots(slots) {
    var html = '';
    
    if (slots.length === 0) {
        html = '<div class="alert alert-warning">Aucun créneau disponible pour cette date.</div>';
    } else {
        html = '<div class="available-slots">';
        slots.forEach(function(slot) {
            html += '<span class="label label-success mr-2">' + 
                    String(slot.hour_from).padStart(2, '0') + ':00 - ' + 
                    String(slot.hour_to).padStart(2, '0') + ':00</span> ';
        });
        html += '</div>';
    }
    
    $('#available-slots-list').html(html);
    $('#available-slots-info').show();
}

/**
 * Changer le statut d'une réservation
 */
function changeReservationStatus(reservationId, newStatus) {
    $.ajax({
        url: ReservationCalendar.ajaxUrl,
        type: 'POST',
        data: {
            action: 'changeStatus',
            id: reservationId,
            status: newStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#reservation-details-modal').modal('hide');
                loadCalendar();
                alert('Statut modifié avec succès.');
            } else {
                alert('Erreur: ' + (response.error || 'Erreur inconnue'));
            }
        },
        error: function() {
            alert('Erreur de communication avec le serveur.');
        }
    });
}

/**
 * Traiter une action en lot
 */
function processBulkAction(action) {
    if (ReservationCalendar.selectedReservations.length === 0) {
        alert('Veuillez sélectionner au moins une réservation.');
        return;
    }
    
    showBulkActionModal(action);
}

/**
 * Afficher la modal d'action en lot
 */
function showBulkActionModal(action) {
    var title = '';
    var content = '';
    var buttonClass = 'btn-primary';
    var buttonText = 'Confirmer';
    
    switch (action) {
        case 'accept':
            title = 'Accepter les réservations';
            content = '<div class="alert alert-info">Cette action acceptera les réservations sélectionnées et créera les commandes correspondantes.</div>';
            buttonClass = 'btn-success';
            buttonText = 'Accepter';
            break;
        case 'cancel':
            title = 'Annuler les réservations';
            content = '<div class="alert alert-warning">Cette action annulera les réservations sélectionnées.</div>';
            buttonClass = 'btn-warning';
            buttonText = 'Annuler les réservations';
            break;
        case 'delete':
            title = 'Supprimer les réservations';
            content = '<div class="alert alert-danger"><strong>Attention:</strong> Cette action supprimera définitivement les réservations sélectionnées.</div>';
            buttonClass = 'btn-danger';
            buttonText = 'Supprimer';
            break;
    }
    
    $('#bulk-reservations-modal .modal-title').text(title);
    $('#bulk-action-content').html(content);
    $('#confirm-bulk-reservations')
        .removeClass('btn-primary btn-success btn-warning btn-danger')
        .addClass(buttonClass)
        .text(buttonText)
        .data('action', action);
    
    $('#bulk-reservations-modal').modal('show');
}

/**
 * Confirmer l'action en lot
 */
function confirmBulkAction() {
    var action = $('#confirm-bulk-reservations').data('action');
    
    $.ajax({
        url: ReservationCalendar.ajaxUrl,
        type: 'POST',
        data: {
            action: 'bulkReservations',
            action: action,
            reservation_ids: ReservationCalendar.selectedReservations
        },
        dataType: 'json',
        success: function(response) {
            $('#bulk-reservations-modal').modal('hide');
            
            if (response.success) {
                alert('Action réalisée avec succès sur ' + response.success_count + ' réservation(s).');
            } else {
                var message = 'Action partiellement réalisée:\n';
                message += '- Succès: ' + response.success_count + '\n';
                message += '- Erreurs: ' + response.error_count + '\n';
                if (response.errors.length > 0) {
                    message += 'Détails:\n' + response.errors.join('\n');
                }
                alert(message);
            }
            
            loadCalendar();
            
            // Réinitialiser la sélection
            ReservationCalendar.selectedReservations = [];
            updateSelectionDisplay();
        },
        error: function() {
            alert('Erreur de communication avec le serveur.');
        }
    });
}

/**
 * Mettre à jour les statistiques
 */
function updateStatistics(reservations) {
    var stats = {
        pending: 0,
        accepted: 0,
        paid: 0,
        cancelled: 0,
        expired: 0,
        total: 0
    };
    
    reservations.forEach(function(res) {
        stats.total++;
        switch (res.status) {
            case 0: stats.pending++; break;
            case 1: stats.accepted++; break;
            case 2: stats.paid++; break;
            case 3: stats.cancelled++; break;
            case 4: stats.expired++; break;
        }
    });
    
    $('#stat-pending').text(stats.pending);
    $('#stat-accepted').text(stats.accepted);
    $('#stat-paid').text(stats.paid);
    $('#stat-cancelled').text(stats.cancelled);
    $('#stat-expired').text(stats.expired);
    $('#stat-total').text(stats.total);
}

/**
 * Navigation dans les périodes
 */
function navigatePeriod(direction) {
    switch (ReservationCalendar.currentView) {
        case 'month':
            ReservationCalendar.currentMonth += direction;
            if (ReservationCalendar.currentMonth > 12) {
                ReservationCalendar.currentMonth = 1;
                ReservationCalendar.currentYear++;
            } else if (ReservationCalendar.currentMonth < 1) {
                ReservationCalendar.currentMonth = 12;
                ReservationCalendar.currentYear--;
            }
            break;
    }
    
    ReservationCalendar.updatePeriodDisplay();
    loadCalendar();
}

/**
 * Aller à aujourd'hui
 */
function goToToday() {
    var today = new Date();
    ReservationCalendar.currentYear = today.getFullYear();
    ReservationCalendar.currentMonth = today.getMonth() + 1;
    
    ReservationCalendar.updatePeriodDisplay();
    loadCalendar();
}

/**
 * Mettre à jour l'affichage de la période
 */
ReservationCalendar.updatePeriodDisplay = function() {
    var display = '';
    
    switch (this.currentView) {
        case 'month':
            var monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                             'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
            display = monthNames[this.currentMonth - 1] + ' ' + this.currentYear;
            break;
        case 'week':
            display = 'Semaine - ' + this.currentYear;
            break;
        case 'day':
            display = 'Jour - ' + this.currentYear;
            break;
    }
    
    $('#current-period').val(display);
};

/**
 * Fonctions utilitaires
 */
function formatDate(date) {
    return date.getFullYear() + '-' + 
           String(date.getMonth() + 1).padStart(2, '0') + '-' +
           String(date.getDate()).padStart(2, '0');
}

function formatDateTime(datetime) {
    return new Date(datetime).toLocaleString('fr-FR');
}

function isDateToday(date) {
    var today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
}

function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function getStatusLabelClass(status) {
    switch (status) {
        case 0: return 'label-warning';
        case 1: return 'label-info';
        case 2: return 'label-success';
        case 3: return 'label-danger';
        case 4: return 'label-default';
        default: return 'label-default';
    }
}