<?php



class NakupPresenter extends SecuredPresenter
{
	private $navigace;
    const TITUL_DEFAULT = 'Stránka nákupu';
    const TITUL_SUBTITL = 'Přehled funkcí modulu';
	private $todos	= array(
							'Materiál'	=> array(
											'Evidence materiálových kusovníků (BOM)'=>'Material:default',
											'Přidání materiálu do kusovníku (jednotlivě)'=>'Material:add',
											'Přiřazení zvolené položky položce zboží z K2 a výběr ceny'=>''
											),
							'Import BOM'	=> array(
											'Import materiálového kusovníku z CSV souboru pomocí průvodce'=>'Import:default'
												),
							'K2'		=> array(
											'Vyhledávání materiálových položek v K2 zboží dle názvu položky' => 'K2:default'
												)
							);

	
	public function startup()
	{
		parent::startup();

		$menu = array(
	            'Nákup'			=> 'Nakup',
				'Materiál'		=> 'Material',
				'Import BOM'	=> 'Import',
				'K2'			=> 'K2',
	            'Obchod'		=> 'Obchod',  //dalsší moduly zařadit až na konec menu
	            'TPV'			=> 'Tpv',
	            //'Správa'		=> 'Sprava'   //dalsší moduly zařadit až na konec menu
		        );
        $this->navigace = $menu;
	}
	
	public function renderDefault()
	{
        $this->template->titul = self::TITUL_DEFAULT;
        $this->template->subtitle = self::TITUL_SUBTITL;
		
		$mat = new Material;
		$data = $mat->countNoPrices();
		$this->template->bezcen = $data;

		$prod = new Produkt;
		$nezapocate = $prod->showByStatus(3);
		$this->template->nezapocate = $nezapocate;
		
		$neocenene = $prod->showByStatus(6);
		$this->template->neocenene = $neocenene;
		$this->template->goPresenter = "Material";
		
		$this->template->is_todo = $this->is_todo;
		if($this->is_todo){$this->template->todos = $this->todos;}
	}
	
	
	/**
	 * list of product operations
	 * @param type $id id_produkty
	 */
	public function actionMaterial($id){
		$this->setIntoMySet(4, $id);
		$this->redirect('Material:');
	}

	/*
	 * Vytváří vrchní navigační panel
	 * @param array
	*/
	
	protected function createComponentNavigation($name) {
		$this->MakeMenu($name, $this->navigace);
	}


}
