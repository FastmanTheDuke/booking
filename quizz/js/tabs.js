$(function () {
	$("#subtab-AdminQuizz").hide();	
	$("#subtab-AdminFaq").hide();	
	$("#subtab-AdminBooker").hide();	
	$("#subtab-AdminBookerAuth").hide();	
	$("#subtab-AdminBookerAuthReserved").hide();	
	$("#subtab-AdminBookerView").hide();
	$("#tab-QUIZZ").css("cursor","pointer");
	$("#tab-QUIZZ").click(function(){
		$("#subtab-AdminQuizz").toggle(150);
	});
});