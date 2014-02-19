<?php

use Nette\Application\UI\Form,
	Nette\Application as NA,
	Vodacek\Forms\Controls\DateInput;

/*
 * Set sazeb rezii presenter
 */
class SetSazebPresenter extends ObchodPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Sety sazeb';
    const TITUL_ADD 	= 'Nový set sazeb';
    const TITUL_EDIT 	= 'Změna setu sazeb';
    const TITUL_DELETE 	= 'Smazání setu sazeb';
	const TITUL_GROUP 	= 'Hromadné zadání sazeb režií';

	/**
	 * @var string
	 * @titul
	 */ 
	private $titul;
    private $items;
	private $sazby;

	/**
	 * @var int
	 * @idss
	 */
	private $idss;
	


	public function startup()
	{
		parent::startup();
		$item = new SetSazeb;
		$this->items = $item->show();

	}


	/********************* view default *********************/


	/*
	 * @return void
	 */
	public function renderDefault()
	{
		$item = new SetSazeb;
		$this->template->items = $item->show();
        $this->template->titul = self::TITUL_DEFAULT;
		$saz = new Sazba;	
//        $this->template->sazby = $sazby;
//		$this->template->idss = $item->id;
		$vzorce = $saz->getKalkVzorce();		
        $this->template->vzorce = $vzorce;
		$this->template->isakce = TRUE;
	}
	/********************* views detail *********************/
	/*
	 * @param int
	 * @return void
	 */
	public function renderDetail($id = 0)
	{
        $instance = new SetSazeb;
		$item = $instance->find($id)->fetch();
		if (!$item) {
			throw new NA\BadRequestException('Záznam nenalezen.');
		}
		$this->template->item = $item;
	   	$this->template->titul = $item->nazev;
		$saz = new Sazba;	
        $sazby = $saz->show($id);
        $this->template->sazby = $sazby;
		$this->template->idss = $item->id;
		$this->idss = $item->id;
		$vzorce = $saz->getKalkVzorce();
        $this->template->vzorec = $item['kalkulace'];
        $this->template->vzorce = $vzorce;
		$this->template->isakce = TRUE;
	}


	/********************* views add & edit *********************/


	/**
	 * Add item SetSazeb
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
			$item = new SetSazeb;
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
		$item = new SetSazeb;
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
			$sazba = new Sazba;
			$this['rateForm']['save']->caption = 'Přidat';
			$this['rateForm']['id_typy_sazeb']->value = $tid;
			$this['rateForm']['id_set_sazeb']->value = $idss;
			$this->template->titul = "Nová sazba: ".$sazba->getTypSazby($tid);
	}
	
	/********************* view edit rate *********************/
	/**
	 * @param int, int, int
	 * @throws BadRequestException
	 * @return void
	 */
	public function renderEditRate($sid, $tid, $idss)
	{
		$sazba = new Sazba;
		$this['rateForm']['id_typy_sazeb']->value = $tid;
		$this['rateForm']['id_set_sazeb']->value = $idss;
		$this->template->titul = "Změna: ".$sazba->getTypSazby($tid);
		$form = $this['rateForm'];
		if (!$form->isSubmitted()) {
            $row = $sazba->find($sid)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		//$this->template->titul = "Změna sazby";

	}
	
	/**
	 * @param int id = id_set_sazeb
	 * @return void
	 */
	public function renderAddGroup($id)
	{
		$tsazb = new Sazba;
		$form = $this['addGroupForm'];
		$data = $tsazb->getTypeRates($id)->orderBy('poradi');
		$form['id_set_sazeb']->value = $id;
		$ssazb = new SetSazeb;
		$row = $ssazb->find($id)->fetch();
		$naz = "";
		if ($row){
			$naz = $row['nazev'];
		}
        $this->template->titul = self::TITUL_GROUP;
		$this->template->subtitul = $naz;
		// reset default render
		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = NULL;
		$renderer->wrappers['pair']['container'] = NULL;
		$renderer->wrappers['label']['container'] = NULL;
		$renderer->wrappers['control']['container'] = NULL;
		$this->template->items = $data;
		$this->template->form = $form;
		$vzorce = $tsazb->getKalkVzorce();
        $this->template->vzorec = $row['kalkulace'];
        $this->template->vzorce = $vzorce;
		$this->template->isakce = false;

	}
	
	/********************* view delete rate *********************/
	/**
	 * @param int
	 * @throws BadRequestException
	 * @return void
	 */	
	public function renderDeleteRate($sid, $idss)
	{
		$sazba = new Sazba;
		$this->template->rate = $sazba->find($sid)->fetch();
		if (!$this->template->rate) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = "Výmaz sazby";
	}


	
	
	/**
	 * Add item Kalkulace
	 * @return void
	 */
	public function renderAddKalk()
	{
		$this['kalkForm']['save']->caption = 'Přidat';
		$m = new Model;
		$this->template->tceny = $m->getTableData("typy_cen ORDER BY poradi");
		$this->template->tnakl = $m->getTableData("typy_nakladu ORDER BY poradi");
		$this->template->tsazb = $m->getTableData("typy_sazeb ORDER BY poradi");
        $this->template->titul = "Nový kalkulační vzorec";

	}


	/**
	 * Edite item Kalkulace
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderEditKalk($id = 0)
	{
		$form = $this['kalkForm'];
		if (!$form->isSubmitted()) {
			$item = new SetSazeb;
            $row = $item->findKalk($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$m = new Model;
		$this->template->tceny = $m->getTableData("typy_cen ORDER BY poradi");
		$this->template->tnakl = $m->getTableData("typy_nakladu ORDER BY poradi");
		$this->template->tsazb = $m->getTableData("typy_sazeb ORDER BY poradi");
		$this->template->titul = "Změna kalkulačního vzorce ".$row->zkratka;

	}



	/********************* view delete *********************/


	/**
	 * Delet item Kalkulace
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderDeleteKalk($id = 0)
	{
		$item = new SetSazeb;
		$row = $item->findKalk($id)->fetch();
		$this->template->item = $row;
		if (!$this->template->item) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = "Smazání kalkulačního vzorce ".$row->zkratka;

	}
	
	
	

	/********************* component factories *********************/



	/**
	 * Item edit form component factory.
	 * @return mixed
	 */
	protected function createComponentItemForm()
	{
		$form = new Form;
 
		$form->addText('nazev', 'Název:')
				->setRequired('Uveďte název.');

			
		$form->addDate('platnost_od', 'Platnost od:', DateInput::TYPE_DATE)
				->setRequired('Uveďte platnost od.');

		$form->addDate('platnost_do', 'Platnost do:', DateInput::TYPE_DATE);
		
		$kv = new SetSazeb;
		$kvzor = $kv->getKalkul();
		$form->addSelect('kalkulace', 'Kalkulace:', $kvzor)
					->setPrompt('Zvolte kalkulační vzorec')
					->addRule(Form::FILLED, 'Zvolte kalkulační vzorec');

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
			$item = new SetSazeb;
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
		$this->redirect('default');
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


	/**
	 * submit delete Form
	 * @param Form $form 
	 */
	public function deleteFormSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$item = new SetSazeb;
			$item->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}

	

	/**
	 * contact edit form component factory.
	 * @return mixed
	 */
	protected function createComponentRateForm()
	{
		$form = new Form;
		$sazba = new Sazba;
        //$row = $sazba->find($this->getParam('sid'))->fetch();		
		$form->addText('hodnota', 'Hodnota [%]:')
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype->autocomplete('off')
				->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		//$tzkr = $row['zkratka'];
		//dd($tzkr,'FormVal');
//		if(strpos('ZasR,MatM', $tzkr)>-1){
			$form->addTextArea('pravidlo', 'Pravidlo:', 60, 4)
				->addRule(Form::MAX_LENGTH, 'Pravidlo je příliš dlouhé', 2000);
				//->setAttribute('style', 'width:150%;');
//		}
		$form->addHidden('id_set_sazeb');
		$form->addHidden('id_typy_sazeb');
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'rateFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}


	/**
	 * submit Rate Form
	 * @param Form $form 
	 */
	public function rateFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('sid');
			$rate = new Sazba;
			$data = (array) $form->values;
			$data['hodnota'] = ($data['hodnota']/100);
			if ($id > 0) {
				$rate->update($id, $data);
				$this->flashMessage('Položka byla změněna.');
			} else {
				$rate->insert($data);
				$this->flashMessage('Položka byla přidána.');
			}
		}

		$this->redirect('detail',$this->getParam('idss'));

	}


	/**
	 * contact delete form component factory.
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


	/**
	 * submit DeleteRate Form
	 * @param Form $form 
	 */
	public function deleteRateSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$item = new Sazba;
			$item->delete($this->getParam('sid'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('detail',$this->getParam('idss'));
	}

	/**
	 * factory form AddGroup Form
	 * used $form->addContainer
	 * @return Nette\Application\UI\Form 
	 */
	protected function createComponentAddGroupForm()
	{
		$form = new Form;
		$sazby = new Sazba;
		$id_set_sazeb = $this->getParam('id');
		$data = $sazby->getTypeRates($id_set_sazeb)->orderBy('poradi');
		$container = $form->addContainer('mpole');
		$i = 0;
		foreach($data as $row => $v){
			$i++;
			$container->addText('hodnota'.$i, 'Sazba [%]:')->setValue($v['hodnota']*100)
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype->autocomplete('off')
				->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
			$container->addText('pravidlo'.$i,  'Pravidlo:',200)->setValue($v['pravidlo']);
			$container->addHidden('hodn'.$i)->setValue($v['hodnota']*100);
			$container->addHidden('prav'.$i)->setValue($v['pravidlo']);
			$container->addHidden('idts'.$i)->setValue($v['id']);
			$container->addHidden('ids'.$i)->setValue($v['ids']);
		}
		$form->addHidden('id_set_sazeb')->setValue($id_set_sazeb);
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('saveas', 'Uložit jako kopii');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'groupsFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	/**
	 * submit AddGroup Form
	 * @param Form $form 
	 */
	public function groupsFormSubmitted(Form $form)
	{
		$id = $this->getParam('id');

		if ($form['save']->isSubmittedBy()) {
			$sazba = new Sazba;
			$res = $sazba->saveGroupRate($form['mpole']->values, $form['id_set_sazeb']->value);
			$this->flashMessage($res['message']);
		} elseif ($form['saveas']->isSubmittedBy()) {
			$sazba = new Sazba;
			$res = $sazba->saveGroupRate($form['mpole']->values, $form['id_set_sazeb']->value, 1);
			$id = $res['id'];
			$this->flashMessage($res['message']." Editujete nový set sazeb.");
			$this->redirect('addGroup', $id);
		}

		$this->redirect('detail', $id);
	}	

	
	/**
	 * Item Kalkulace edit/add form component factory.
	 * @return mixed
	 */
	protected function createComponentKalkForm()
	{
		$form = new Form;
 
		$form->addText('zkratka', 'Zkratka:')
				->setRequired('Uveďte zkratku.');

		$form->addText('nazev', 'Název:', 60)
				->setRequired('Uveďte název.');

		$form->addTextArea('popis', 'Popis:', 94, 10);
		$form->addTextArea('definice', 'Definice:', 72, 20)
			->setAttribute('style', 'font: 14px "Droid Sans Mono", "Courier new", Courier, monospace; padding: 8px; color: #1A4571;')
			->setAttribute('spellcheck', 'false');
		$form->addText('procedura', 'Procedura:')
				->setRequired('Uveďte název uložené procedury.');
		$form->addText('param', 'Parametry:')
				->setOption('description', '(parametry procedury)');
		$form->addText('mater_c', 'Cena materiálu:', 30)
				->setOption('description', '(definice kalkulace prodejní ceny materiálu)');

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('saveas', 'Uložit kopii');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'kalkFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}
	
	/**
	 * Submit Kalk Form
	 * @param Form $form 
	 */
	public function kalkFormSubmitted(Form $form)
	{
		$id = (int) $this->getParam('id');
		$item = new SetSazeb;
		if ($form['save']->isSubmittedBy()) {
			$sets = (array) $form->values;
			if ($id > 0) {
				$item->updateKalk($id,(array) $sets);
				$this->flashMessage('Položka byla změněna.');

			} else {
				$item->insertKalk((array) $sets);
				$this->flashMessage('Položka byla přidána.');
			}
		} else {
			if ($form['saveas']->isSubmittedBy()) {
				$sets = (array) $form->values;
				$item->insertKalk((array) $sets);
				$this->flashMessage('Kopie vzorce byla uložena.');
			}
		}
		$this->redirect('default');
	}
	
	/**
	 * Kalkulace delete form component factory.
	 * @return mixed
	 */
	protected function createComponentDeleteKalk()
	{
		$form = new Form;
		$form->addSubmit('delete', 'Smazat')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno');
		$form->onSuccess[] = callback($this, 'deleteKalkSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}


	/**
	 * submit DeleteKalk Form
	 * @param Form $form 
	 */
	public function deleteKalkSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$item = new SetSazeb;
			$item->deleteKalk($this->getParam('sid'));
			$this->flashMessage('Smazáno.');
		}
		$this->redirect('default');
	}
	
}
