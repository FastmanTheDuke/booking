<?php 
if (!defined('_PS_VERSION_')) {
    exit;
}
class quizzdisplayModuleFrontController extends ModuleFrontController  {
	
	
	public function initContent(){
		//avant le chargement du contenu - body content
		parent::initContent();
		
		$quizz=new Quizz();
		$questions=$quizz->getQuestions();
		$this->context->smarty->assign(
		array(
		  'questions' => $questions,
		));
		
		$this->setTemplate('module:quizz/views/templates/front/display.tpl');
	}
	public function setMedia()
	{
		parent::setMedia();
		$this->registerStylesheet(
			'module-quizz-style',
			'modules/'.$this->module->name.'/css/quizz.css',
			[
			  'media' => 'all',
			  'priority' => 200,
			]
		);

		$this->registerJavascript(
			'module-quizz-simple-lib',
			'modules/'.$this->module->name.'/js/quizz.js',
			[
			  'priority' => 200,
			  'attribute' => 'async',
			]
		);
	}
}
?>