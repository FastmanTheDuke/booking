{* 
 * 08/2106
 * beforebigbang/La Teapot du web pour le vieux plongeur
 * Import version 32 : on a désormais la possibilité de créer des produits;
 * ceci associe aussi aux boutiques les produits
 *
  *}
<div class="relative full inlineblock main">
	<div class="y flexbox-container mb60 mt60">
		<div class="prev" data-datenum="{$year_num_day}" data-action="{$ajaxloader}" data-dir="prev"> < </div>		
		<div class="y"><h3>From <b>{$first_date_of_this_week}</b> to <b>{$last_date_of_this_week}</b></h3></div>
		<div class="next" data-datenum="{$year_num_day}" data-action="{$ajaxloader}" data-dir="next"> > </div>
	</div>
	<div class="days flexbox-container">
		{foreach $days as $day}
			<div class="days_hours">
				<div class="day">{$day}</div>
				
				<div class="hours flexbox-container-vertical">
					{assign var=class value=""}
					{foreach $hours as $hour}
						{if $reserved==1}
							{assign var=class value="reserved "}
						{/if}
						
						{foreach $booker_auth_reserved.$day as $details=>$vals}
							{foreach $vals as $detail=>$val}								
								{if $detail=="hour_from" && $val|cat:":00"==$hour}							
									{assign var=class value="reserved start "}
									{assign var=reserved value=1}
								{/if}							
								{if $detail=="hour_to" && $val|cat:":00"==$hour}							
									{assign var=class value="reserved end "|cat:$class}
									{assign var=reserved value=0}
								{/if}
								{if $detail=="id_reserved" && $reserved==1}
									{assign var=class value=$class|cat:"id_reserved_"|cat:$val|cat:" "}
								{/if}
								{if $detail=="id_booker" && $reserved==1}
									{assign var=class value=$class|cat:"id_booker_"|cat:$val|cat:" "}
								{/if}
							{/foreach}
						{/foreach}
						<div class="hour {$class} " data-date_reserved="{$daysitem.$day}" data-datenum="{$year_num_day}" data-action="{$ajaxnewer}">{$hour}</div>
						{if $reserved==0}
							{assign var=class value=""}
						{/if}
					{/foreach}
				</div>
			</div>
		{/foreach}
	</div>
	

	<div class="popup">
		<div class="inside">
			<input type="hidden" name="id_booker" id="id_booker" value="1" class="" required="required">
			<input type="hidden" name="active" id="active" value="1" class="" required="required">
			<input type="hidden" name="datenum" id="datenum" value="1" class="" required="required">
			<div class="form-group date_reserved"><label class="control-label col-lg-4 required">Date:</label><div class="col-lg-8 value"><h2></h2></div></div>
			<div class="form-group hour_from"><label class="control-label col-lg-4 required">Start Hour:</label><div class="col-lg-8 value"><h2></h2></div></div>
			<div class="form-group hour_to">
				<label class="control-label col-lg-4 required">End Hour:</label>
				<div class="col-lg-8 value">
					<input type="text" name="hour_to" id="hour_to" value="" class="" required="required">
				</div>
			</div>
			<div class="add">Réserver</div>
			<div class="closer">Fermer x</div>
		</div>
	</div>
	<script type="text/javascript">
		AJAXURL="{$ajaxUrl}";
		//$(function () {
		$("document").ready(function(){
			$(".next,.prev").click(function () {
				//var ids = $(this).val();
				var datenum = $(this).data("datenum");
				var dir = $(this).data("dir");
				var action = $(this).data("action");		
				$.ajax({
					dataType: "JSON",
					type: "POST",				
					url: AJAXURL,
					data: { 
					  ajax: true, 
					  action: action,
					  dir: dir,
					  datenum: datenum,
					}, 
					success: function (result) {
						console.log(result);
						$(".main").parent().html(result);
						$("html,body").stop().animate({  
							scrollTop:$(".main").position().top
							//scrollTop:0
						}, 300); 
					},
					error: function (jqXHR, textStatus, errorThrown) {
						var errorMsg = textStatus + ': ' + errorThrown;					
					}
				});
				return false;
			});
			$(".popup").hide(1);
			$(".closer").click(function () {
				$(".popup").hide(1);
			});
			$(".hour").not(".reserved").click(function () {
				//var ids = $(this).val();
				var hour_from = $(this).html();
				var datenum = $(this).data("datenum");
				var action = $(this).data("action");		
				var date_reserved = $(this).data("date_reserved");
				var index = $(this).index();
				var finalIndex = 24;
				console.log(index);
				console.log(finalIndex);
				$(this).parent(".hours").children(".hour").each(function(){
					if($(this).index()>index && $(this).hasClass(".reserved")){
						var finalIndex=$(this).index()-1;
					}
				});
				var max = $(".hour").eq(finalIndex).html();
		
				console.log(finalIndex);
				console.log(max);
				$(".popup").show(150,function(){
					$(this).children(".inside").children(".date_reserved").children(".value").children("h2").html(date_reserved);
					$(this).children(".inside").children(".hour_from").children(".value").children("h2").html(hour_from);
					$(".add").click(function () {
						
						$.ajax({
							dataType: "JSON",
							type: "POST",				
							url: AJAXURL,
							data: { 
							  ajax: true, 
							  action: action,
							  date_reserved: date_reserved,
							  datenum: datenum,
							  hour_from: hour_from,
							  hour_to: $(".popup").children(".inside").children(".hour_to").children(".value").children("input[name='hour_to']").val(),
							}, 
							success: function (result) {
								console.log(result);
								/* $(".main").parent().html(result);
								$("html,body").stop().animate({  
									scrollTop:$(".main").position().top
									//scrollTop:0
								}, 300);  */
							},
							error: function (jqXHR, textStatus, errorThrown) {
								var errorMsg = textStatus + ': ' + errorThrown;					
							}
						});
					});		
				});		
				
				return false;
			});	
		});

	</script>

	<style>

	{literal}
	/* FONTS */
	@font-face {
		font-family: 'hando_softblack';
		src: url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-black-webfont.eot');
		src: url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-black-webfont.eot?#iefix') format('embedded-opentype'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-black-webfont.woff2') format('woff2'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-black-webfont.woff') format('woff'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-black-webfont.ttf') format('truetype'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-black-webfont.svg#hando_softblack') format('svg');
		font-weight: normal;
		font-style: normal;
		font-display: swap;
	}
	@font-face {
		font-family: 'hando_softbold';
		src: url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-bold-webfont.eot');
		src: url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-bold-webfont.eot?#iefix') format('embedded-opentype'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-bold-webfont.woff2') format('woff2'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-bold-webfont.woff') format('woff'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-bold-webfont.ttf') format('truetype'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-bold-webfont.svg#hando_softbold') format('svg');
		font-weight: normal;
		font-style: normal;
		font-display: swap;
	}
	@font-face {
		font-family: 'hando_softlight';
		src: url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-light-webfont.eot');
		src: url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-light-webfont.eot?#iefix') format('embedded-opentype'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-light-webfont.woff2') format('woff2'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-light-webfont.woff') format('woff'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-light-webfont.ttf') format('truetype'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-light-webfont.svg#hando_softlight') format('svg');
		font-weight: normal;
		font-style: normal;
		font-display: swap;
	}
	@font-face {
		font-family: 'hando_softregular';
		src: url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-regular-webfont.eot');
		src: url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-regular-webfont.eot?#iefix') format('embedded-opentype'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-regular-webfont.woff2') format('woff2'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-regular-webfont.woff') format('woff'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-regular-webfont.ttf') format('truetype'),
			 url('../../../../../themes/happesmoke/assets/fonts/handsoft/handosoft-regular-webfont.svg#hando_softregular') format('svg');
		font-weight: normal;
		font-style: normal;
		font-display: swap;
	}
	.flexbox-container {
	  display: flex;
	  justify-content: space-around;
	  align-items: center;
	  align-content: space-around;
	}
	.flexbox-container-vertical {
	  display: flex;
	  flex-direction: column;
	  justify-content: space-around;
	  align-items: center;
	  align-content: center;
	}
	.hours,.days_hours{min-height:100vh;}
	.next,.prev {
		font-family: Fontawesome,hando_softblack, sans-serif;
		font-size: 2rem;
		margin-left: 30px;
		cursor: pointer;
	}

	.y h3 {
		margin: 0!important;padding:1rem;
	}

	.y {
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center;
		align-content: center;
	}
	.prev {
		margin: 0 30px 0;
	}
	.hour.reserved {
		cursor: not-allowed;
		opacity: 0.4;
	}

	.hour {
		box-shadow: 0 0 1px 1px #dadada;
		width: 100%;
		text-align: center;
		padding: 0.2rem;
		font-size: 0.8rem;
		cursor: pointer;
	}
	.popup {
		position: fixed;
		width: 100%;
		min-height: 100vh;
		background: #ffffff8a;
		top: 0;
		left: 0;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		align-content: center;
		text-align: center;
		padding: 12%;
	}
	.popup .inside {
	   display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		align-content: center;
		text-align: center;
		max-width: 90%;
		width: 800px;
		background: #ffffff;   
		padding: 1rem;
	}

	.popup input {
		text-align: center;
	}
	{/literal}
	</style>
</div>