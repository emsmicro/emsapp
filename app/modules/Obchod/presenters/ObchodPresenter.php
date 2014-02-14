<?php

	

class ObchodPresenter extends SecuredPresenter
{
	private $navigace;
    const TITUL_DEFAULT = 'Stránka obchodu';
    const TITUL_SUBTITL = 'Přehled funkcí';
	private $todos	= array(
							'Nabídky'	=> array(
											'Přehled nabídek.'=>'Nabidka:default',
											'Založení nové nabídky'=>'Nabidka:add',
											'Sledování historie stavu nabídky'=>'',
											),
							'Produkty'	=> array(
											'Přehled produktů.'=>'Produkt:default',
											'Založení nového produktu'=>'Produkt:add',
											'Sledování historie stavu produktu'=>'',
											'Kalkulace nákladů produktu'=>'',
											'Ocenění produktu podle velikosti výrobní dávky a měny'=>'',
												),
							'Zákazníci'	=> array(
											'Přehled zákazníků.'=>'Firma:default',
											'Založení nového zákazníka'=>'Firma:add',
											'Kontaktní údaje zákazníků.'=>'',
												),
							'Osoby'	=> array(
											'Přehled osoba zákazníků.'=>'Osoba:default',
											'Založení nové osoby'=>'Osoba:add',
												),
							'Kurzy'	=> array(
											'Přehled kurzů měn dle doby platnosti.'=>'Kurz:default',
											'Založení nového kurzu měny'=>'Kurz:add',
												),
							'Sazby režií'=> array(
											'Přehled setů režijních sazeb dle doby platnosti.'=>'SetSazeb:default',
											'Založení nového setu režijních sazeb'=>'SetSazeb:add',
											'Hromadné zadání režijních sazeb v rámci setu.'=>'',
												),
							);

	
	public function startup()
	{
		parent::startup();
	    $menu = array(
	            'Obchod'	=> 'Obchod',
                'Nabídky'   => 'Nabidka',
				'Produkty ' => 'Produkt',
	            'Zákazníci' => 'Firma',
	            'Osoby'		=> 'Osoba',
	            'Kurzy'		=> 'Kurz',
	            'Sazby režií'	=> 'SetSazeb',
				'TPV'		=> 'Tpv',
				'Nákup'		=> 'Nakup',
				//'Správa'	=> 'Sprava',
		        );
        $this->navigace = $menu;
		// Kurzy
		$this['rater']->setPath($this->myvar['rates']);
		//$this['rater']->setCurrencies(array('EUR','USD','GBP:3'));
		$this->template->is_rates = TRUE;
		
	}
	
	
	public function renderDefault()
	{
		
		$this->redirect('dash');

		$this->template->titul = self::TITUL_DEFAULT;
        $this->template->subtitle = self::TITUL_SUBTITL;

		$this->template->is_todo = $this->is_todo;
		if($this->is_todo){$this->template->todos = $this->todos;}
		if($this->getIdFromMySet(4)>0){$this->redirect('graph');}

	}

	/**
	 * Make Pie Graph as Dashboard actual price of product
	 */
	public function renderDash()
	{
        $this->template->titul = "Dashboard - obchod";
        $this->template->subtitle = self::TITUL_SUBTITL;

		$nab = new Nabidka;
		$nezapocate = $nab->showByStatus(1);
		$this->template->pnezap = count($nezapocate);
		$this->template->nezapocate = $nezapocate;
		
		$neocenene = $nab->showByStatus(7);
		$this->template->pneoc = count($neocenene);
		$this->template->neocenene = $neocenene;
		
		$data = $nab->getSummary()->fetchAll();
		$gdata = $nab->dataMoreForGraph($data, 0, 2);
		$catg = substr($gdata,0,strpos($gdata,"][")+1);
		$valg = substr($gdata,strpos($gdata,"][")+1);
		$this->template->catg_bar = $catg;
		$this->template->data_bar = $valg;
		
		$pid = $this->getIdFromMySet(4);
		$nproduct="";
		$data="";
		if($pid>0){
			$product = new Produkt;
			$data = $product->getProdPrice4PieGraph($pid);
			$nproduct = $this->getNameFromMySet(4);
		}
		$this->template->data = $data;
		$this->template->nproduct = $nproduct;
		$this->template->is_todo = $this->is_todo;
		if($this->is_todo){$this->template->todos = $this->todos;}
	}
	

	/**
	 * Vytváří vrchní navigační panel
	 * @param array
	*/		

	protected function createComponentNavigation($name) {
		$this->MakeMenu($name, $this->navigace);
	}
	
	
	
}
