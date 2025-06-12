<?php
require_once (dirname(__FILE__). '/../../classes/Booker.php');
require_once (dirname(__FILE__). '/../../classes/BookerAuth.php');
require_once (dirname(__FILE__). '/../../classes/BookerAuthReserved.php');
class AdminBookerViewController extends ModuleAdminControllerCore
{
    protected $_module = NULL;
	public $controller_type='admin';   

    public function __construct()
    {		
        $this->display = 'options';
        $this->displayName = 'Booker View';
        $this->bootstrap = true;
        parent::__construct();
    }
	public function getFieldsCalendar()
    {
		$fields = array(
			'FIELD_ERA'                  => 0,
			'FIELD_YEAR'                 => 1,
			'FIELD_MONTH'                => 2,
			'FIELD_WEEK_OF_YEAR'         => 3,
			'FIELD_WEEK_OF_MONTH'        => 4,
			'FIELD_DATE'                 => 5,
			'FIELD_DAY_OF_YEAR'          => 6,
			'FIELD_DAY_OF_WEEK'          => 7,
			'FIELD_DAY_OF_WEEK_IN_MONTH' => 8,
			'FIELD_AM_PM'                => 9,
			'FIELD_HOUR'                 => 10,
			'FIELD_HOUR_OF_DAY'          => 11,
			'FIELD_MINUTE'               => 12,
			'FIELD_SECOND'               => 13,
			'FIELD_MILLISECOND'          => 14,
			'FIELD_ZONE_OFFSET'          => 15,
			'FIELD_DST_OFFSET'           => 16,
			'FIELD_YEAR_WOY'             => 17,
			'FIELD_DOW_LOCAL'            => 18,
			'FIELD_EXTENDED_YEAR'        => 19,
			'FIELD_JULIAN_DAY'           => 20,
			'FIELD_MILLISECONDS_IN_DAY'  => 21,
			'FIELD_IS_LEAP_MONTH'        => 22,
			'FIELD_FIELD_COUNT'          => 23,
		);
	}
	public function setFieldsCalendar(IntlCalendar $cal)
    {
		global $fields;
		$ret = array();
		foreach ($fields as $name => $value) {
			if ($cal->isSet($value)) {
				$ret[] = $name;
			}
		}
		return $ret;
	}
	public function renderOptions()
    {
        //$this->addJS(_MODULE_DIR_.$this->module->name.'/views/js/admin-importsupplier.js');
		$output=$this->dates();
		return $output;
    }
	public function ajaxProcessLoader() {
		$datenum=Tools::getValue('datenum'); 
		$dir=Tools::getValue('dir');	
		$output=$this->dates($datenum,$dir);
		echo json_encode($output);
		exit; 
	}
	public function ajaxProcessNewer() {
		$datenum=Tools::getValue('datenum'); 
		$date_reserved=Tools::getValue('date_reserved');
		$hour_from=Tools::getValue('hour_from');
		$hour_to=Tools::getValue('hour_to');
		$resa = new BookerAuthReserved();
		$resa->date_reserved=$date_reserved;
		$resa->hour_from=$hour_from;
		$resa->hour_to=$hour_to;
		$resa->active=1;
		$resa->id_booker=1;
		$id=$resa->add();
		$output[]=$id;
		$output[]=$datenum;
		$output[]=$date_reserved;
		$output[]=$hour_from;
		$output[]=$hour_to;
		//$output=$this->dates($datenum,$dir);
		echo json_encode($output);
		exit; 
	}
	public function dates($datenum=false,$dir=false){
		
		$option = '';		
		ini_set('intl.default_locale', 'fr_FR');		
		ini_set('date.timezone', 'UTC');
		setlocale(LC_TIME, 'fr_FR');
		setlocale(LC_ALL, 'fr_FR');
		//$dt = new DateTime("2022-10-10 23:10:08");
		$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL);
		$formatter->setPattern('E d/M/yyyy EEEE d LLLL yyyy à hh.mm.ss aaaa / HH:mm:ss');
		
		//echo $formatter->format($dt).PHP_EOL;
		
		if(null!==Tools::getValue("day")){
			$current_day=date("Ymd");
		}else{
			$current_day=date("Ymd");
		}
		
		if(null!==Tools::getValue("month")){
			$current_month=date("m");
		}else{
			$current_month=date("m");
		}
		$current_year=date("Y");
		
		if(isset($_GET["y"])){
			$y=$_GET["y"];
			if($y!=$current_year){
				$first_day=0;
				$start=$y."0101";
				$week_num_day=date('N',strtotime($next_year."0101"));
			}else{
				$first_day=date("z");
				$week_num_day=date("N");
				$start=date("Ymd");				
			}
		}else{
			$y=$current_year;
			$week_num_day=date("N");
			$year_num_day=$first_day=date("z");
			$start=date("Ymd");
		}
		if($dir){			
			if($dir=="next"){
				$year_num_day=$datenum+7;
			}else{
				$year_num_day=$datenum-7;
			}
		}
		$days = [];
		$checkdays = [];
		
		for ($i=3;$i>=1;$i--){
			$item = DateTime::createFromFormat('z',strval($year_num_day-$i));
			$formatter->setPattern('E');
			$thisdayword = trim($formatter->format($item));
			
			$item = DateTime::createFromFormat('z',strval($year_num_day-$i));
			$formatter->setPattern('d');
			$thisday = trim($formatter->format($item));
			
			
			$item = DateTime::createFromFormat('z',strval($year_num_day-$i));
			$formatter->setPattern('MMM');
			$thismonth = trim($formatter->format($item));
			
			$item = DateTime::createFromFormat('z',strval($year_num_day-$i));
			$formatter->setPattern('yyyy');
			$thisyear = trim($formatter->format($item));
			
			$days[] = $thisdayword." ".$thisday." ".$thismonth." ".$thisyear;
			
			
			$item = DateTime::createFromFormat('z',strval($year_num_day-$i));
			$formatter->setPattern('MM');
			$thismonthnum = trim($formatter->format($item));
			
			$daysitem[$thisdayword." ".$thisday." ".$thismonth." ".$thisyear] = $thisyear."-".$thismonthnum."-".$thisday;
			
			$checkdays[$thisdayword." ".$thisday." ".$thismonth." ".$thisyear] = $thisyear."-".$thismonthnum."-".$thisday;
			
			if($i==3){$first_date_of_this_week=$thisday." ".$thismonth." ".$thisyear;};
			
		}
		
		$current_day = new DateTime("$current_day");
		$formatter->setPattern('EEEE');
		$word_day = trim($formatter->format($current_day));
		
		$item = DateTime::createFromFormat('z',strval($year_num_day));
		$formatter->setPattern('E');
		$thisdayword = trim($formatter->format($item));
		
		$item = DateTime::createFromFormat('z',strval($year_num_day));
		$formatter->setPattern('d');
		$thisday = trim($formatter->format($item));
		
		
		$item = DateTime::createFromFormat('z',strval($year_num_day));
		$formatter->setPattern('MMM');
		$thismonth = trim($formatter->format($item));
		
		$item = DateTime::createFromFormat('z',strval($year_num_day));
		$formatter->setPattern('yyyy');
		$thisyear = trim($formatter->format($item));
		
		$days[] = $thisdayword." ".$thisday." ".$thismonth." ".$thisyear;
		
		$item = DateTime::createFromFormat('z',strval($year_num_day));
		$formatter->setPattern('MM');
		$thismonthnum = trim($formatter->format($item));
		$daysitem[$thisdayword." ".$thisday." ".$thismonth." ".$thisyear] = $thisyear."-".$thismonthnum."-".$thisday;
		$checkdays[$thisdayword." ".$thisday." ".$thismonth." ".$thisyear] = $thisyear."-".$thismonthnum."-".$thisday;
		
		for ($i=1;$i<=3;$i++){			
			
			$item = DateTime::createFromFormat('z',strval($year_num_day+$i));
			$formatter->setPattern('E');
			$thisdayword = trim($formatter->format($item));
			
			$item = DateTime::createFromFormat('z',strval($year_num_day+$i));
			$formatter->setPattern('d');
			$thisday = trim($formatter->format($item));
			
			
			$item = DateTime::createFromFormat('z',strval($year_num_day+$i));
			$formatter->setPattern('MMM');
			$thismonth = trim($formatter->format($item));
			
			$item = DateTime::createFromFormat('z',strval($year_num_day+$i));
			$formatter->setPattern('yyyy');
			$thisyear = trim($formatter->format($item));
			
			$days[] = $thisdayword." ".$thisday." ".$thismonth." ".$thisyear;
			
			$item = DateTime::createFromFormat('z',strval($year_num_day+$i));
			$formatter->setPattern('MM');
			$thismonthnum = trim($formatter->format($item));
			$daysitem[$thisdayword." ".$thisday." ".$thismonth." ".$thisyear] = $thisyear."-".$thismonthnum."-".$thisday;
			$checkdays[$thisdayword." ".$thisday." ".$thismonth." ".$thisyear] = $thisyear."-".$thismonthnum."-".$thisday;
			$last_date_of_this_week = $thisday." ".$thismonth." ".$thisyear;
		}
		
		for ($i=0;$i<=23;$i++){
			$item = DateTime::createFromFormat('H',strval($i));
			$formatter->setPattern('HH');
			$hours[] = $formatter->format($item) . ":00";
			
		}
		
		$booker_auth_reserved = [];
		
		foreach($checkdays as $key => $checkday){			
			$booker_auth_reserved_sql = 'SELECT * FROM `'._DB_PREFIX_.'booker_auth_reserved` WHERE date_reserved>="'.$checkday.'" AND date_reserved<="'.$checkday.'" AND active=1 order by hour_from ASC';
			$booker_auth_reserved_req = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($booker_auth_reserved_sql);
			if($booker_auth_reserved_req){				
				foreach($booker_auth_reserved_req as $auth_reserved){
					$addfrom="";
					$addto="";
					if($auth_reserved["hour_from"]<10){$addfrom="0";}
					if($auth_reserved["hour_to"]<10){$addto="0";}
					$booker_auth_reserved[$key][]=["date"=>$auth_reserved["date_reserved"],"hour_from"=>$addfrom.$auth_reserved["hour_from"],"id_reserved"=>$auth_reserved["id_reserved"],"id_booker"=>$auth_reserved["id_booker"],"hour_to"=>$addto.$auth_reserved["hour_to"]];
				}
			}
		}
		
		
		
		
		$this->context->smarty->assign('hours', $hours);
		$this->context->smarty->assign('word_day', $word_day);
		$this->context->smarty->assign('days', $days);
		$this->context->smarty->assign('daysitem', $daysitem);
		$this->context->smarty->assign('booker_auth_reserved', $booker_auth_reserved);
		
		$this->context->smarty->assign('first_date_of_this_week', $first_date_of_this_week);
		$this->context->smarty->assign('last_date_of_this_week', $last_date_of_this_week);
		$this->context->smarty->assign('current_month', $current_month);
		
		$this->context->smarty->assign('y', $y);
		$this->context->smarty->assign('first_day', $first_day);
		$this->context->smarty->assign('start', $start);
		$this->context->smarty->assign('week_num_day', $week_num_day);
		$this->context->smarty->assign('year_num_day', $year_num_day);
		
		$this->context->smarty->assign('ajaxloader', "loader");
		$this->context->smarty->assign('ajaxnewer', "newer");
		
		$this->context->smarty->assign(
			array(		  
			  'ajaxUrl' => $this->context->link->getAdminLink('AdminBookerView'),
			)
		);
        $this->context->smarty->assign('import_controller_link', $this->context->link->getAdminLink('AdminBookerView'));
        $option .= $this->context->smarty->fetch($this->getTemplatePath().'booker_view.tpl');
		return $option;
	}
	/*public function renderList()
	{
		 //https://www.prestashop.com/forums/topic/1063433-param%C3%A8tre-champs-en-fonction-dun-autre-dans-admin-controller-module/
		$list = parent::renderList();	
		$this->context->smarty->assign(
		array(		  
		  'ajaxUrl' => $this->context->link->getAdminLink('AdminBookerAuthReserved')
		));
		$content = $this->context->smarty->fetch($this->getTemplatePath().'ajax.tpl');
		return $list . $content; 
	}    */
	/* public function initPageHeaderToolbar()
    {
 
        //Bouton d'ajout
        $this->page_header_toolbar_btn['new'] = array(
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->module->l('Add new Booker Auth'),
            'icon' => 'process-icon-new'
        );
 
        parent::initPageHeaderToolbar();
    }  */
	
}