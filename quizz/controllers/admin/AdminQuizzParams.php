<?php
/*
 * 08/2106
 * beforebigbang/La Teapot du web pour le vieux plongeur
 * Import version 32 : on a désormais la possibilité de créer des produits;
 * ceci associe aussi aux boutiques les produits
 */

//exemple de produit à tester  98-6048G_OCE dans le fichier BASE AUP 2016.csv


class AdminQuizzParamsController extends ModuleAdminControllerCore
{
    public $controller_type='admin';   
    public function __construct()
    {
		$this->display = 'options';
        $this->displayName = 'Quizz settings';
        $this->bootstrap = true;
        parent::__construct();
		Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminModules').'&configure='.$this->module->name);
    }
}