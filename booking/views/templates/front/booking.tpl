{extends file='page.tpl'}

{block name="page_title"}
    Réservation en ligne
{/block}

{block name="page_content"}
<div class="booking-container">
    
    <!-- Section de sélection des éléments -->
    <div class="booking-section booker-selection">
        <h2>Choisissez votre réservation</h2>
        
        {if $bookers && count($bookers) > 0}
            <div class="bookers-grid">
                {foreach from=$bookers item=booker}
                    <div class="booker-card {if !$booker.has_availability}unavailable{/if}" 
                         data-booker-id="{$booker.id_booker}">
                        
                        <div class="booker-image">
                            <img src="{$booker.image_url}" alt="{$booker.name}" loading="lazy">
                            {if !$booker.has_availability}
                                <div class="unavailable-overlay">
                                    <span>Indisponible</span>
                                </div>
                            {/if}
                        </div>
                        
                        <div class="booker-info">
                            <h3>{$booker.name}</h3>
                            {if $booker.description}
                                <p class="booker-description">{$booker.description|truncate:100}</p>
                            {/if}
                            
                            <div class="booker-price">
                                À partir de <span class="price">{$booker.base_price|string_format:"%.2f"}€</span>
                            </div>
                            
                            {if $booker.has_availability}
                                <button type="button" class="btn btn-primary select-booker-btn">
                                    Sélectionner
                                </button>
                            {else}
                                <button type="button" class="btn btn-secondary" disabled>
                                    Indisponible
                                </button>
                            {/if}
                        </div>
                    </div>
                {/foreach}
            </div>
        {else}
            <div class="alert alert-info">
                Aucun élément disponible pour le moment.
            </div>
        {/if}
    </div>
    
    <!-- Section de réservation -->
    <div class="booking-section reservation-form" id="reservation-section" style="display: none;">
        <div class="section-header">
            <h2>Détails de votre réservation</h2>
            <button type="button" class="btn btn-link back-to-selection">
                ← Changer de sélection
            </button>
        </div>
        
        <div class="reservation-content">
            <!-- Informations sur l'élément sélectionné -->
            <div class="selected-booker-info">
                <div class="booker-summary">
                    <img id="selected-booker-image" src="" alt="">
                    <div class="booker-details">
                        <h3 id="selected-booker-name"></h3>
                        <p id="selected-booker-description"></p>
                        <div class="price-info">
                            Prix de base: <span id="selected-booker-price"></span>€
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulaire de réservation -->
            <form id="booking-form" class="booking-form">
                <input type="hidden" id="selected-booker-id" name="booker_id" value="">
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="booking-date">Date souhaitée *</label>
                        <input type="date" 
                               id="booking-date" 
                               name="date" 
                               class="form-control" 
                               min="{$min_date}" 
                               max="{$max_date}"
                               required>
                        <small class="form-text text-muted">
                            Sélectionnez une date pour voir les créneaux disponibles
                        </small>
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="time-slots">Créneaux disponibles</label>
                        <select id="time-slots" name="time_slot" class="form-control" disabled required>
                            <option value="">Choisir d'abord une date</option>
                        </select>
                        <div id="slots-loading" class="text-center" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Chargement des créneaux...
                        </div>
                    </div>
                </div>
                
                <!-- Affichage du prix -->
                <div class="price-summary" id="price-summary" style="display: none;">
                    <div class="price-details">
                        <div class="price-line">
                            <span>Durée:</span>
                            <span id="booking-duration"></span>
                        </div>
                        <div class="price-line total">
                            <span>Total:</span>
                            <span id="booking-total-price"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Informations client -->
                <div class="customer-info-section">
                    <h4>Vos informations</h4>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="customer-name">Nom complet *</label>
                            <input type="text" 
                                   id="customer-name" 
                                   name="customer_name" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="customer-email">Email *</label>
                            <input type="email" 
                                   id="customer-email" 
                                   name="customer_email" 
                                   class="form-control" 
                                   required>
                            <small class="form-text text-muted">
                                Vous recevrez une confirmation par email
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="customer-phone">Téléphone</label>
                            <input type="tel" 
                                   id="customer-phone" 
                                   name="customer_phone" 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="customer-notes">Notes ou demandes spéciales</label>
                            <textarea id="customer-notes" 
                                      name="notes" 
                                      class="form-control" 
                                      rows="3" 
                                      placeholder="Informations complémentaires..."></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Conditions -->
                <div class="booking-conditions">
                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               id="accept-conditions" 
                               required>
                        <label class="form-check-label" for="accept-conditions">
                            J'accepte les <a href="#" data-toggle="modal" data-target="#conditions-modal">conditions de réservation</a> *
                        </label>
                    </div>
                </div>
                
                <!-- Boutons d'action -->
                <div class="booking-actions">
                    <button type="button" class="btn btn-secondary" id="reset-form">
                        Recommencer
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg" id="submit-booking" disabled>
                        <i class="fas fa-calendar-check"></i>
                        Confirmer la réservation
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Section de confirmation -->
    <div class="booking-section confirmation-section" id="confirmation-section" style="display: none;">
        <div class="confirmation-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Demande de réservation envoyée !</h2>
            <p class="confirmation-message">
                Votre demande de réservation a été enregistrée avec succès. 
                Vous recevrez une confirmation par email dans les plus brefs délais.
            </p>
            
            <div class="reservation-summary">
                <h4>Récapitulatif de votre demande :</h4>
                <div class="summary-details">
                    <!-- Détails remplis via JavaScript -->
                </div>
            </div>
            
            <div class="next-steps">
                <h4>Prochaines étapes :</h4>
                <ol>
                    <li>Nous examinerons votre demande</li>
                    <li>Vous recevrez une confirmation par email</li>
                    <li>Un lien de paiement vous sera envoyé si la réservation est acceptée</li>
                </ol>
            </div>
            
            <div class="confirmation-actions">
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    Faire une nouvelle réservation
                </button>
                <a href="{$link->getPageLink('index')}" class="btn btn-secondary">
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal des conditions -->
<div class="modal fade" id="conditions-modal" tabindex="-1" role="dialog" aria-labelledby="conditions-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="conditions-modal-title">Conditions de réservation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>Conditions générales :</h6>
                <ul>
                    <li>Les réservations sont soumises à validation</li>
                    <li>Le paiement sera demandé après acceptation de la réservation</li>
                    <li>Une caution peut être demandée</li>
                    <li>L'annulation doit se faire au moins 24h à l'avance</li>
                    <li>En cas d'annulation tardive, des frais peuvent s'appliquer</li>
                </ul>
                
                <h6>Modalités de paiement :</h6>
                <ul>
                    <li>Paiement sécurisé par carte bancaire</li>
                    <li>Possibilité de paiement par virement</li>
                    <li>Facture fournie après paiement</li>
                </ul>
                
                <h6>Responsabilité :</h6>
                <p>
                    Le client s'engage à utiliser l'élément réservé dans les règles de l'art 
                    et sera tenu responsable de tout dommage causé.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="accept-conditions-btn">
                    J'accepte ces conditions
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div class="loading-overlay" id="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="spinner-border" role="status"></div>
        <p>Traitement en cours...</p>
    </div>
</div>

<script>
// Variables globales pour JavaScript
window.BookingConfig = {
    ajaxUrl: '{$ajax_url}',
    selectedBookerId: {$selected_booker|default:0},
    selectedDate: '{$selected_date|default:""}',
    minDate: '{$min_date}',
    maxDate: '{$max_date}',
    currentStep: 'selection'
};
</script>
{/block}

{block name="page_footer"}
<style>
/* Styles spécifiques à cette page */
.booking-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.booking-section {
    margin-bottom: 40px;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.bookers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.booker-card {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.booker-card:hover:not(.unavailable) {
    border-color: #007cba;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.booker-card.unavailable {
    opacity: 0.6;
    cursor: not-allowed;
}

.booker-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.booker-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.unavailable-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.booker-info {
    padding: 20px;
}

.booker-info h3 {
    margin-bottom: 10px;
    color: #333;
}

.booker-description {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.5;
}

.booker-price {
    font-size: 1.2em;
    margin-bottom: 15px;
}

.booker-price .price {
    font-weight: bold;
    color: #28a745;
}

.confirmation-section {
    text-align: center;
}

.success-icon {
    font-size: 4em;
    color: #28a745;
    margin-bottom: 20px;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-content {
    text-align: center;
}

@media (max-width: 768px) {
    .bookers-grid {
        grid-template-columns: 1fr;
    }
    
    .booking-container {
        padding: 10px;
    }
    
    .booking-section {
        padding: 20px;
    }
}
</style>
{/block}