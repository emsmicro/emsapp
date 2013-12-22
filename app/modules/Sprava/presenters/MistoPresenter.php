<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;
/**
 * Misto presenter
 */

class MistoPresenter extends SpravaPresenter
{
    /** Title constants */
    const TITUL_ADDS 	= 'Nový stát';
    const TITUL_EDITS 	= 'Změna státu';
    const TITUL_DELETES = 'Smazání státu';
	const TITUL_ADDK 	= 'Nový kraj';
    const TITUL_EDITK 	= 'Změna kraj';
	const TITUL_ADDO 	= 'Nová obec';
    const TITUL_EDITO 	= 'Změna obce';
     /**
	 * @var string
	 * @titul
	 */ 
	private $titul;
	/** @var Nette\Database\Table\Selection */
	private $stat = 'staty';
	private $kraj = 'kraje';
	private $obec = 'obce';


	public function startup()
	{
		parent::startup();

	}


	/********************* view default *********************/

	/*
	 * @return void
	 */

	public function renderDefault()
	{

		$item = new Misto;
		$this->template->stat = $item->showCis($this->stat);
		$this->template->kraj = $item->showCis($this->kraj);
		$this->template->obec = $item->showCis($this->obec);
		$mysec = parent::getMySection();
	}



	/********************* views add *********************/
	/*
	 * @return void
	 */

	public function renderAdds()
	{
		$this['statForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADDS;

	}
	
		public function renderAddk()
	{
		$this['krajForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADDK;

	}
	
		public function renderAddo()
	{
		$this['obecForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADDO;

	}
	/********************* views edit *********************/
	/*
	 * @param int
	 * @throws BadRequestException
	 * @return void
	 */

	public function renderEdits($id = 0)
	{
		$form = $this['statForm'];
		if (!$form->isSubmitted()) {
			$item = new Misto;
            $row = $item->find($id,$this->stat)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDITS;

	}
	
		public function renderEditk($id = 0)
	{
		$form = $this['krajForm'];
		if (!$form->isSubmitted()) {
			$item = new Misto;
            $row = $item->find($id,$this->kraj)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDITK;

	}
	
		public function renderEdito($id = 0)
	{
		$form = $this['obecForm'];
		if (!$form->isSubmitted()) {
			$item = new Misto;
            $row = $item->find($id,$this->obec)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDITO;

	}



	/********************* view delete *********************/

	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 

	public function renderDelete($id = 0)
	{
		$item = new Misto;
		$this->template->item = $item->find($id,$this->stat)->fetch();
		if (!$this->template->item) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = self::TITUL_DELETES;

	}
	*/


	/********************* component factories *********************/



	/**
	 * Stat edit form component factory.
	 * @return mixed
	 */
	protected function createComponentStatForm()
	{
		$form = new Form;
		$form->addText('zkratka', 'Zkratka:')
			->setRequired('Uveďte zkratku.');

		$form->addText('nazev', 'Název:')
			->setRequired('Uveďte název.');

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'statFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function statFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$item = new Misto;
			if ($id > 0) {
				$item->updateCis($this->stat,$id, $form->values);
				$this->flashMessage('Položka byla změněna.');
			} else {
				
				$item->insertCis($this->stat,$form->values);
				$this->flashMessage('Položka byla přidána.');
			}
		}
		$this->redirect('default');
	}

	/********************* component factories *********************/



	/**
	 * Kraj edit form component factory.
	 * @return mixed
	 */
	protected function createComponentKrajForm()
	{
		$form = new Form;
		$form->addText('zkratka', 'Zkratka:')
			->setRequired('Uveďte zkratku.');

		$form->addText('nazev', 'Název:')
			->setRequired('Uveďte název.');

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'krajFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function krajFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$item = new Misto;
			if ($id > 0) {
				$item->updateCis($this->kraj,$id, $form->values);
				$this->flashMessage('Položka byla změněna.');
			} else {
				
				$item->insertCis($this->kraj,$form->values);
				$this->flashMessage('Položka byla přidána.');
			}
		}
		$this->redirect('default');
	}
	
	
/********************* component factories *********************/



	/**
	 * Obec edit form component factory.
	 * @return mixed
	 */
	protected function createComponentObecForm()
	{
		$form = new Form;

		$form->addText('nazev', 'Název:')
			->setRequired('Uveďte název.');

		$form->addText('psc', 'PSČ:')
			->setRequired('Uveďte PSČ.');
		
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'obecFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function obecFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$item = new Misto;
			if ($id > 0) {
				$item->updateCis($this->obec,$id, $form->values);
				$this->flashMessage('Položka byla změněna.');
			} else {
				
				$item->insertCis($this->obec,$form->values);
				$this->flashMessage('Položka byla přidána.');
			}
		}
		$this->redirect('default');
	}

	/**
	 * Item delete form component factory.
	 * @return mixed
	 
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
			$item = new Misto;
			$item->delete($this->getParam('id'),$this->stat);
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}
	*/
}
