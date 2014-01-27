<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;


class K2Presenter extends NakupPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'K2 položky zboží';
    const TITUL_PRICES 	= 'Ceny položek K2 zboží';

	private $titul;
	/** @var Nette\Database\Table\Selection */
	//private $items;
	private $k2param;
	

	public function startup()
	{
		parent::startup();
		$k2param = $this->getContext()->parameters['k2'];
		$this->k2param = $k2param;
		//$instance = new K2($k2param);
		//$items = $instance->show();
	}


	/********************* view default *********************/



	public function renderDefault()
	{

		$this->redirect('find');
		
        $k2 = new K2($this->k2param);
		$rows = $k2->show()->fetchAll();
		// strankovani
		$paginator = $this['vp']->getPaginator();
		$paginator->itemsPerPage = 30;
		$paginator->itemCount = count($rows);
		$k2->limit = $paginator->getLength();
		$k2->offset = $paginator->getOffset();

		$result = $k2->show();

		$this->template->items = $result;
        $this->template->titul = self::TITUL_DEFAULT . "  (str. " . $paginator->page."/".$paginator->getPageCount() .")";

	}

	/**
	 * find K2 item by parts of name
	 * @param $id int .. id material when is found in K2
	 * @param $seek string .. find by edited parts of name
	 * @param $type if type="N" .. find by id
	 * @return mixed
	 */
	public function renderFind($id = 0, $seek = '', $type = '')
	{
        $k2 = new K2($this->k2param);
		
		$form = $this['findForm'];
		$this->template->titul = '';
		if($seek<>''){ 
			if($type==''){ // find by edited string
				$form['nazev']->value = $seek;
				$hledam = $seek;
				$this->template->titul = "Hledaný výraz: $seek";
			} else { // find by id k2
				$hledam = $seek;
			   	$this->template->titul = "Položka K2: $seek";
			}
		} else {
			if (!$form->isSubmitted()) {
				$hledam='';
				$item = false;
				if($type=='' && $id>0){
					$item = $k2->findMaterial($id)->fetch();
				}
				if($type==''){ // first find by string
					if($item){
						$hledam = $item->nazev;
						$form->setDefaults($item);
						$this->template->titul = "Hledaný výraz: $item->nazev";
					}
				}
			} else {
				$hledam = $form['nazev']->getValue();
			   	$this->template->titul = "Hledaný výraz: $item->nazev";
			}
		}

		$this->template->hledam = $hledam;
		
		$rows = $k2->findName($hledam, $type);

		// stránkování
		$paginator = $this['vp']->getPaginator(); 
		$paginator->itemsPerPage = 30;
		$paginator->itemCount = count($rows);
		$k2->limit = $paginator->getLength();
		$k2->offset = $paginator->getOffset();
		$rowp = $k2->findName($hledam, $type);				
		
		if($rowp){
			$cnt = count($rowp);
			if ($cnt<1) {
				$this->flashMessage("Hledaný výraz $hledam nebyl nalezen.",'exclamation');
			}
			$this->template->idm = $id;
			$this->template->items = $rowp;
		}
		$this->template->actidp = $this->getIdFromMySet(4);
	}

	/**
	 * Přiřadí položku materiálu k položce K2
	 * @param type $id .. id_materialu
	 * @param type $idz .. id_zbozi k2
	 * @param type $idc  .. id_ceny k2
	 */
	public function actionSelect($id, $idz, $idc)
	{
		if($id>0 && $idc>0){
			$k2 = new K2($this->k2param);
			$cena = $k2->findOnePrice($idc);
			if($cena->mena == "" || $cena->mena = "CZK") {
				$data = array('id_k2' => $idz, 'cena_cm' =>(float) $cena->cena, 'id_meny' => 1);
			} else {
				$data = array('id_k2' => $idz);
			}
		}
		$mat = new Material;
		$mat->updateK2id((int) $id, $data);
		$this->redirect('Material:default');
	}

	/**
	 * K položce materiálu přiřadí nákupní cenu
	 * @param type $idmat
	 * @param type $idcena 
	 */
	public function actionSetPrice($idmat, $idcena)
	{
		$nk2 = new K2($this->k2param);
		$price = (array) $nk2->findOnePrice($idcena);
		if($price){
			$mena = $price['mena'];
			$model = new Model;
			$curr = (array) $model->getCurrency();
			$imena=0;
			//zjištění id_meny dle podobnosti jejího názvu s číselníkem = brutus
			foreach ($curr as $k => $v){
				if(strlen($mena)>=strlen($v)){
					//bud zleva
					if(strpos($mena, strtoupper($v))===false){
						//jinak fakt nejde zjistit, zda tam je nebo není
					} else {
						$imena = $k;
					}
				} else {
					//nebo zprava
					if(strpos(strtoupper($v), $mena)===false){
					} else {
						$imena = $k;
					}
				}
			}
			if($imena == 0){
				$this->flashMessage("POZOR: Zvolená cena má neznámou měnu, doplňte ji do číselníku a zadejte kurz.",'exclamation');
				$imena=1;
			}
			$data = array(	
							'id_k2'		=> (int)  $price['id'],
							'cena_cm'	=> (float) $price['cena'],
							'id_meny'	=> (int) $imena
						);
		}
		$mat = new Material;
		if($mat->updateK2price((int) $idmat, $data)){
			$this->flashMessage("Cena a K2 číslo byly úspěšně aktualizovány.");
		}
				
		$this->redirect('Material:default');
	}

	
	/**
	 * K položce materiálu přiřadí nákupní cenu z posledních nákupů
	 * @param type $idmat
	 * @param type $idcena 
	 */
	public function actionSetPriceValue($idmat, $id_k2, $cena)
	{
		if($cena>0 && $idmat>0 && $id_k2>0){
			$data = array(	
							'id_k2'		=> (int)  $id_k2,
							'cena_cm'	=> (float) $cena,
							'id_meny'	=> 1
						);
		}
		$mat = new Material;
		if($mat->updateK2price((int) $idmat, $data)){
			$this->flashMessage("Cena a K2 číslo byly úspěšně uloženy.");
		}
				
		$this->redirect('Material:default');
	}
	
	
	/**
	 * Show prices from K2
	 * @param type $id_mat
	 * @param type $id_k2 
	 */
	public function renderPrices($id_mat = 0, $id_k2 = 0)
	{
        $instance = new K2($this->k2param);
		$rows = $instance->findPrices($id_k2);
		$pol = $rows->fetchSingle();
		$rows = $instance->findPrices($id_k2);
		$data = $rows->fetchAll();
		$cnt = count($data);
		$ppur = $instance->lastPurchase($id_k2);
		$purch = $ppur->fetchAll();
		$cnp = count($purch);
		
		if ($cnt<1 && $cnp<1) {
			$this->flashMessage("Žádné ceny ani dodávky pro položku nebyly nalezeny.",'exclamation');
			$this->redirect('Material:default');
		} else {
			$this->template->subtitle = "$id_k2 - $pol";
			$this->template->idm = $id_mat;
			$this->template->items = $data;
			$this->template->actidp = $this->getIdFromMySet(4);
		}
		$this->template->polozka = $pol;
		$this->template->czbo = $id_k2;
		$this->template->cnp = $cnp;
		$this->template->purch = $purch;
		switch ($cnp){
			case 1:
				$txlabel = "poslední nákup";
				break;
			case $cnp<5:
				$txlabel = "poslední nákupy";
				break;
			default:
				$txlabel = "posledních nákupů";
		}
		$this->template->txlabel = $txlabel;
		
	}

	
	
	/********************* component factories *********************/
	
	/**
	 * stat edit form component factory.
	 * @return mixed
	 */
	protected function createComponentFindForm()
	{
		$form = new Form;
		$form->addText('nazev', 'Hledám:', 65)
					->setRequired('Zadejte hledaný název položky, nebo jeho část(i).')
					->setAttribute('placeholder', 'hledaný výraz');		
		$form->addSubmit('find', 'Najít')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Zpět')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'findFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function findFormSubmitted(Form $form)
	{
		$id = (int) $this->getParam('id');
		if ($form['find']->isSubmittedBy()) {
			$item = new K2($this->k2param);
			$data = (array) $form->values;
			$seek = $data['nazev'];
			$this->redirect('find', $id, $seek, '');
		}
		if ($form['cancel']->isSubmittedBy()) {
			$this->redirect('Material:default');
		} else {
			$this->redirect('find', $id, $seek,'');
		}
	}


	
}
