/**
 * JavaScript pour l'interface de réservation côté client
 */
$(document).ready(function() {
    
    // Initialisation
    BookingApp.init();
    
    // Configuration des gestionnaires d'événements
    setupEventHandlers();
    
    // Charger l'état initial si des paramètres sont présents
    if (window.BookingConfig.selectedBookerId) {
        selectBooker(window.BookingConfig.selectedBookerId);
    }
});

/**
 * Objet principal de l'application de réservation
 */
window.BookingApp = {
    selectedBooker: null,
    selectedDate: null,
    selectedSlot: null,
    availableSlots: [],
    currentPrice: 0,
    
    init: function() {
        this.ajaxUrl = window.BookingConfig.ajaxUrl;
        this.minDate = window.BookingConfig.minDate;
        this.maxDate = window.BookingConfig.maxDate;
        this.currentStep = window.BookingConfig.currentStep || 'selection';
        
        // Initialiser les validations
        this.initFormValidation();
        
        // Masquer les sections non nécessaires
        this.showStep(this.currentStep);
    },
    
    showStep: function(step) {
        $('.booking-section').hide();
        
        switch(step) {
            case 'selection':
                $('.booker-selection').show();
                break;
            case 'reservation':
                $('.reservation-form').show();
                break;
            case 'confirmation':
                $('.confirmation-section').show();
                break;
        }
        
        this.currentStep = step;
    },
    
    initFormValidation: function() {
        // Validation en temps réel
        $('#customer-email').on('blur', function() {
            validateEmail($(this).val(), $(this));
        });
        
        $('#customer-phone').on('blur', function() {
            validatePhone($(this).val(), $(this));
        });
        
        // Activer/désactiver le bouton de soumission
        $('#booking-form input, #booking-form select, #booking-form textarea').on('change keyup', function() {
            updateSubmitButton();
        });
        
        $('#accept-conditions').on('change', function() {
            updateSubmitButton();
        });
    }
};

/**
 * Configuration des gestionnaires d'événements
 */
function setupEventHandlers() {
    
    // Sélection d'un booker
    $('.select-booker-btn').on('click', function(e) {
        e.preventDefault();
        var bookerId = $(this).closest('.booker-card').data('booker-id');
        selectBooker(bookerId);
    });
    
    // Clic sur une carte booker
    $('.booker-card:not(.unavailable)').on('click', function(e) {
        if (!$(e.target).hasClass('select-booker-btn')) {
            var bookerId = $(this).data('booker-id');
            selectBooker(bookerId);
        }
    });
    
    // Retour à la sélection
    $('.back-to-selection').on('click', function() {
        BookingApp.showStep('selection');
        resetForm();
    });
    
    // Changement de date
    $('#booking-date').on('change', function() {
        var date = $(this).val();
        if (date && BookingApp.selectedBooker) {
            loadAvailableSlots(BookingApp.selectedBooker.id, date);
        }
    });
    
    // Sélection d'un créneau
    $('#time-slots').on('change', function() {
        var slotValue = $(this).val();
        if (slotValue) {
            selectTimeSlot(slotValue);
        } else {
            clearPriceSummary();
        }
    });
    
    // Soumission du formulaire
    $('#booking-form').on('submit', function(e) {
        e.preventDefault();
        submitReservation();
    });
    
    // Reset du formulaire
    $('#reset-form').on('click', function() {
        resetForm();
    });
    
    // Modal des conditions
    $('#accept-conditions-btn').on('click', function() {
        $('#accept-conditions').prop('checked', true);
        updateSubmitButton();
    });
    
    // Gestion des erreurs AJAX globales
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        hideLoading();
        if (xhr.responseJSON && xhr.responseJSON.error) {
            showError(xhr.responseJSON.error);
        } else {
            showError('Une erreur est survenue. Veuillez réessayer.');
        }
    });
}

/**
 * Sélectionner un booker
 */
function selectBooker(bookerId) {
    showLoading();
    
    $.ajax({
        url: BookingApp.ajaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'getBookerInfo',
            booker_id: bookerId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                BookingApp.selectedBooker = response.booker;
                displaySelectedBooker(response.booker);
                BookingApp.showStep('reservation');
                
                // Pré-sélectionner la date si fournie
                if (window.BookingConfig.selectedDate) {
                    $('#booking-date').val(window.BookingConfig.selectedDate);
                    loadAvailableSlots(bookerId, window.BookingConfig.selectedDate);
                }
            } else {
                showError(response.error || 'Erreur lors de la sélection');
            }
        },
        complete: function() {
            hideLoading();
        }
    });
}

/**
 * Afficher les informations du booker sélectionné
 */
function displaySelectedBooker(booker) {
    $('#selected-booker-id').val(booker.id);
    $('#selected-booker-image').attr('src', booker.image_url).attr('alt', booker.name);
    $('#selected-booker-name').text(booker.name);
    $('#selected-booker-description').text(booker.description);
    $('#selected-booker-price').text(booker.base_price.toFixed(2));
    
    // Configurer les dates disponibles
    if (booker.available_days && booker.available_days.length > 0) {
        // Vous pouvez ici implémenter une logique pour désactiver les dates non disponibles
        // Pour l'instant, on se contente des attributs min/max
    }
}

/**
 * Charger les créneaux disponibles pour une date
 */
function loadAvailableSlots(bookerId, date) {
    var $slotsSelect = $('#time-slots');
    var $slotsLoading = $('#slots-loading');
    
    // État de chargement
    $slotsSelect.prop('disabled', true).html('<option value="">Chargement...</option>');
    $slotsLoading.show();
    clearPriceSummary();
    
    $.ajax({
        url: BookingApp.ajaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'getAvailableSlots',
            booker_id: bookerId,
            date: date
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                BookingApp.availableSlots = response.slots;
                BookingApp.selectedDate = date;
                populateTimeSlots(response.slots);
            } else {
                $slotsSelect.html('<option value="">Aucun créneau disponible</option>');
                showError(response.error || 'Aucun créneau disponible pour cette date');
            }
        },
        error: function() {
            $slotsSelect.html('<option value="">Erreur de chargement</option>');
        },
        complete: function() {
            $slotsLoading.hide();
        }
    });
}

/**
 * Peupler la liste des créneaux horaires
 */
function populateTimeSlots(slots) {
    var $slotsSelect = $('#time-slots');
    
    if (slots && slots.length > 0) {
        var options = '<option value="">Choisir un créneau</option>';
        
        slots.forEach(function(slot) {
            var value = slot.hour_from + '-' + slot.hour_to;
            var label = slot.label + ' (' + slot.price.toFixed(2) + '€)';
            options += '<option value="' + value + '" data-price="' + slot.price + '" data-duration="' + slot.duration + '">' + label + '</option>';
        });
        
        $slotsSelect.html(options).prop('disabled', false);
    } else {
        $slotsSelect.html('<option value="">Aucun créneau disponible</option>').prop('disabled', true);
    }
}

/**
 * Sélectionner un créneau horaire
 */
function selectTimeSlot(slotValue) {
    var $selectedOption = $('#time-slots option:selected');
    var price = parseFloat($selectedOption.data('price')) || 0;
    var duration = parseInt($selectedOption.data('duration')) || 0;
    
    BookingApp.selectedSlot = {
        value: slotValue,
        price: price,
        duration: duration
    };
    
    BookingApp.currentPrice = price;
    
    // Afficher le résumé des prix
    showPriceSummary(duration, price);
    
    // Mettre à jour le bouton de soumission
    updateSubmitButton();
}

/**
 * Afficher le résumé des prix
 */
function showPriceSummary(duration, price) {
    $('#booking-duration').text(duration + 'h');
    $('#booking-total-price').text(price.toFixed(2) + '€');
    $('#price-summary').show();
}

/**
 * Masquer le résumé des prix
 */
function clearPriceSummary() {
    $('#price-summary').hide();
    BookingApp.currentPrice = 0;
    BookingApp.selectedSlot = null;
    updateSubmitButton();
}

/**
 * Soumettre la réservation
 */
function submitReservation() {
    if (!validateForm()) {
        return;
    }
    
    showLoading();
    
    var formData = {
        ajax: true,
        action: 'createReservation',
        booker_id: $('#selected-booker-id').val(),
        date: $('#booking-date').val(),
        hour_from: BookingApp.selectedSlot.value.split('-')[0],
        hour_to: BookingApp.selectedSlot.value.split('-')[1],
        customer_name: $('#customer-name').val(),
        customer_email: $('#customer-email').val(),
        customer_phone: $('#customer-phone').val(),
        notes: $('#customer-notes').val()
    };
    
    $.ajax({
        url: BookingApp.ajaxUrl,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showConfirmation(formData, response.reservation_id);
            } else {
                showError(response.error || 'Erreur lors de la réservation');
            }
        },
        complete: function() {
            hideLoading();
        }
    });
}

/**
 * Afficher la confirmation
 */
function showConfirmation(formData, reservationId) {
    // Remplir le récapitulatif
    var summaryHtml = '<div class="summary-item"><strong>Élément:</strong> ' + BookingApp.selectedBooker.name + '</div>';
    summaryHtml += '<div class="summary-item"><strong>Date:</strong> ' + formatDate(formData.date) + '</div>';
    summaryHtml += '<div class="summary-item"><strong>Horaire:</strong> ' + formData.hour_from + 'h00 - ' + formData.hour_to + 'h00</div>';
    summaryHtml += '<div class="summary-item"><strong>Durée:</strong> ' + BookingApp.selectedSlot.duration + 'h</div>';
    summaryHtml += '<div class="summary-item"><strong>Prix:</strong> ' + BookingApp.currentPrice.toFixed(2) + '€</div>';
    summaryHtml += '<div class="summary-item"><strong>Client:</strong> ' + formData.customer_name + '</div>';
    summaryHtml += '<div class="summary-item"><strong>Email:</strong> ' + formData.customer_email + '</div>';
    if (formData.customer_phone) {
        summaryHtml += '<div class="summary-item"><strong>Téléphone:</strong> ' + formData.customer_phone + '</div>';
    }
    if (formData.notes) {
        summaryHtml += '<div class="summary-item"><strong>Notes:</strong> ' + formData.notes + '</div>';
    }
    summaryHtml += '<div class="summary-item"><strong>Numéro de réservation:</strong> #' + reservationId + '</div>';
    
    $('.summary-details').html(summaryHtml);
    
    // Afficher la section de confirmation
    BookingApp.showStep('confirmation');
    
    // Faire défiler vers le haut
    $('html, body').animate({
        scrollTop: $('.confirmation-section').offset().top - 50
    }, 500);
}

/**
 * Valider le formulaire
 */
function validateForm() {
    var errors = [];
    
    // Vérifications de base
    if (!BookingApp.selectedBooker) {
        errors.push('Aucun élément sélectionné');
    }
    
    if (!$('#booking-date').val()) {
        errors.push('Veuillez sélectionner une date');
    }
    
    if (!$('#time-slots').val()) {
        errors.push('Veuillez sélectionner un créneau');
    }
    
    if (!$('#customer-name').val().trim()) {
        errors.push('Le nom est obligatoire');
    }
    
    if (!$('#customer-email').val().trim()) {
        errors.push('L\'email est obligatoire');
    } else if (!isValidEmail($('#customer-email').val())) {
        errors.push('Email invalide');
    }
    
    var phone = $('#customer-phone').val().trim();
    if (phone && !isValidPhone(phone)) {
        errors.push('Numéro de téléphone invalide');
    }
    
    if (!$('#accept-conditions').is(':checked')) {
        errors.push('Vous devez accepter les conditions de réservation');
    }
    
    // Afficher les erreurs
    if (errors.length > 0) {
        showError(errors.join('<br>'));
        return false;
    }
    
    return true;
}

/**
 * Mettre à jour l'état du bouton de soumission
 */
function updateSubmitButton() {
    var $submitBtn = $('#submit-booking');
    var isValid = true;
    
    // Vérifications minimales
    if (!BookingApp.selectedBooker || 
        !$('#booking-date').val() || 
        !$('#time-slots').val() ||
        !$('#customer-name').val().trim() ||
        !$('#customer-email').val().trim() ||
        !$('#accept-conditions').is(':checked')) {
        isValid = false;
    }
    
    $submitBtn.prop('disabled', !isValid);
    
    if (isValid) {
        $submitBtn.removeClass('btn-secondary').addClass('btn-primary');
    } else {
        $submitBtn.removeClass('btn-primary').addClass('btn-secondary');
    }
}

/**
 * Réinitialiser le formulaire
 */
function resetForm() {
    $('#booking-form')[0].reset();
    $('#time-slots').html('<option value="">Choisir d\'abord une date</option>').prop('disabled', true);
    clearPriceSummary();
    BookingApp.selectedDate = null;
    BookingApp.selectedSlot = null;
    BookingApp.currentPrice = 0;
    updateSubmitButton();
    hideAllMessages();
}

/**
 * Fonctions de validation
 */
function validateEmail(email, $field) {
    if (email && !isValidEmail(email)) {
        showFieldError($field, 'Email invalide');
        return false;
    } else {
        clearFieldError($field);
        return true;
    }
}

function validatePhone(phone, $field) {
    if (phone && !isValidPhone(phone)) {
        showFieldError($field, 'Numéro invalide');
        return false;
    } else {
        clearFieldError($field);
        return true;
    }
}

function isValidEmail(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function isValidPhone(phone) {
    var re = /^[\d\s\-\+\(\)\.]{10,}$/;
    return re.test(phone.replace(/\s/g, ''));
}

/**
 * Gestion des messages d'erreur sur les champs
 */
function showFieldError($field, message) {
    clearFieldError($field);
    $field.addClass('is-invalid');
    $field.after('<div class="invalid-feedback">' + message + '</div>');
}

function clearFieldError($field) {
    $field.removeClass('is-invalid');
    $field.siblings('.invalid-feedback').remove();
}

/**
 * Gestion des messages globaux
 */
function showError(message) {
    hideAllMessages();
    
    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show booking-alert" role="alert">' +
                   '<i class="fas fa-exclamation-circle"></i> ' + message +
                   '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                   '<span aria-hidden="true">&times;</span>' +
                   '</button>' +
                   '</div>';
    
    $('.booking-container').prepend(alertHtml);
    
    // Faire défiler vers le haut
    $('html, body').animate({
        scrollTop: $('.booking-alert').offset().top - 20
    }, 300);
    
    // Auto-hide après 10 secondes
    setTimeout(function() {
        $('.booking-alert').alert('close');
    }, 10000);
}

function showSuccess(message) {
    hideAllMessages();
    
    var alertHtml = '<div class="alert alert-success alert-dismissible fade show booking-alert" role="alert">' +
                   '<i class="fas fa-check-circle"></i> ' + message +
                   '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                   '<span aria-hidden="true">&times;</span>' +
                   '</button>' +
                   '</div>';
    
    $('.booking-container').prepend(alertHtml);
    
    // Auto-hide après 5 secondes
    setTimeout(function() {
        $('.booking-alert').alert('close');
    }, 5000);
}

function hideAllMessages() {
    $('.booking-alert').remove();
}

/**
 * Gestion du loading
 */
function showLoading(message) {
    var loadingMessage = message || 'Traitement en cours...';
    $('#loading-overlay p').text(loadingMessage);
    $('#loading-overlay').show();
}

function hideLoading() {
    $('#loading-overlay').hide();
}

/**
 * Fonctions utilitaires
 */
function formatDate(dateStr) {
    var date = new Date(dateStr);
    var options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        weekday: 'long'
    };
    return date.toLocaleDateString('fr-FR', options);
}

function formatPrice(price) {
    return parseFloat(price).toFixed(2) + '€';
}

function formatTime(hour) {
    return String(hour).padStart(2, '0') + ':00';
}

// Gestion des erreurs JavaScript globales
window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript:', e.error);
    // Ne pas afficher d'erreur à l'utilisateur pour les erreurs JS non critiques
});

// Auto-save des données du formulaire dans le localStorage
function saveFormData() {
    if (typeof(Storage) !== "undefined") {
        var formData = {
            booker_id: $('#selected-booker-id').val(),
            date: $('#booking-date').val(),
            time_slot: $('#time-slots').val(),
            customer_name: $('#customer-name').val(),
            customer_email: $('#customer-email').val(),
            customer_phone: $('#customer-phone').val(),
            notes: $('#customer-notes').val(),
            timestamp: Date.now()
        };
        
        localStorage.setItem('booking_form_data', JSON.stringify(formData));
    }
}

function loadFormData() {
    if (typeof(Storage) !== "undefined") {
        var savedData = localStorage.getItem('booking_form_data');
        if (savedData) {
            try {
                var formData = JSON.parse(savedData);
                
                // Vérifier que les données ne sont pas trop anciennes (1 heure max)
                if (Date.now() - formData.timestamp < 3600000) {
                    // Restaurer les données non sensibles
                    if (formData.customer_name) $('#customer-name').val(formData.customer_name);
                    if (formData.customer_email) $('#customer-email').val(formData.customer_email);
                    if (formData.customer_phone) $('#customer-phone').val(formData.customer_phone);
                }
            } catch (e) {
                // Ignorer les erreurs de parsing
            }
        }
    }
}

function clearSavedFormData() {
    if (typeof(Storage) !== "undefined") {
        localStorage.removeItem('booking_form_data');
    }
}

// Sauvegarder automatiquement les données du formulaire
$(document).on('change keyup', '#booking-form input, #booking-form textarea', function() {
    // Debounce pour éviter trop d'appels
    clearTimeout(window.saveFormTimeout);
    window.saveFormTimeout = setTimeout(saveFormData, 1000);
});

// Charger les données sauvegardées au chargement de la page
$(document).ready(function() {
    loadFormData();
});

// Nettoyer les données sauvegardées lors de la soumission réussie
$(document).on('booking-success', function() {
    clearSavedFormData();
});