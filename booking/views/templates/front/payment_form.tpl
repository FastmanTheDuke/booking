{extends file='page.tpl'}

{block name="page_title"}
    {l s='Paiement de votre réservation' d='Modules.Booking.Front'}
{/block}

{block name="page_content"}
<div class="payment-container">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                
                {* En-tête de confirmation *}
                <div class="payment-header">
                    <div class="text-center mb-4">
                        <i class="fa fa-check-circle fa-3x text-success"></i>
                        <h2 class="mt-3">{l s='Réservation confirmée !' d='Modules.Booking.Front'}</h2>
                        <p class="lead">{l s='Finalisez votre réservation en procédant au paiement' d='Modules.Booking.Front'}</p>
                    </div>
                </div>

                {* Détails de la réservation *}
                <div class="reservation-summary">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="fa fa-calendar"></i>
                                {l s='Résumé de votre réservation' d='Modules.Booking.Front'}
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>{l s='Informations de réservation' d='Modules.Booking.Front'}</h5>
                                    <dl class="row">
                                        <dt class="col-sm-5">{l s='Référence :' d='Modules.Booking.Front'}</dt>
                                        <dd class="col-sm-7">
                                            <strong class="text-primary">#{$reservation->booking_reference}</strong>
                                        </dd>
                                        
                                        <dt class="col-sm-5">{l s='Élément :' d='Modules.Booking.Front'}</dt>
                                        <dd class="col-sm-7">{$booker->name|escape:'html':'UTF-8'}</dd>
                                        
                                        <dt class="col-sm-5">{l s='Date :' d='Modules.Booking.Front'}</dt>
                                        <dd class="col-sm-7">
                                            {$reservation->date_reserved|date_format:'%A %d %B %Y'}
                                        </dd>
                                        
                                        <dt class="col-sm-5">{l s='Horaires :' d='Modules.Booking.Front'}</dt>
                                        <dd class="col-sm-7">
                                            {$reservation->hour_from|string_format:"%02d"}h00 - {$reservation->hour_to|string_format:"%02d"}h00
                                        </dd>
                                        
                                        <dt class="col-sm-5">{l s='Statut :' d='Modules.Booking.Front'}</dt>
                                        <dd class="col-sm-7">
                                            <span class="badge badge-success">
                                                {l s='Confirmée' d='Modules.Booking.Front'}
                                            </span>
                                        </dd>
                                    </dl>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>{l s='Informations client' d='Modules.Booking.Front'}</h5>
                                    <dl class="row">
                                        <dt class="col-sm-5">{l s='Nom :' d='Modules.Booking.Front'}</dt>
                                        <dd class="col-sm-7">
                                            {$reservation->customer_firstname} {$reservation->customer_lastname}
                                        </dd>
                                        
                                        <dt class="col-sm-5">{l s='Email :' d='Modules.Booking.Front'}</dt>
                                        <dd class="col-sm-7">{$reservation->customer_email}</dd>
                                        
                                        {if $reservation->customer_phone}
                                        <dt class="col-sm-5">{l s='Téléphone :' d='Modules.Booking.Front'}</dt>
                                        <dd class="col-sm-7">{$reservation->customer_phone}</dd>
                                        {/if}
                                    </dl>
                                    
                                    {if $reservation->customer_message}
                                    <h6>{l s='Votre message :' d='Modules.Booking.Front'}</h6>
                                    <p class="text-muted font-italic">"{$reservation->customer_message|escape:'html':'UTF-8'}"</p>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {* Détails de facturation *}
                <div class="payment-details">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h4 class="mb-0">
                                <i class="fa fa-credit-card"></i>
                                {l s='Détails du paiement' d='Modules.Booking.Front'}
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="payment-breakdown">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{l s='Réservation - %s' sprintf=[$booker->name] d='Modules.Booking.Front'}</span>
                                            <span>{$reservation->total_price|string_format:"%.2f"}€</span>
                                        </div>
                                        
                                        {if $reservation->deposit_amount > 0}
                                        <div class="d-flex justify-content-between mb-2 text-warning">
                                            <span>
                                                {l s='Caution (remboursée après utilisation)' d='Modules.Booking.Front'}
                                                <i class="fa fa-info-circle" 
                                                   data-toggle="tooltip" 
                                                   title="{l s='Cette caution sera automatiquement remboursée après votre réservation si aucun dommage n\'est constaté' d='Modules.Booking.Front'}"></i>
                                            </span>
                                            <span>{$reservation->deposit_amount|string_format:"%.2f"}€</span>
                                        </div>
                                        {/if}
                                        
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <strong class="h5">{l s='Total à payer :' d='Modules.Booking.Front'}</strong>
                                            <strong class="h5 text-primary">
                                                {($reservation->total_price + $reservation->deposit_amount)|string_format:"%.2f"}€
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="payment-security">
                                        <h6>{l s='Paiement sécurisé' d='Modules.Booking.Front'}</h6>
                                        <div class="security-badges">
                                            <img src="{$urls.img_url}stripe-badge.png" alt="Stripe" class="img-fluid mb-2">
                                            <div class="d-flex">
                                                <i class="fa fa-lock text-success me-2"></i>
                                                <small class="text-muted">
                                                    {l s='Paiement 100% sécurisé par SSL' d='Modules.Booking.Front'}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {* Actions de paiement *}
                <div class="payment-actions text-center">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{$payment_url}" class="btn btn-primary btn-lg btn-block">
                                <i class="fa fa-credit-card"></i>
                                {l s='Payer maintenant' d='Modules.Booking.Front'}
                                <br>
                                <small>{($reservation->total_price + $reservation->deposit_amount)|string_format:"%.2f"}€</small>
                            </a>
                            <small class="text-muted d-block mt-2">
                                {l s='Cartes acceptées : Visa, Mastercard, American Express' d='Modules.Booking.Front'}
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <a href="{$urls.pages.contact}" class="btn btn-outline-secondary btn-lg btn-block">
                                <i class="fa fa-envelope"></i>
                                {l s='Payer plus tard' d='Modules.Booking.Front'}
                                <br>
                                <small>{l s='Nous contacter' d='Modules.Booking.Front'}</small>
                            </a>
                            <small class="text-muted d-block mt-2">
                                {l s='Vous pouvez également nous contacter pour un paiement différé' d='Modules.Booking.Front'}
                            </small>
                        </div>
                    </div>
                </div>

                {* Informations importantes *}
                <div class="payment-info">
                    <div class="alert alert-info">
                        <h5 class="alert-heading">
                            <i class="fa fa-info-circle"></i>
                            {l s='Informations importantes' d='Modules.Booking.Front'}
                        </h5>
                        <ul class="mb-0">
                            <li>{l s='Votre réservation est confirmée et votre créneau est bloqué' d='Modules.Booking.Front'}</li>
                            <li>{l s='Le paiement finalise définitivement votre réservation' d='Modules.Booking.Front'}</li>
                            <li>{l s='Vous recevrez un email de confirmation après le paiement' d='Modules.Booking.Front'}</li>
                            <li>{l s='Annulation possible jusqu\'à 24h avant (remboursement intégral)' d='Modules.Booking.Front'}</li>
                            {if $reservation->deposit_amount > 0}
                            <li class="text-warning">
                                <strong>{l s='La caution sera remboursée automatiquement sous 7 jours après votre réservation' d='Modules.Booking.Front'}</strong>
                            </li>
                            {/if}
                        </ul>
                    </div>
                </div>

                {* Délai de paiement *}
                <div class="payment-deadline">
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-clock fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-1">{l s='Délai de paiement' d='Modules.Booking.Front'}</h6>
                                <p class="mb-0">
                                    {l s='Merci de procéder au paiement dans les 48 heures pour confirmer définitivement votre réservation.' d='Modules.Booking.Front'}
                                    <br>
                                    <small class="text-muted">
                                        {l s='Passé ce délai, votre réservation pourra être annulée automatiquement.' d='Modules.Booking.Front'}
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {* Support client *}
                <div class="customer-support text-center">
                    <hr>
                    <h6>{l s='Besoin d\'aide ?' d='Modules.Booking.Front'}</h6>
                    <p>
                        {l s='Notre équipe est à votre disposition :' d='Modules.Booking.Front'}
                        <br>
                        <i class="fa fa-phone"></i> <a href="tel:{Configuration::get('PS_SHOP_PHONE')}">{Configuration::get('PS_SHOP_PHONE')}</a>
                        |
                        <i class="fa fa-envelope"></i> <a href="mailto:{Configuration::get('PS_SHOP_EMAIL')}">{Configuration::get('PS_SHOP_EMAIL')}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-container {
    padding: 40px 0;
    background: #f8f9fa;
    min-height: 80vh;
}

.payment-header i {
    color: #28a745;
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 8px;
}

.card-header {
    border-radius: 8px 8px 0 0 !important;
}

.payment-breakdown {
    font-size: 1.1rem;
}

.security-badges img {
    max-height: 40px;
}

.btn-lg {
    padding: 15px 30px;
    font-size: 1.1rem;
}

.payment-actions .btn {
    transition: all 0.3s ease;
}

.payment-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.alert {
    border-radius: 8px;
}

dl.row dt {
    font-weight: 600;
    color: #495057;
}

dl.row dd {
    color: #6c757d;
}

@media (max-width: 768px) {
    .payment-container {
        padding: 20px 0;
    }
    
    .col-md-6 {
        margin-bottom: 15px;
    }
    
    .btn-block {
        width: 100%;
    }
}
</style>

<script>
$(document).ready(function() {
    // Initialiser les tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Compter à rebours pour le délai de paiement (optionnel)
    // À implémenter selon vos besoins
});
</script>
{/block}