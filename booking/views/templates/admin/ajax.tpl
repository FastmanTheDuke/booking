{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
<script type="text/javascript">
	AJAXURL="{$ajaxUrl}";
	$(function () {
		$(".saver").click(function () {
			var ids = $(this).val();
			var action = $(this).data("action");
			var valeur = $(this).parent().children(".valeur").val();
			console.log(AJAXURL);
			console.log(ids);
			console.log(action);
			console.log(valeur);
			$.ajax({
				dataType: "JSON",
				type: "POST",				
				url: AJAXURL,
				data: { 
				  ajax: true, 
				  action: action,
				  id: ids,
				  valeur: valeur
				}, 
				success: function (result) {
					console.log(result);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					var errorMsg = textStatus + ': ' + errorThrown;
					console.log(AJAXURL + action+"&valeur="+valeur+"&id="+ids);
					console.log("BAD");
				}
			});
			return false;
		});
		
		/* $(".ajax_table_link").click(function () {
			var link = $(this);
			$.post($(this).attr('href'), function (data) {
			  // If response comes from symfony controller
	  // then data has "status" and "message" properties
	  // otherwise if response comes from legacy controller
	  // then data has "success" and "text" properties.

				if (data.success == 1 || data.status === true) {
					showSuccessMessage(data.text || data.message);
					if (link.hasClass('action-disabled')){
						link.removeClass('action-disabled').addClass('action-enabled');
					} else {
						link.removeClass('action-enabled').addClass('action-disabled');
					}
					link.children().each(function () {
						if ($(this).hasClass('hidden')) {
							$(this).removeClass('hidden');
						} else {
							$(this).addClass('hidden');
						}
					});
				} else {
					showErrorMessage(data.text || data.message);
				}
			}, 'json');
			return false;
		}); */
	});
</script>
{* var WIC_ERP = (function () {
	'use strict';
	var $ajaxUrl,
		translations = {},
		successClass = 'module_confirmation conf confirm',
		errorClass = 'module_error alert',
		waitClass = 'alert alert-info',		
		upToDate = true;

	return {
		init: function (options) {
			if (typeof options === 'object'){
				if (options.hasOwnProperty('translations')){
					$.extend(translations, options.translations);
				}else{
					console.log('translations empty ');
				}
				
				if(options.hasOwnProperty('ajaxUrl') && options.ajaxUrl !== ''){
					AJAXURL = options.ajaxUrl + '&action=';
				}else{
					AJAXURL = $(".ajaxUrl").html();					
				}					
			}else{
				AJAXURL = $(".ajaxUrl").html();
				//ajaxUrl = AJAXURL;
			}
		},
		ajaxReq: function (action, data, callback) {
			$.ajax({
				dataType: "JSON",
				type: "POST",				
				url: AJAXURL + action,
				data: data,
				success: function (result) {					
					if (typeof callback === 'function')						
						callback(result);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					var errorMsg = textStatus + ': ' + errorThrown;
					callback("none");
				}
			});
		},
		formUpdate: function (e) {			
			var data = $("#refresher").serializeArray();			
			WIC_ERP.ajaxReq('finder', data, function (result) {				
				
				if (!result){
					$("#results").html("Aucun résultat pour cette recherche.<br>Etendez les résultats à 50km ou affinez votre recherche.");
					return result;
				}
				
				var bounds = new google.maps.LatLngBounds();				
				var results = JSON.stringify(result);
				results = JSON.parse(results);
				clearMarkers();
				markers = [];
				if(results.length>0){			
					for (var i = 0; i < results.length; i++) {
						var markerIcon = MARKER_PATH;
						markers[i] = new google.maps.Marker({
							position: {lat: Number(results[i].lat), lng: Number(results[i].lng)},
							icon: markerIcon
						});
						markers[i].placeResult = new Object();					
						markers[i].placeResult.lat = results[i].lat;
						markers[i].placeResult.lng = results[i].lng;
						results[i].nom = markers[i].placeResult.nom = results[i].name;
						results[i].adresse = markers[i].placeResult.adresse = results[i].adresse;					
						markers[i].placeResult.post_city = results[i].code_city;
						results[i].code = markers[i].placeResult.code = results[i].code;
						results[i].city = markers[i].placeResult.city = results[i].city;
						results[i].country = markers[i].placeResult.country = results[i].country;
						markers[i].placeResult.tel = results[i].tel= results[i].tel;
						results[i].full_address = markers[i].placeResult.full_address = results[i].name+" - "+results[i].adresse+" "+results[i].code+" "+results[i].city+" "+results[i].country;						
						results[i].full_address_html = "<b>"+results[i].name+"</b><br>"+results[i].adresse+"<br>"+results[i].code+" "+results[i].city+"<br>"+results[i].country;
						markers[i].image= results[i].image = "";
						results[i].comp_adresse = "";					
						var type=results[i].type;
						var value=results[i].value;
						markers[i].logo = results[i].logo = $(".logo_map").html();					
						
						
						
						google.maps.event.addListener(markers[i], 'click', showInfoWindow);
						/* console.log(results[i].country);
						console.log(results[i].req); */
						setTimeout(dropMarker(markers,i), i * 100);
						bounds.extend(markers[i].position);					
						addResult(results[i], i);
					}				
					if(markers.length){
						if(type=="country"){
							var padding=0;
						}else{
							var padding=60;
						}
						/* console.log(type);
						console.log(padding);
						console.log(value); */
						map.fitBounds(bounds,padding);
					}
					return true;
				}else{
					//clearMarkers();
					clearResults();
					markers = [];
					$("#results").html("Aucun résultat pour cette recherche.<br>Etendez les résultats à 50km ou affinez votre recherche.");
					return true;
				}
			});
			
		},
	};
}());
*}