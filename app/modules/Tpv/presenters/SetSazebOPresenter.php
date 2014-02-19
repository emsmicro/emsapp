<?php

use Nette\Application\UI\Form,
	Nette\Application as NA,
	Vodacek\Forms\Controls\DateInput;

/**
 * Set sazeb operaci presenter
 */

class SetSazebOPresenter extends TpvPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Sety sazeb typových operací';
    const TITUL_ADD 	= 'Nový set sazeb typových operací';
    const TITUL_EDIT 	= 'Změna setu sazeb typových operací';
    const TITUL_DELETE 	= 'Smazání setu sazeb typových operací';
    const TITUL_GROUP 	= 'Hromadné zadání sazeb typových operací';
    /**
	 * @var string
	 * @titul
	 */  
	private $titul;
	/** @var Nette\Database\Table\Selection */
    private $items;
	/** @var Nette\Database\Table\Selection */
	private $sazby;
	/*
	 * @var int
	 * @tid
	 * @idss
	 */
	private $idss;
	


	public function startup()
	{
		parent::startup();
		$item = new SetSazebO;
		$this->items = $item->show();

	}


	/********************* view default *********************/

	/**
	 * @return void
	 */

	public function renderDefault()
	{
		$item = new SetSazebO;
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
        $sets = new SetSazebO;
		$item = $sets->find($id)->fetch();
		$sett = $sets->getActualTarifParam();
		$this->template->settar = $sett['nazev'];
		$this->template->item = $item;
	   	$this->template->titul = $item->nazev;
		$saz = new SazbaO;	
        $sazby = $saz->show($id)->fetchAll();
		//dd($sazby,'SAZBY');
        $this->template->sazby = $sazby;
		$this->template->idss = $item->id;
		$this->idss = $item->id;
	}

	
	public function actionUpdSazby($id)
	{
		$sazby = new SazbaO;
		$res = $sazby->updateSetSazeb($id);
		if($res){
			$this->flashMessage("Sazby typových operací byly aktualizovány dle aktuálních sazeb strojů a tarifních tříd.");
		} else {
			$this->flashMessage("Nepodařilo se aktualizovat všechny sazby typových operací.", "exclamation");
		}
		//dd($res);
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
			$item = new SetSazebO;
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
		$item = new SetSazebO;
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
			$sazba = new SazbaO;
			$this['rateForm']['save']->caption = 'Přidat';
			$this['rateForm']['id_typy_operaci']->value = $tid;
			$this['rateForm']['id_set_sazeb_o']->value = $idss;
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
		$sazba = new SazbaO;
		$this['rateForm']['id_typy_operaci']->value = $tid;
		$this['rateForm']['id_set_sazeb_o']->value = $idss;
		$this->template->titul = "Změna sazby: ".$sazba->getTypSazby($tid);
		$form = $this['rateForm'];
		if (!$form->isSubmitted()) {
            $row = $sazba->find($sid)->fetch();
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
		$oper = new SazbaO;
		$data = $oper->getTypesOper($id)->fetchAll();
		$ssazb = new SetSazebO;
		$row = $ssazb->find($id)->fetch();
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
	}
	/********************* view delete rate *********************/
	/**
	 * @param int
	 * @throws BadRequestException
	 * @return void
	 */
	public function renderDeleteRate($sid, $idss)
	{
		$sazba = new SazbaO;
		$this->template->rate = $sazba->find($sid)->fetch();
		if (!$this->template->rate) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = "Výmaz sazby";
	}



	/********************* component factories *********************/



	/**
	 * Item add and edit form component factory.
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
			$item = new SetSazebO;
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



	public function deleteFormSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$item = new SetSazebO;
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
		$form->addText('hodnota', 'Hodnota:')
				->setRequired('Uveďte hodnotu sazby.')
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$form->addHidden('id_set_sazeb_o');
		$form->addHidden('id_typy_operaci');

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
			$rate = new SazbaO;
			$data = (array) $form->values;
			$data['hodnota'] = floatval($data['hodnota']);
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
			$item = new SazbaO;
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
		$oper = new SazbaO;
		$id_set_sazeb_o = $this->getParam('id');
		$data = $oper->getTypesOper($id_set_sazeb_o)->fetchAll();
		$container = $form->addContainer('mpole');
		$i = 0;
		foreach($data as $k => $v){
			$i++;
			$container->addText('hodnota'.$i, 'Hodnota:')->setValue($v['hodnota'])
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
			$container->addHidden('hodn'.$i)->setValue($v['hodnota']);
			$container->addHidden('idto'.$i)->setValue($v['idto']);
			$container->addHidden('idso'.$i)->setValue($v['idso']);
		}
		$form->addHidden('id_set_sazeb_o')->setValue($id_set_sazeb_o);
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
			$sazba = new SazbaO;
			$res = $sazba->saveGroupRate($form['mpole']->values, $form['id_set_sazeb_o']->value);
			$this->flashMessage($res['message']);
		} elseif ($form['saveas']->isSubmittedBy()) {
			$sazba = new SazbaO;
			$res = $sazba->saveGroupRate($form['mpole']->values, $form['id_set_sazeb_o']->value, 1);
			$id = $res['id'];
			$this->flashMessage($res['message']." Editujete nový set sazeb operací.");
			$this->redirect('addGroup', $id);
		}

		$this->redirect('detail', $id);
	}	
	
}
