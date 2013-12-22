<?php

use Nette\Application\UI\Presenter;


abstract class BasePresenter extends Presenter
{
   
	const MESS_PROTECT	= 'Vypršel ochranný časový limit, odešlete prosím formulář ještě jednou.';

    private $navigace;
	public  $user;
	public  $is_todo;
	public	$company;
	/** Moje parametry - fond, smennost, delka smeny*/
	public	$mypar;		
	public	$stroje;
	public	$mpars;
	/** @var Nette\DI\Container */ 
	public  $context;
	public  $is_filter = false;
	public  $presnt_name = '';
	public  $render_name = '';



	public function startup()
	{
		parent::startup();
				
		$user = $this->getUser();
		$this->user = $user;
		$context = $this->getContext();
		$this->context = $context;

		$is_todo = false;
		if(isset($context->params['myvar']['todos'])){
			$tdpar = $context->params['myvar']['todos'];
			$is_todo = true;
			$this->is_todo = $is_todo;
		}
		if(isset($context->params['company'])){
			$this->company = $context->params['company'];
		}
		if(isset($context->params['mypar'])){
			$this->mypar = $context->params['mypar'];
		}		
		if(isset($context->params['stroje'])){
			$this->stroje = $context->params['stroje'];
		}		
		$session = $context->session;
		if(!$session->isStarted()){$session->start();}
		
        if(!$session->hasSection('mySetting'))
        {
			$section = $session->getSection('mySetting');
			$section->setExpiration('+ 18 hours');
			$this->eraseMySet();
		}
		$mdl = new Model;
		$this->mpars = $mdl->getActualTarifParam();
		
		$this->refreshMySetting();
//		dd($this->mypar, "MYPAR");
//		dd($this->stroje, "STROJE");
//		dd($this->mpars, "MPARS");
		//dd($this->mpars['rucni_fond'], "Mtest");
		
		//main menu
		$menu = array(
	            'Obchod' 	=> 'Obchod',
	            'TPV'	 	=> 'Tpv',
	            'Nákup' 	=> 'Nakup',
	            'Správa' 	=> 'Sprava',
		        );
        $this->navigace = $menu;
		
	}
	
	/**
	 * Set variable into Session
	 * @param type $name - variable name
	 * @param type $value - variable value
	 */
	public function setSess($name,$value)
    {
        $container = $this->context;
        $session = $container->session;
        $section = $session->getSection('All');
        $section->$name = $value;
    }

	/**
	 * Get variable from Session
	 * @param type $name
	 * @return type (value of variable)
	 */
    public function getSess($name)
    {
        $container = $this->context;
        $session = $container->session;
        $section = $session->getSection('All');
        return $section->$name;
    }	
	
	/*
	 * @return void
	 */
	public function actionGoBack()
	{
		$this->goBack();
	}	
	
	/**
	* Navigation control factory.
	* @return mixed
	*/	
	protected function createComponentNavigation($name) {
		$this->MakeMenu($name, $this->navigace);
	}

		
	protected function MakeMenu($name, $menu){
	 	$mains = "Úvod, Obchod, Nákup, TPV, Správa";
	    $nav = new \Navigation\Navigation($this, $name);
	    $sec = $nav->setupHomepage("Úvod", $this->link("Homepage:"));
        if ($this->name == 'Homepage') {
            $nav->setCurrent($sec);
        }
        $user = $this->getUser();
		$is_guest = in_array('guest', $user->getRoles());
		if(!$is_guest)
		{
		    $navigace = $menu;
		    foreach ($navigace as $k => $v) {
				$ismain = strpos($mains, $k) > 0;
				if($this->user->isAllowed($v,'default')){
					if ($ismain){
						$sec = $nav->add($k, $this->link($v . ":"), $v, $ismain);
					} else {
						$sec = $nav->add($k, $this->link($v . ":"),'', $ismain);
					}
			        if ($this->name == $v) {
				        $nav->setCurrent($sec);
					}
				}
			}
		}
		
	}
		
	protected function eraseMySet(){
		if(!$this->context->session->hasSection('mySetting')){return;}
		$section = $this->context->session->getSection('mySetting');
		$section->firma = '<nevybrána>';
		$section->id_firma = 0;
		$section->id_nabidka = 0;
		$section->nabidka = '<nevybrána>';
		$section->id_osoba = 0;
		$section->osoba = '<nevybrán>';
		$section->id_produkt = 0;
		$section->produkt='<nevybrán>';
		$this->refreshMySetting();
 }
	
 
 protected function isMySet($poradi=1){
    if(!$this->context->session->hasSection('mySetting')){return false;}
 	$section = $this->context->session->getSection('mySetting');
	switch($poradi) {
		case 1: return $section->id_firma>0;
		case 2: return $section->id_osoba>0;
		case 3: return $section->id_nabidka>0;
		case 4: return $section->id_produkt>0;
	}
 }

 /**
  *
  * @param type $poradi 1..firma, 2..osoba, 3..nabídka, 4..produkt
  * @param type $id	id_firmy/id_osoby/id_nabidky/id_produkty - dle poradi
  * @param type $setonly 1..jen nastavit - nemazat, 0..vymazat ostatni
  * @return type int 1..ok, 0..nosuccess
  */
 protected function setIntoMySet($poradi, $id=0, $setonly=0){
	if($id==0){return 0;}
	if(!$this->context->session->hasSection('mySetting')){return 0;}
 	$section = $this->context->session->getSection('mySetting');
	switch($poradi) {
		case 1: 
			$instance = new Firma;
			$firma = $instance->find($id)->fetch();
			$section->firma = $firma->nazev;
			$section->id_firma = $firma->id;
			$section->id_osoba = 0;
			$section->osoba = '<nevybrán>';
			if($setonly==0){
				$section->id_nabidka = 0;
				$section->nabidka = '<nevybrána>';
				$section->id_produkt = 0;
				$section->produkt='<nevybrán>';
				$section->id_osoba = 0;
				$section->osoba = '<nevybrána>';
			}
			break;
		case 2: 
			$instance = new Osoba;
			$osoba = $instance->find($id)->fetch();
			$section->osoba = $osoba->titul_pred .' '. $osoba->prijmeni .' '. $osoba->jmeno .' '. $osoba->titul_za;
			$section->osoba = $osoba->prijmeni .' '. $osoba->jmeno;
			$section->id_osoba = $osoba->id;
			if($osoba->id_firmy<>$section->id_firma){
				$this->setIntoMySet(1, $osoba->id_firmy, 1);
			}
			if($setonly==0){
				$section->id_nabidka = 0;
				$section->nabidka = '<nevybrána>';
				$section->id_produkt = 0;
				$section->produkt='<nevybrán>';
			}
			break;
		case 3: 
			$instance = new Nabidka;
			$item = $instance->find($id)->fetch();
			if(!$item===FALSE){
				$section->nabidka = $item->popis;
				$section->id_nabidka = $item->id;
				if($item->id_firmy<>$section->id_firma){
					$this->setIntoMySet(1, $item->id_firmy, 1);
				} else {
					$prod = new Produkt;
					if($section->id_produkt>0){
						$prd = $prod->find($section->id_produkt)->fetch();
						if($prd){
							if($prd->idn<>$item->id){
								$setonly=0;
							} else {
								$setonly=1;
							}
						}
					} else {
						$setonly=1;
					}
				}
			}
			if($setonly==0){
				$section->id_produkt = 0;
				$section->produkt='<nevybrán>';
			}
			break;
		case 4: 
			$instance = new Produkt;
			$item = $instance->find($id)->fetch();
			$vazby = $instance->countProdVazby($id);
			$section->produkt = $item->nazev;
			$section->id_produkt = $item->id;
			$section->countBOM = $vazby->bom;
			$section->countTPV = $vazby->tpv;
			if($item->id_firmy<>$section->id_firma){
				$this->setIntoMySet(1, $item->id_firmy, 1);
			}
			if($item->idn<>$section->id_nabidka){
				$this->setIntoMySet(3, $item->idn, 1);
			}
			break;
	}
	$this->refreshMySetting();
	return 1;
}

 
	protected function refreshMySetting(){
			$section = $this->context->session->getSection('mySetting');
			$this->template->afirma = $section->firma;
			$this->template->aosoba = $section->osoba;
			$this->template->anabidka = $section->nabidka;
			$this->template->aprodukt = $section->produkt;
			$this->template->ifirma = $section->id_firma;
			$this->template->iosoba = $section->id_osoba;
			$this->template->inabidka = $section->id_nabidka;
			$this->template->iprodukt = $section->id_produkt;
			$this->template->cntBOM = $section->countBOM>0 ? " ($section->countBOM)": "";
			$this->template->cntTPV = $section->countTPV>0 ? " ($section->countTPV)": "";
			$this->template->titBOM = $section->countBOM>0 ? " ($section->countBOM mat. položek)": " (žádný materiál)";
			$this->template->titTPV = $section->countTPV>0 ? " ($section->countTPV operací)": " (žádné operace)";
			$this->template->ishome = $this->name == 'Homepage';
			$this->template->islogin = !$this->getUser()->isLoggedIn();
	}

	protected function getIdFromMySet($poradi=1){
		if(!$this->context->session->hasSection('mySetting')){return 0;}
		$section = $this->context->session->getSection('mySetting');
		switch($poradi) {
			case 1: return $section->id_firma;
			case 2: return $section->id_osoba;
			case 3: return $section->id_nabidka;
			case 4: return $section->id_produkt;
		}
	}

	protected function getNameFromMySet($poradi=1){
		if(!$this->context->session->hasSection('mySetting')){return "";}
		$section = $this->context->session->getSection('mySetting');
		switch($poradi) {
			case 1: return $section->firma;
			case 2: return $section->osoba;
			case 3: return $section->nabidka;
			case 4: return $section->produkt;
		}
	}

	public function getMySection() {
		  return $this->context->session->getSection('mySetting');
	}

 

	/**
	 * Návrat na page, ze které byla akce volána (jen výpis zprávy)
	 */
	public function goBack() {
		$url = $this->getHttpRequest()->getReferer();
		$url->appendQuery(array(self::FLASH_KEY => $this->getParam(self::FLASH_KEY)));
		$this->redirectUrl($url->absoluteUrl);
	}

	/** --------------------------------------------------------------**/

	 protected function resetDefaultRender(&$form){
			// reset default render
			$renderer = $form->getRenderer();
			$renderer->wrappers['controls']['container'] = NULL;
			$renderer->wrappers['pair']['container'] = NULL;
			$renderer->wrappers['label']['container'] = NULL;
			$renderer->wrappers['control']['container'] = NULL;

	 }
 

}