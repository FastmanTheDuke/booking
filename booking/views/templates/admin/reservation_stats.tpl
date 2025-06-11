{**
 * Template pour afficher les statistiques des réservations
 *}
{if isset($reservation_stats) && count($reservation_stats) > 0}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-bar-chart"></i> Statistiques des réservations
    </div>
    <div class="panel-body">
        <div class="row">
            {foreach from=$reservation_stats item=stat}
                <div class="col-lg-3 col-md-6">
                    <div class="alert {if $stat.status_id == 0}alert-warning{elseif $stat.status_id == 1}alert-info{elseif $stat.status_id == 2}alert-success{else}alert-danger{/if}">
                        <div class="text-center">
                            <div style="font-size: 2em; font-weight: bold;">{$stat.count}</div>
                            <div>{$stat.label}</div>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
</div>
{/if}