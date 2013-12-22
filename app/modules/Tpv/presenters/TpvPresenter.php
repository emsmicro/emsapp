<?php

/*
 * Tpv presenter
 */

class TpvPresenter extends SecuredPresenter
{	
	/**
	 * @var array
	 * @navigace
	 */
	private $navigace;
	/** Title constants */
    const TITUL_DEFAULT = 'Stránka TPV';
    const TITUL_SUBTITL = 'Přehled funkcí modulu';
	private $todos	= array(
							'Operace'	=> array(
											'Přehled výrobních operací aktuálního produktu.'=>'Operace:default',
											'Hromadné přidání výrobních operací k produktu.'=>'Operace:addGroup',
											'Individuelní přidání výrobních operací produktu.'=>'Operace:add'
											),
							'Typové operace'	=> array(
											'Přehlde typových operací.' => 'TypOperace:default',
											'Přidání typové operace do TPV.'=>'TypOperace:add',
												),
							'Sazby typových operací'=> array(
											'Přehled setů sazeb typových operací dle období platnosti.' => 'SetSazebO:default',
											'Hromadné i individuální zadání sazeb v rámci setu sazeb typových operací.'=>'',
												)
							);

	public function startup()
	{
		parent::startup();
	    $menu = array(
	            'TPV'			=> 'Tpv',
	            'Postupy'		=> 'Postup',
	            'Operace'		=> 'Operace',
	            'Šablony TP'	=> 'Sablona',
	            'Typy operací'	=> 'TypOperace',
	            'Sazby operací'	=> 'SetSazebO',
	            'Atributy času'	=> 'AtrCasu',
				'Stroje'		=> 'Stroj',
	            'Obchod'		=> 'Obchod',  
	            'Nákup'			=> 'Nakup',
		        );
        $this->navigace = $menu;
	}
	
	/**
	 * @return void
	 */
	public function renderDefault()
	{
        $this->template->titul = self::TITUL_DEFAULT;
        $this->template->subtitle = self::TITUL_SUBTITL;
		
		$prod = new Produkt;
		$nezapocate = $prod->showByStatus(4);
		$this->template->nezapocate = $nezapocate;
		
		$nedokoncene = $prod->showByStatus(5);
		$this->template->nedokoncene = $nedokoncene;
		$this->template->goPresenter = "Operace:default";
		
		$this->template->is_todo = $this->is_todo;
		if($this->is_todo){$this->template->todos = $this->todos;}
	}
		
	/**
	 * list of product operations
	 * @param type $id id_produkty
	 */
	public function renderOperace($id){
		$this->setIntoMySet(4, $id);
		$this->redirect('Operace:');
	}
	
	/**
	 * Vytváří vrchní navigační panel
	 * @param array
	*/

	protected function createComponentNavigation($name) {
		$this->MakeMenu($name, $this->navigace);
	}

}
