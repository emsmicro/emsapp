<?php

use Nette\Application\UI\Form,
	Nette\Application as NA,
	Vodacek\Forms\Controls\DateInput;

/**
 * Set sazeb operaci presenter
 */

class SetTarifuPresenter extends TpvPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Sety tarifních sazeb';
    const TITUL_ADD 	= 'Nový set tarifních sazeb';
    const TITUL_EDIT 	= 'Změna setu tarifních sazeb';
    const TITUL_DELETE 	= 'Smazání setu tarifních sazeb';
    const TITUL_GROUP 	= 'Hromadné zadání tarifních sazeb';
    /**
	 * @var string
	 * @titul
	 */  
	private $titul;
	/** @var Nette\Database\Table\Selection */
    private $items;
	/** @var Nette\Database\Table\Selection */
	private $tarify;
	/*
	 * @var int
	 * @tid
	 * @idss
	 */
	private $idss;
	


	public function startup()
	{
		parent::startup();
		$item = new SetTarifu;
		$this->items = $item->show();

	}


	/********************* view default *********************/

	/**
	 * @return void
	 */

	public function renderDefault()
	{
		$item = new SetTarifu;
		$this->template->items = $item->show();
        $this->template->titul = self::TITUL_DEFAULT;
	}
	/********************* views detail *********************/
	/**
	 * @param int
	 * @return void
	 */
	
	public function renderDetail($id = 0)
	{
        $sett = new SetTarifu;
		$item = $sett->find($id)->fetch();

		$this->template->item = $item;
	   	$this->template->titul = $item->nazev;
		$tar = new Tarif;	
        $tarify = $tar->show($id)->fetchAll();
//		dd($item,'TAR');
		$tarify = $sett->calcSazba($tarify, $this->mpars);
		//dd($tarify,'TARIFS');
//		dd($this->mpars,'PARAMS');
        $this->template->sazby = $tarify;
		$this->template->idss = $item->id;
		$this->idss = $item->id;
	}


	public function actionUpdTarify($id)
	{
		$tar = new Tarif;	
        $tarify = $tar->show($id)->fetchAll();
		$sett = new SetTarifu;
		$tarify = $sett->calcSazba($tarify, $this->mpars);
		$res = $sett->updateTarify($id, $tarify);
		if($res){
			$this->flashMessage("Tarifní třídy byly aktualizovány dle aktuálních parametrů setu tarifních sazeb.");
		} else {
			$this->flashMessage("Nepodařilo se aktualizovat všechny sazby tarifních tříd.", "exclamation");
		}
		$this->redirect('detail', $id);
	}
	
	/********************* views add & edit *********************/

	/**
	 * @return void
	 */

	public function renderAdd()
	{
		$this['itemForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADD;
		$this->template->is_addon = TRUE;

	}

	/**
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */

	public function renderEdit($id = 0)
	{
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
			$item = new SetTarifu;
            $row = $item->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDIT;
		$this->template->is_addon = TRUE;

	}



	/********************* view delete *********************/

	/**
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */

	public function renderDelete($id = 0)
	{
		$item = new SetTarifu;
		$this->template->item = $item->find($id)->fetch();
		if (!$this->template->item) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = self::TITUL_DELETE;

	}
	/********************* view add rate *********************/
	/**
	 * @param int, int
	 * @return void
	 */
	public function renderAddRate($tid, $idss)
	{
			$tarif = new Tarif;
			$ntarif = $tarif->getTypTarifu($tid)->nazev;
			$this['rateForm']['save']->caption = 'Přidat';
			$this['rateForm']['id_typy_tarifu']->value = $tid;
			$this['rateForm']['id_set_tarifu']->value = $idss;
			$this->template->titul = "Nový tarif: $ntarif";
	}

	/********************* view edit rate *********************/
	/**
	 * @param int, int, int
	 * @throws BadRequestException
	 * @return void
	 */
	
	public function renderEditRate($sid, $tid, $idss)
	{
		$tarif = new Tarif;
		$ntarif = $tarif->getTypTarifu($tid)->nazev;
		$this['rateForm']['id_typy_tarifu']->value = $tid;
		$this['rateForm']['id_set_tarifu']->value = $idss;
		$this->template->titul = "Změna tarifu: $ntarif";
		$form = $this['rateForm'];
		if (!$form->isSubmitted()) {
            $row = $tarif->find($sid)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
	}
	
	/**
	 * @param int id = id_set_sazeb_o
	 * @return void
	 */
	public function renderAddGroup($id=0)
	{
		$tar = new Tarif;
		$data = $tar->getTypesTarif($id)->fetchAll();
		$star = new SetTarifu;
		$row = $star->find($id)->fetch();
		$naz = "";
		if ($row){
			$naz = $row['nazev'];
		}
        $this->template->titul = self::TITUL_GROUP;
		$this->template->subtitul = $naz;
		
		$form = $this['addGroupForm'];
		// reset default render
		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = NULL;
		$renderer->wrappers['pair']['container'] = NULL;
		$renderer->wrappers['label']['container'] = NULL;
		$renderer->wrappers['control']['container'] = NULL;
		$this->template->items = $data;
		$this->template->form = $form;
		//dd($data);
	}
	/********************* view delete rate *********************/
	/**
	 * @param int
	 * @throws BadRequestException
	 * @return void
	 */
	public function renderDeleteRate($sid, $idss)
	{
		$tarif = new Tarif;
		$ntarif = $tarif->getTypTarifu($sid)->nazev;
		$this->template->rate = $tarif->find($sid)->fetch();
		if (!$this->template->rate) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = "Výmaz tarifu: $ntarif";
	}



	/********************* component factories *********************/



	/**
	 * Item add and edit form component factory.
	 * @return mixed
	 */
	protected function createComponentItemForm()
	{
		$form = new Form;
 
		$form->addText('nazev', 'Název:', 60)
				->setRequired('Uveďte název.');

		$form->addDate('platnost_od', 'Platnost od:', DateInput::TYPE_DATE)
				->setRequired('Uveďte platnost od.');

		$form->addDate('platnost_do', 'Platnost do:', DateInput::TYPE_DATE);

        $form->addText('dny_pracovni', 'Počet pracovních dnů:', 3)
			->setRequired('Uveďte počet pracovních dnů v aktuálním roce.')
			->setAttribute('class', 'tcislo')
			->setOption('description', '[dnů/rok]')
			->addCondition($form::FILLED)
					->addRule($form::INTEGER, 'Počet pracovních dnů: Hodnota musí být celé číslo.');
		
        $form->addText('dny_dovolena', 'Počet dnů dovolené:', 3)
			->setRequired('Uveďte počet dnů dovolené v aktuálním roce.')
			->setAttribute('class', 'tcislo')
			->setOption('description', '[dnů/rok]')
			->addCondition($form::FILLED)
					->addRule($form::INTEGER, 'Počet dnů dovolené: Hodnota musí být celé číslo.');
		
        $form->addText('dny_svatky', 'Počet dnů svátků:', 3)
			->setRequired('Uveďte počet dnů svátků v aktuálním roce.')
			->setAttribute('class', 'tcislo')
			->setOption('description', '[dnů/rok]')
			->addCondition($form::FILLED)
					->addRule($form::INTEGER, 'Počet dnů svátků: Hodnota musí být celé číslo.');
		
        $form->addText('dny_nemoc', 'Prům. počet dnů nemoci:', 3)
			->setAttribute('class', 'tcislo')
			->setOption('description', '[dnů/rok]')
			->addCondition($form::FILLED)
					->addRule($form::INTEGER, 'Prům. počet dnů nemoci: Hodnota musí být celé číslo.');
		
        $form->addText('dny_odstavky', 'Počet dnů odstávky strojů:', 3)
			->setAttribute('class', 'tcislo')
			->setOption('description', '[dnů/rok]')
			->addCondition($form::FILLED)
					->addRule($form::INTEGER, 'Počet dnů odstávky strojů: Hodnota musí být celé číslo.');

		$form->addText('podil_prescasu', 'Podíl přesčasů:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[% z ročního fondu]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Podíl přesčasů: Hodnota musí být celé nebo reálné číslo.');
		
        $form->addText('doch_bonus', 'Docházkový bonus:', 3)
			->setAttribute('class', 'tcislo')
			->setOption('description', '[Kč/měsíc]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::INTEGER, 'Docházkový bonus: Hodnota musí být celé nebo reálné číslo.');

		$form->addText('priplatky', 'Příplatky / náhrady:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[% z Tarif. mzdy], za přečasy, směny, náhrady za nemoc')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Příplatky / náhrady: Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('stravne', 'Stravné:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[Kč/den], příspěvek zaměstnavatele')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Stravné: Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('penzijni_poj', 'Penzijní připojištění:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[%], příspěvek zaměstnavatele')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Penzijní připojištění: Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('odmeny', 'Násobek platu na odměny:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[ks/rok], počet platů na odměny v roce')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Násobek platu na odměny: Hodnota musí být celé nebo reálné číslo.');

		$form->addText('navyseni_prumeru', 'Navýšení průměru:')
			->setRequired('Uveďte navýšení průměrné hodinové sazby pro náhrady za dovolenou a svátky z titulu ročních odměn.')
			->setAttribute('class', 'cislo')
			->setOption('description', '[Kč/h] (z titulu výplaty ročních odměn za minulý rok)')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Odvody SZP: Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('odvody', 'Odvody SZP:')
			->setRequired('Uveďte sazbu sociálního a zdravotního pojištění.')
			->setAttribute('class', 'cislo')
			->setOption('description', '[%]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Odvody SZP: Hodnota musí být celé nebo reálné číslo.');

		$form->addText('stroj_kapcita_sm', 'Strojní kapacita:',3)
			->setAttribute('class', 'tcislo')
			->setRequired('Uveďte plánovanou roční kapacitu stroje v jednosměnném provoze.')
			->setOption('description', '[hod/směna/rok]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::INTEGER, 'Strojní kapacita: Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('smennost_strojni', 'Plán. strojní směnnost:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[směn/den], počet směn za den (1 až 3)')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Strojní kapacita: Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('rucni_smena', 'Délka směny:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[hod/směna], (pro výpočet počtu pracovníků)')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Délka směny: Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('naklady_plochy', 'Náklady na plochu:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[Kč/m2], (odpisy/údržba/opravy ploch)')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Náklady na m2 plochy: Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('cena_elekriny', 'Cena elektřiny:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[Kč/kWh]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Cena elektřiny: Hodnota musí být celé nebo reálné číslo.');

		$form->addText('cena_dusiku', 'Cena dusíku:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[Kč/m3]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Cena dusíku: Hodnota musí být celé nebo reálné číslo.');

		$form->addText('urokova_mira', 'Úroková míra:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[%]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Úroková míra: Hodnota musí být celé nebo reálné číslo.');
		
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'itemFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function itemFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$item = new SetTarifu;
			$sets = (array) $form->values;
			$sets['platnost_od'] = $item->getDateStringForInsertDB($sets['platnost_od']);
			$sets['platnost_do'] = $item->getDateStringForInsertDB($sets['platnost_do']);
			if ($id > 0) {
				$item->update($id,(array) $sets);
				$this->flashMessage('Položka byla změněna.');

			} else {
				$item->insert((array) $sets);
				$this->flashMessage('Položka byla přidána.');
			}
		}
		$this->redirect('detail', $id);
	}



	/**
	 * Item delete form component factory.
	 * @return mixed
	 */
	protected function createComponentDeleteForm()
	{
		$form = new Form;
		$form->addSubmit('delete', 'Smazat')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno');
		$form->onSuccess[] = callback($this, 'deleteFormSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function deleteFormSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$item = new SetTarifu;
			$item->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}
/********************* rate component factories *********************/



	/**
	 * Rate add and edit form component factory.
	 * @return mixed
	 */
	protected function createComponentRateForm()
	{
		$form = new Form;
		$form->addText('tarif', 'Tarifní sazba:')
				->setRequired('Uveďte tarifní hodinovou sazbu.')
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('hodnota', 'Kalkulační hodnota:')
				->setRequired('Uveďte kalkulační hodnotu tarifu.')
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$form->addHidden('id_set_tarifu');
		$form->addHidden('id_typy_tarifu');

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'rateFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function rateFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('sid');
			$rate = new Tarif;
			$data = (array) $form->values;
			$data['tarif'] = floatval($data['tarif']);
			$data['hodnota'] = floatval($data['hodnota']);
			if ($id > 0) {
				$rate->update($id, $data);
				$this->flashMessage('Položka tarifu byla změněna.');
			} else {
				$rate->insert($data);
				$this->flashMessage('Položka tarifu byla přidána.');
			}
		}

		$this->redirect('detail',$this->getParam('idss'));

	}



	/**
	 * Rate delete form component factory.
	 * @return mixed
	 */
	protected function createComponentDeleteRate()
	{
		$form = new Form;
		$form->addSubmit('delete', 'Smazat')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno');
		$form->onSuccess[] = callback($this, 'deleteRateSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function deleteRateSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$item = new Tarif;
			$item->delete($this->getParam('sid'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('detail',$this->getParam('idss'));
	}

	/**
	 * AddGroup - rate of operation
	 * @return \Nette\Application\UI\Form
	 */
	protected function createComponentAddGroupForm()
	{
		$form = new Form;
		$tar = new Tarif;
		$id_set_tarifu = $this->getParam('id');
		$data = $tar->getTypesTarif($id_set_tarifu)->fetchAll();
		$container = $form->addContainer('mpole');
		$i = 0;
		foreach($data as $k => $v){
			$i++;
			$container->addText('tarif_'.$i, 'Tarifní sazba:')->setValue($v['tarif'])
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
			$container->addText('hodnota_'.$i, 'Kalk. hodnota:')->setValue($v['hodnota'])
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
			
			$container->addHidden('tarf_'.$i)->setValue($v['tarif']);
			$container->addHidden('hodn_'.$i)->setValue($v['hodnota']);
			$container->addHidden('idto_'.$i)->setValue($v['idto']);
			$container->addHidden('idso_'.$i)->setValue($v['idso']);
		}
		$form->addHidden('id_set_tarifu')->setValue($id_set_tarifu);
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('saveas', 'Uložit jako kopii');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'groupoFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	public function groupoFormSubmitted(Form $form)
	{
		$id = $this->getParam('id');

		if ($form['save']->isSubmittedBy()) {
			$sazba = new Tarif;
			$res = $sazba->saveGroupRate($form['mpole']->values, $form['id_set_tarifu']->value);
			$this->flashMessage($res['message']);
		} elseif ($form['saveas']->isSubmittedBy()) {
			$sazba = new Tarif;
			$res = $sazba->saveGroupRate($form['mpole']->values, $form['id_set_tarifu']->value, 1);
			$id = $res['id'];
			$this->flashMessage($res['message']." Editujete nový set tarifů.");
			$this->redirect('addGroup', $id);
		}

		$this->redirect('detail', $id);
	}	
	
}
