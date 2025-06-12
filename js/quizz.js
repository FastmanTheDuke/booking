$(document).ready(function(){
	$('<div class="quantity-nav"><div class="quantity-button quantity-up">+</div><div class="quantity-button quantity-down">-</div></div>').insertAfter('.quantity input');
    $('.quantity').each(function() {
      var spinner = $(this),
        input = spinner.find('input[type="number"]'),
        btnUp = spinner.find('.quantity-up'),
        btnDown = spinner.find('.quantity-down'),
        min = input.attr('min'),
        max = input.attr('max');
        step = input.attr('step');
		console.log(spinner.attr("id") + " - " + step);
		btnUp.click(function() {
			step = input.attr('step');
			console.log(spinner.attr("id") + " - " + step);
			var oldValue = parseFloat(input.val());
			if (oldValue >= max) {
				var newVal = oldValue;
			} else {
				var newVal = oldValue*1 + parseFloat(step)*1;
			}
			if(input.hasClass("decimal")){
				newVal = newVal.toFixed(2);
			}
			spinner.find("input").val(newVal);
			spinner.find("input").trigger("change");
		});

		btnDown.click(function() {
			step = input.attr('step');
			
			
			var oldValue = parseFloat(input.val());
			console.log(spinner.attr("id") + " - " + step);
			
			if (oldValue <= min) {
				var newVal = oldValue;
			} else {
				var newVal = oldValue*1 - parseFloat(step)*1;
			}
			if(input.hasClass("decimal")){
				newVal = newVal.toFixed(2);
			}
			spinner.find("input").val(newVal);
			spinner.find("input").trigger("change");
		});

	});
})