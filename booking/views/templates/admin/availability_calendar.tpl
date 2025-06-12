<div class="panel">
    <div class="panel-heading">
        <i class="icon-calendar"></i> Calendrier des Disponibilités
    </div>
    <div class="panel-body">
        
        <!-- Contrôles du calendrier -->
        <div class="calendar-controls row mb-3">
            <div class="col-md-3">
                <label for="booker-select">Sélectionner un élément :</label>
                <select id="booker-select" class="form-control">
                    <option value="">-- Tous les éléments --</option>
                    {foreach from=$bookers item=booker}
                        <option value="{$booker.id_booker}">{$booker.name}</option>
                    {/foreach}
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="calendar-view">Vue :</label>
                <select id="calendar-view" class="form-control">
                    <option value="month">Mois</option>
                    <option value="week">Semaine</option>
                    <option value="day">Jour</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="date-navigation">Navigation :</label>
                <div class="input-group">
                    <button id="prev-period" class="btn btn-default">
                        <i class="icon-chevron-left"></i>
                    </button>
                    <input type="text" id="current-period" class="form-control text-center" readonly>
                    <button id="next-period" class="btn btn-default">
                        <i class="icon-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <label>&nbsp;</label>
                <div>
                    <button id="today-btn" class="btn btn-info">Aujourd'hui</button>
                    <button id="refresh-calendar" class="btn btn-default">
                        <i class="icon-refresh"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Outils de sélection multiple -->
        <div class="calendar-tools row mb-3">
            <div class="col-md-6">
                <div class="btn-group">
                    <button id="select-mode" class="btn btn-default" data-mode="single">
                        <i class="icon-mouse-pointer"></i> Sélection simple
                    </button>
                    <button id="multi-select-mode" class="btn btn-default" data-mode="multi">
                        <i class="icon-th"></i> Sélection multiple
                    </button>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="btn-group" id="bulk-actions" style="display: none;">
                    <button id="bulk-add-availability" class="btn btn-success">
                        <i class="icon-plus"></i> Ajouter disponibilité
                    </button>
                    <button id="bulk-remove-availability" class="btn btn-danger">
                        <i class="icon-minus"></i> Supprimer disponibilité
                    </button>
                </div>
            </div>
        </div>

        <!-- Calendrier principal -->
        <div id="calendar-container" class="calendar-container">
            <div id="calendar-loading" class="text-center">
                <i class="icon-spinner icon-spin"></i> Chargement du calendrier...
            </div>
            
            <div id="calendar-content" style="display: none;">
                <!-- Le contenu du calendrier sera injecté ici via JavaScript -->
            </div>
        </div>

        <!-- Légende -->
        <div class="calendar-legend mt-3">
            <h4>Légende :</h4>
            <div class="legend-items">
                <span class="legend-item">
                    <span class="legend-color availability-exists"></span>
                    Disponibilité existante
                </span>
                <span class="legend-item">
                    <span class="legend-color availability-selected"></span>
                    Sélectionné
                </span>
                <span class="legend-item">
                    <span class="legend-color availability-conflict"></span>
                    Conflit détecté
                </span>
                <span class="legend-item">
                    <span class="legend-color has-reservations"></span>
                    A des réservations
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter une disponibilité -->
<div class="modal fade" id="availability-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Ajouter une disponibilité</h4>
            </div>
            <div class="modal-body">
                <form id="availability-form">
                    <input type="hidden" id="availability-booker-id" name="booker_id">
                    
                    <div class="form-group">
                        <label for="availability-date-from">Date et heure de début :</label>
                        <input type="datetime-local" id="availability-date-from" name="date_from" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="availability-date-to">Date et heure de fin :</label>
                        <input type="datetime-local" id="availability-date-to" name="date_to" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="availability-recurring" name="recurring">
                            Répéter cette disponibilité
                        </label>
                    </div>
                    
                    <div class="form-group" id="recurring-options" style="display: none;">
                        <label for="recurring-pattern">Modèle de répétition :</label>
                        <select id="recurring-pattern" name="recurring_pattern" class="form-control">
                            <option value="">Sélectionner...</option>
                            <option value="daily">Quotidien</option>
                            <option value="weekly">Hebdomadaire</option>
                            <option value="monthly">Mensuel</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                <button type="button" id="save-availability" class="btn btn-primary">Sauvegarder</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour sélection multiple -->
<div class="modal fade" id="bulk-availability-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Gestion en lot des disponibilités</h4>
            </div>
            <div class="modal-body">
                <form id="bulk-availability-form">
                    <input type="hidden" id="bulk-booker-id" name="booker_id">
                    <input type="hidden" id="bulk-action" name="action">
                    
                    <div class="alert alert-info">
                        <span id="selected-dates-count">0</span> date(s) sélectionnée(s)
                    </div>
                    
                    <div id="bulk-add-options" style="display: none;">
                        <div class="form-group">
                            <label for="bulk-time-from">Heure de début :</label>
                            <input type="time" id="bulk-time-from" name="time_from" class="form-control" value="08:00">
                        </div>
                        
                        <div class="form-group">
                            <label for="bulk-time-to">Heure de fin :</label>
                            <input type="time" id="bulk-time-to" name="time_to" class="form-control" value="18:00">
                        </div>
                    </div>
                    
                    <div id="bulk-remove-options" style="display: none;">
                        <div class="alert alert-warning">
                            <strong>Attention :</strong> Cette action supprimera toutes les disponibilités pour les dates sélectionnées.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                <button type="button" id="confirm-bulk-action" class="btn btn-primary">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables globales
    window.AvailabilityCalendar = {
        ajaxUrl: '{$ajax_url}',
        currentBookerId: '',
        currentYear: {$current_year},
        currentMonth: {$current_month},
        currentView: 'month',
        selectionMode: 'single',
        selectedDates: []
    };
</script>

<style>
.calendar-container {
    min-height: 400px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
}

.calendar-controls .form-group {
    margin-bottom: 10px;
}

.calendar-tools {
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}

.calendar-legend {
    border-top: 1px solid #eee;
    padding-top: 15px;
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 3px;
    border: 1px solid #ccc;
}

.availability-exists {
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.availability-selected {
    background-color: #cce5ff;
    border-color: #80bdff;
}

.availability-conflict {
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.has-reservations {
    background-color: #fff3cd;
    border-color: #ffeaa7;
}

.calendar-day {
    position: relative;
    min-height: 100px;
    border: 1px solid #ddd;
    padding: 5px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.calendar-day:hover {
    background-color: #f8f9fa;
}

.calendar-day.selected {
    background-color: #cce5ff;
    border-color: #80bdff;
}

.calendar-day.has-availability {
    background-color: #d4edda;
}

.calendar-day.has-conflict {
    background-color: #f8d7da;
}

.calendar-day.has-reservations {
    background-color: #fff3cd;
}

.day-number {
    font-weight: bold;
    margin-bottom: 5px;
}

.day-availability-count {
    font-size: 0.8em;
    color: #666;
}

.day-reservation-count {
    font-size: 0.8em;
    color: #856404;
}

.multi-select-mode .calendar-day {
    cursor: crosshair;
}

.btn-group .btn.active {
    background-color: #007cba;
    color: white;
}

@media (max-width: 768px) {
    .calendar-controls .col-md-3 {
        margin-bottom: 10px;
    }
    
    .legend-items {
        flex-direction: column;
        gap: 5px;
    }
}
</style>