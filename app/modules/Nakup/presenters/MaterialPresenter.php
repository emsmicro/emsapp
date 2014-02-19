<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;


class MaterialPresenter extends NakupPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Materiál';
    const TITUL_ADD 	= 'Nový materiál';
    const TITUL_EDIT 	= 'Změna materiálu';
    const TITUL_DELETE 	= 'Smazání materiálu';
    /*
	 * @var string
	 * @titul
	 */ 
	private $titul;
	/** @var Nette\Database\Table\Selection */
	private $items;
	private $idproduct;
	

	public function startup()
	{
		parent::startup();
		if(!$this->isMySet(4)){
			//lze pracovat, jen když je aktivován v MySetting nabídka/produkt
			$this->flashMessage('S modulem MATERIÁL nelze pracovat. Nebyl aktivován žádný produkt.','exclamation');
			$this->redirect('Nakup:default');
		}
		$this->idproduct = $this->getIdFromMySet(4);

	}


	/********************* view default *********************/


	/**
	 * show all materials
	 */
	public function renderDefault($id=0, $wh=0)
	{

        $mat = new Material;
		$addtitul = '';
		if($id>0){
			$this->setIntoMySet(4, $id);
			$this->idproduct = $this->getIdFromMySet(4);
		}
		$id = $this->idproduct;
		$idnabidka = $this->getIdFromMySet(3);
		$kalk = new Kalkul;
		$kmat = $kalk->getMatCoef($idnabidka);
		if($id){
			$addtitul = ' - BOM: '.$this->getNameFromMySet(4);
		}
		// Kurzy
		$rater = $this['rater'];
		$this->template->is_rates = TRUE;
		
		// User filter
		$ufilter = $this['uFilter'];
		$mat->filter = $ufilter->getFilter();
		$this->template->is_filter = TRUE;
		
		$rows = $mat->show($id, $wh);
		$cnt = count($rows);

			
		// stránkování
		$paginator = $this['vp']->getPaginator();
		$paginator->itemsPerPage = 30;
		$paginator->itemCount = $cnt;
		$mat->limit = $paginator->getLength();
		$mat->offset = $paginator->getOffset();
		$rowp = $mat->show($id, $wh);			
		
		if ($wh>=0){

			$this->template->items = $rowp;
			$is_rows = count($rowp)>0;
		} else {
			$this->template->items = $rowp;
			$is_rows = count($rowp)>0;
		}
		// data pro View
		$this->template->idp=$id;
		$this->template->koefmat = (float)$kmat['koef'];
		$ilocked = $mat->isProductLocked($id);
		$this->template->unlocked = $ilocked<1;
		$noprices = $mat->countNoSalePrices($id);
		$this->template->rows = count($rows);
		$this->template->noprices = $noprices;
		$summat = $mat->sumBOM($id);
		$sProdej = round($summat['sumProdej'],2);
		$sProAlt = round($summat['sumProAlt'],2);
		$this->template->sProdej = $sProdej;
		$this->template->sProAlt = $sProAlt;
		$this->template->noAltProdej = ($sProdej == $sProAlt or $sProAlt == 0);
		if (round($summat['sumNaklad'],2)>0){
			$this->template->sNaklad = round($summat['sumNaklad'],2);
			$this->template->procprd = (round($summat['sumProdej'],2)/round($summat['sumNaklad'],2)-1)*100;
			$this->template->procpra = (round($summat['sumProAlt'],2)/round($summat['sumNaklad'],2)-1)*100;
		} else {
			$this->template->sNaklad = 0.0001;
			$this->template->procprd = 0;
			$this->template->procpra = 0;
		}
		$this->template->stavy3 = $mat->getProductHistory($id,3);
		$this->template->stavy6 = $mat->getProductHistory($id,6);

		$this->template->is_rows = $is_rows;
		$this->template->co = $wh;
		$this->template->titul = self::TITUL_DEFAULT . $addtitul;
		
		$currdata = $mat->groupByCurrency($id);
		$cnt_curr = count($currdata);
		$vol_curr = $mat->dataPairsForGraph($currdata, 0, 3, 1, 1, $slice = 'EUR', $colors = array(11,1,8,2,4,5,6,7));
		if($cnt_curr==1){
			if($currdata[0]['value']<0.1){$currdata=FALSE;}
		}
		$this->template->currdata = $currdata;
		$this->template->vol_curr = $vol_curr;
		$this->template->cnt_curr = $cnt_curr;

	}


	/**
	 * list of materials
	 * @param type $what = 0..all, 1..without purch. price, 2..with purch. price
	 */
	public function renderList($what=0)
	{

        $mat = new Material;
		$kalk = new Kalkul;
		$addtitul = ' (veškerý)';
		$id = $this->idproduct;
		if($id){
			$addtitul = ' - BOM: '.$this->getNameFromMySet(4);
		}
		$idnabidka = $this->getIdFromMySet(3);

		// User filter
		$ufilter = $this['uFilter'];
		$mat->filter = $ufilter->getFilter();
		$this->template->is_filter = TRUE;

		$rows = $mat->show($id, $what);
		$paginator = $this['vp']->getPaginator(); 
		
		$paginator->itemsPerPage = 30;
		$paginator->itemCount = count($rows);

		$mat->limit = $paginator->getLength();
		$mat->offset = $paginator->getOffset();
		$rowp = $mat->show($id, $what);	
		
		$this->template->setFile(__DIR__ . '/../templates/Material/default.latte');
		$is_rows = count($rowp)>0;
		$ilocked = $mat->isProductLocked($id);
		$this->template->unlocked = $ilocked<1;
		$summat = $mat->sumBOM($id);
		$sProdej = round($summat['sumProdej'],2);
		$sProAlt = round($summat['sumProAlt'],2);
		$this->template->sProdej = $sProdej;
		$this->template->sProAlt = $sProAlt;

		$this->template->noAltProdej = ($sProdej == $sProAlt or $sProAlt == 0);
		if (round($summat['sumNaklad'],2)>0){
			$this->template->sNaklad = round($summat['sumNaklad'],2);
			$this->template->procprd = (round($summat['sumProdej'],2)/round($summat['sumNaklad'],2)-1)*100;
			$this->template->procpra = (round($summat['sumProAlt'],2)/round($summat['sumNaklad'],2)-1)*100;
		} else {
			$this->template->sNaklad = 0.0001;
			$this->template->procprd = 0;
			$this->template->procpra = 0;
		}
		
		$kmat = $kalk->getMatCoef($idnabidka);
		$this->template->koefmat = (float)$kmat['koef'];
		$noprices = $mat->countNoSalePrices($id);
		$this->template->noprices = $noprices;

		$this->template->rows = count($rows);

		$this->template->idp=$id;
		$this->template->stavy3 = $mat->getProductHistory($id,3);
		$this->template->stavy6 = $mat->getProductHistory($id,6);
		$this->template->is_rows = $is_rows;
		$this->template->co = $what;
		$this->template->items = $rowp;
        $this->template->titul = self::TITUL_DEFAULT . $addtitul;
		
		$currdata = $mat->groupByCurrency($id);
		$cnt_curr = count($currdata);
		$vol_curr = $mat->dataPairsForGraph($currdata, 0, 3, 1, 1, $slice = 'EUR', $colors = array(11,1,8,2,4,5,6,7));
		if($cnt_curr==1){
			if($currdata[0]['value']<0.1){$currdata=FALSE;}
		}
		$this->template->currdata = $currdata;
		$this->template->vol_curr = $vol_curr;
		$this->template->cnt_curr = $cnt_curr;
		
		

	}
	
	/********************* views detail *********************/
	/**
	 * show detail of material
	 * @param type $id = id_material
	 */
	public function renderDetail($id = 0)
	{
        $instance = new Material;
		$item = $instance->find($id, $this->idproduct)->fetch();

		$this->template->item = $item;
	   	$this->template->titul = $item->nazev;
	}

	/********************* views add & edit *********************/


	/**
	 * add item into db table
	 * @return void
	 */
	public function renderAdd()
	{
		$this['itemForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADD;

	}


	/**
	 * @param int id = id_material
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderEdit($id = 0)
	{
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
	        $instance = new Material;
            $row = $instance->find($id, $this->idproduct)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDIT;

	}
	/********************* views edit for reducted form *********************/
	
	/**
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderEditr($id = 0)
	{
		$form = $this['ritemForm'];
		if (!$form->isSubmitted()) {
	        $instance = new Material;
            $row = $instance->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDIT;

	}



	/********************* view delete *********************/


	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderDelete($id = 0)
	{
        $instance = new Material;
		$this->template->id = $id;
		if($id>0){
			$item = $instance->find($id)->fetch();
			$this->template->item = $item;
			$this->template->nazev = $item->nazev;
			if (!$this->template->item) {
				throw new Nette\Application\BadRequestException('Záznam nenalezen!');
			}
		} else {
			$id_produkt = $this->getIdFromMySet(4);
			$this['deleteForm']['id_produkt']->value = $id_produkt;
			if($id_produkt>0){
				$this->template->item = true;
				$this->template->nazev = "celý kusovník produktu ".$this->getNameFromMySet(4);
			} else {
				throw new Nette\Application\BadRequestException('Není vybrán produkt, záznamy nelze odstranit!');
			}
		}
		$this->template->titul = self::TITUL_DELETE;

	}

	/**
	 * Change status of product materials
	 * @param type $id = id_produkty
	 * @param type $status = id_stav
	 */
	public function actionChangeStatus($id, $status)
	{
		$prod = new Produkt;
		$prod->insertProductStatus($id, $status, $this->user->id);
		$this->flashMessage('Stav produktu byl změněn.');
		$this->goBack();
	}	

	
	
	/**
	 * export BOM
	 */
	public function renderExport()
	{

		$id = $this->idproduct;
		// nastavím hlavičky a název souboru, dále pošlu soubor do prohlížeče pro stažení
		header("Content-Type: application/csv, windows-1250");
		header("Content-Disposition: attachment;filename=bom_$id.csv");
		header("Cache-Control: max-age=0");
		
		
        $mat = new Material;
		$rows = $mat->show($id);
		$this->template->items = $rows;
		$this->template->idp=$id;
       // napojím data
		$this->template->registerHelper('iconv', function($value, $from = 'utf-8', $to = 'windows-1250') {
					return iconv($from, $to, $value);
			});		

	}	
	
	/**
	 * Přepočet prodejních cena materiálu
	 * @param type $id 
	 */
	public function actionMatPrice($id)
	{
		
		$kalk = new Kalkul;
		$ret = $kalk->calcMatPrices($id, $this->getIdFromMySet(3));
		if($ret['meze1'] or $ret['meze2']){
			$this->flashMessage("Prodejní ceny materiálových položek byly přepočteny dle pravidel: zásobovací režie: ".$ret['pravidlo1']. " marže: ".$ret['pravidlo2'].".");
		} else {
			$this->flashMessage("Prodejní ceny materiálových položek byly přepočteny koeficientem ".str_replace(".", ",", $ret['koef']).".");
		}
		$this->goBack();

	}	

	/**
	 * Vynulování prodejních cena materiálu
	 * @param type $id 
	 */
	public function actionMatPriceErase($id)
	{
        $kalk = new Kalkul;
		$kalk->recalMatPrices($id, 0);
		$this->flashMessage("Prodejní ceny materiálových položek byly vynulovány",'exclamation');
		$this->goBack();

	}	

	
	
	/********************* component factories *********************/	

	/**
	 * Item edit form component factory.
	 * @return mixed
	 */
	protected function createComponentItemForm()
	{
		$form = new Form;
		$form->addText('zkratka', 'Zkratka:', 40)
					->setRequired('Uveďte zkratku.');

		$form->addText('nazev', 'Název:', 55)
					->setRequired('Uveďte název.');
		
		$form->addText('id_k2', 'K2 číslo:',10);
		
		$form->addText('cena_cm', 'Cena/MJ:',10)
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$mat = new Material;
		$nakurzy = $mat->getActualPurchaseRates();

		$form->addSelect('id_kurzy', 'Kurz:', $nakurzy)
			        ->setPrompt('CZK');		

		$form->addText('p_pocet', 'Spotřeba [MJ]:',10)
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype->autocomplete('off')
				->addCondition($form::FILLED);
		
		$form->addText('cena_kc2', 'Prod. cena:',10)
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$mjs = $mat->getMeasureUnits();
		$form->addSelect('id_merne_jednotky', 'MJ:', $mjs);
		
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'itemFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}


	/**
	 * Submit item Form
	 * @param Form $form 
	 */
	public function itemFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$item = new Material;
			$data = (array) $form->values;
			$pocet = $item->getPrefixedFormFields($data,"p_");
			$data = $item->getPrefixedFormFields($data);
			$data['cena_cm'] = floatval($data['cena_cm']);
			if ($id > 0) {
				$item->update($id, $data, (int) $this->idproduct, (int) $pocet['pocet']);
				$this->flashMessage('Položka byla změněna.');
			} else {
				$item->insert($data, (int) $this->idproduct, (int) $pocet['pocet']);
				$this->flashMessage('Položka byla přidána.');
			}
		}

		$this->redirect('default');
	}
	
	/**
	 * Zredukovany formular o zkratku a nazev
	 */
	protected function createComponentRitemForm()
	{
		$form = new Form;

		$form->addText('id_k2', 'K2 číslo:');

		$form->addText('cena_cm', 'Cena/MJ:')
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		$mat = new Model;
		$nakurzy = $mat->getActualPurchaseRates();

		$form->addSelect('id_kurzy', 'Kurz:', $nakurzy)
			        ->setPrompt('CZK');

		$mjs = $mat->getMeasureUnits();
		$form->addSelect('id_merne_jednotky', 'MJ:', $mjs);

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'itemFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}


	/**
	 * Submit reduced item Form
	 * @param Form $form 
	 */
	public function ritemFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$item = new Material;
			$data = (array) $form->values;
			//$data['cena_kc'] = floatval($data['cena_kc']);
			$data['cena_cm'] = floatval($data['cena_cm']);
			if ($id > 0) {
				$item->update($id, $data);
				$this->flashMessage('Položka byla změněna.');
			} else {
				$item->insert($data);
				$this->flashMessage('Položka byla přidána.');
			}
		}
		$this->redirect('default');
	}


	/**
	 * Item delete form component factory.
	 * @return mixed
	 */
	protected function createComponentDeleteForm()
	{
		$form = new Form;
		$form->addHidden('id_produkt');
		$form->addSubmit('delete', 'Smazat')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno');
		$form->onSuccess[] = callback($this, 'deleteFormSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}


	/**
	 * Submit delete form
	 * @param Form $form 
	 */
	public function deleteFormSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$data = $form->values;
			$id = $this->getParam('id');
	        $instance = new Material;
			$idp = 0;
			if($id==0){
				$idp = (int) $data['id_produkt'];
				if($idp>0){
					$instance->delete($id,$idp);
					$this->flashMessage('Záznamy byly smazány.');
				} else {
					$this->flashMessage('Záznamy nelze smazat, není zvolen produkt.', 'exclamation');
				}
			} else {
				$instance->delete($id);
				$this->flashMessage('Smazáno.');
			}
		}

		$this->redirect('default');
	}
	
}
