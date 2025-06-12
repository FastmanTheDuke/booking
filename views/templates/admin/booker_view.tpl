{*
* Template pour la vue calendrier des réservations
* Affichage principal avec filtres et actions
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-calendar"></i>
        Calendrier des Réservations
        <span class="badge badge-info">
            {$stats.today_reservations} aujourd'hui
        </span>
    </div>
</div>

{* Statistiques rapides *}
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="alert alert-info text-center">
            <div style="font-size: 2em; font-weight: bold;">{$stats.today_reservations}</div>
            <div>Réservations aujourd'hui</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="alert alert-primary text-center">
            <div style="font-size: 2em; font-weight: bold;">{$stats.week_reservations}</div>
            <div>Cette semaine</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="alert alert-warning text-center">
            <div style="font-size: 2em; font-weight: bold;">{$stats.pending_reservations}</div>
            <div>En attente</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="alert alert-success text-center">
            <div style="font-size: 1.5em; font-weight: bold;">{$stats.revenue_today|string_format:"%.2f"}€</div>
            <div>CA aujourd'hui</div>
        </div>
    </div>
</div>

{* Barre d'outils et filtres *}
<div class="panel">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-8">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary" id="today-btn">
                        <i class="icon-home"></i> Aujourd'hui
                    </button>
                    <button type="button" class="btn btn-default" id="prev-btn">
                        <i class="icon-chevron-left"></i> Précédent
                    </button>
                    <button type="button" class="btn btn-default" id="next-btn">
                        Suivant <i class="icon-chevron-right"></i>
                    </button>
                </div>
                
                <div class="btn-group ml-2" role="group">
                    <button type="button" class="btn btn-default" data-view="dayGridMonth">Mois</button>
                    <button type="button" class="btn btn-info" data-view="timeGridWeek">Semaine</button>
                    <button type="button" class="btn btn-default" data-view="timeGridDay">Jour</button>
                </div>
                
                <button type="button" class="btn btn-success ml-2" id="new-reservation-btn">
                    <i class="icon-plus"></i> Nouvelle réservation
                </button>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="booker-filter">Filtrer par élément :</label>
                    <select class="form-control" id="booker-filter">
                        <option value="all">Tous les éléments</option>
                        {foreach from=$bookers item=booker}
                            <option value="{$booker.id_booker}">{$booker.name|escape:'html':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status-filter">Filtrer par statut :</label>
                    <select class="form-control" id="status-filter">
                        <option value="all">Tous les statuts</option>
                        {foreach from=$statuses key=status_id item=status_label}
                            <option value="{$status_id}">{$status_label|escape:'html':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

{* Actions en lot *}
<div class="panel panel-default" id="bulk-actions-panel" style="display: none;">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-8">
                <span id="selected-count">0</span> réservation(s) sélectionnée(s)
            </div>
            <div class="col-md-4 text-right">
                <div class="btn-group">
                    <button type="button" class="btn btn-success btn-sm" id="bulk-accept">
                        <i class="icon-check"></i> Accepter
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="bulk-refuse">
                        <i class="icon-times"></i> Refuser
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" id="bulk-delete">
                        <i class="icon-trash"></i> Supprimer
                    </button>
                </div>
                <button type="button" class="btn btn-default btn-sm ml-2" id="clear-selection">
                    Tout désélectionner
                </button>
            </div>
        </div>
    </div>
</div>

{* Calendrier principal *}
<div class="panel">
    <div class="panel-body">
        <div id="calendar"></div>
    </div>
</div>

{* Légende *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-info"></i> Légende
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3">
                <span class="label" style="background-color: #ffc107; color: #212529;">■</span>
                En attente
            </div>
            <div class="col-md-3">
                <span class="label" style="background-color: #17a2b8;">■</span>
                Acceptée
            </div>
            <div class="col-md-3">
                <span class="label" style="background-color: #28a745;">■</span>
                Payée
            </div>
            <div class="col-md-3">
                <span class="label" style="background-color: #dc3545;">■</span>
                Annulée/Expirée
            </div>
        </div>
    </div>
</div>

{* Modal pour création/édition de réservation *}
<div class="modal fade" id="reservation-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title" id="reservation-modal-title">Nouvelle réservation</h4>
            </div>
            
            <div class="modal-body">
                <form id="reservation-form">
                    <input type="hidden" id="reservation-id" name="reservation_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal-booker">Élément à réserver *</label>
                                <select class="form-control" id="modal-booker" name="booker_id" required>
                                    <option value="">Sélectionner un élément</option>
                                    {foreach from=$bookers item=booker}
                                        <option value="{$booker.id_booker}" data-price="{$booker.price|default:'50.00'}">
                                            {$booker.name|escape:'html':'UTF-8'}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="modal-date">Date *</label>
                                <input type="date" class="form-control" id="modal-date" name="date" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modal-hour-from">Heure de début *</label>
                                        <select class="form-control" id="modal-hour-from" name="hour_from" required>
                                            {for $hour=6 to 23}
                                                <option value="{$hour}">{$hour}h00</option>
                                            {/for}
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modal-hour-to">Heure de fin *</label>
                                        <select class="form-control" id="modal-hour-to" name="hour_to" required>
                                            {for $hour=7 to 24}
                                                <option value="{$hour}">{$hour}h00</option>
                                            {/for}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal-firstname">Prénom *</label>
                                <input type="text" class="form-control" id="modal-firstname" name="customer_firstname" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="modal-lastname">Nom *</label>
                                <input type="text" class="form-control" id="modal-lastname" name="customer_lastname" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="modal-email">Email</label>
                                <input type="email" class="form-control" id="modal-email" name="customer_email">
                            </div>
                            
                            <div class="form-group">
                                <label for="modal-phone">Téléphone</label>
                                <input type="tel" class="form-control" id="modal-phone" name="customer_phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal-status">Statut</label>
                                <select class="form-control" id="modal-status" name="status">
                                    {foreach from=$statuses key=status_id item=status_label}
                                        <option value="{$status_id}" {if $status_id == 0}selected{/if}>
                                            {$status_label|escape:'html':'UTF-8'}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal-price">Prix</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="modal-price" name="total_price">
                                    <span class="input-group-addon">€</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal-message">Message client</label>
                        <textarea class="form-control" id="modal-message" name="customer_message" rows="3"></textarea>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="delete-reservation-btn" style="display: none;">
                    <i class="icon-trash"></i> Supprimer
                </button>
                <button type="button" class="btn btn-primary" id="save-reservation-btn">
                    <i class="icon-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

{* Modal de confirmation pour actions en lot *}
<div class="modal fade" id="confirm-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title">Confirmation</h4>
            </div>
            <div class="modal-body" id="confirm-message">
                Êtes-vous sûr de vouloir effectuer cette action ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirm-action-btn">Confirmer</button>
            </div>
        </div>
    </div>
</div>

{* Configuration JavaScript *}
<script>
var BookingCalendar = {
    ajaxUrls: {$ajax_urls|json_encode},
    token: '{$token}',
    config: {$calendar_config|json_encode},
    currentDate: '{$current_date}',
    statuses: {$statuses|json_encode},
    paymentStatuses: {$payment_statuses|json_encode},
    selectedEvents: [],
    
    // Messages de traduction
    messages: {
        'confirm_delete': 'Êtes-vous sûr de vouloir supprimer cette réservation ?',
        'confirm_bulk_accept': 'Accepter les réservations sélectionnées ?',
        'confirm_bulk_refuse': 'Refuser les réservations sélectionnées ?',
        'confirm_bulk_delete': 'Supprimer les réservations sélectionnées ?',
        'no_selection': 'Aucune réservation sélectionnée',
        'loading': 'Chargement...',
        'error': 'Erreur lors de l\'opération',
        'success_create': 'Réservation créée avec succès',
        'success_update': 'Réservation mise à jour',
        'success_delete': 'Réservation supprimée',
        'validation_required': 'Veuillez remplir tous les champs obligatoires',
        'validation_time': 'L\'heure de fin doit être postérieure à l\'heure de début'
    }
};
</script>

{* Styles personnalisés *}
<style>
.fc-event-selected {
    border: 2px solid #007bff !important;
    box-shadow: 0 0 10px rgba(0,123,255,0.5) !important;
}

.fc-event:hover {
    transform: scale(1.02);
    transition: transform 0.2s;
}

.modal-lg {
    width: 90%;
    max-width: 800px;
}

.alert {
    border: none;
    border-radius: 8px;
}

.panel {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-group .btn {
    margin-right: 5px;
}

.fc-toolbar {
    margin-bottom: 20px;
}

.fc-event-time {
    font-weight: bold;
}

.fc-event-title {
    padding: 2px 4px;
}

#bulk-actions-panel {
    border-left: 4px solid #007bff;
    background-color: #f8f9fa;
}

.label {
    display: inline-block;
    width: 15px;
    height: 15px;
    margin-right: 5px;
    border-radius: 3px;
}
</style>