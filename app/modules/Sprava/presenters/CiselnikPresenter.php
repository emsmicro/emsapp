<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;
/*
 * Stat presenter
 */

class CiselnikPresenter extends SpravaPresenter
{
    const TITUL_DEFAULT = 'Číselník';
    const TITUL_ADD 	= 'Nový záznam';
    const TITUL_EDIT 	= 'Změna záznamu';
    const TITUL_DELETE 	= 'Smazání záznamu';
	private $table;

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

		$item = new Ciselnik;
		$this->template->tcen = $item->showCis('typy_cen');
		$this->template->dfirem = $item->showCis('druhy_firem');
		$this->template->doperaci = $item->showCis('druhy_operaci');
		$this->template->merjed = $item->showCis('merne_jednotky');
		$this->template->osloveni = $item->showCis('osloveni');
		$this->template->role = $item->showCis('role');
		$this->template->tnakladu = $item->showCis('typy_nakladu');
		$this->template->tsazeb = $item->showCis('typy_sazeb');
		$this->template->tkontaktu = $item->showCis('typy_kontaktu');
		$mysec = parent::getMySection();
	}


	/********************* views add & edit *********************/


	/*
	 * @return void
	 */
	public function renderAdd($table='')
	{
		$this->table = $table;
        $this->template->titul = self::TITUL_ADD;
	}


	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderEdit($id=0, $table='')
	{
		$this->table = $table;
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
	        $instance = new Ciselnik;
            $row = $instance->find($id, $table)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDIT;
	}

	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderDelete($id=0, $table='')
	{
		$this->table = $table;
        $instance = new Ciselnik;
		$this->template->item = $instance->find($id, $table)->fetch();
		if (!$this->template->item) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = self::TITUL_DELETE;

	}



	/********************* component factories *********************/



	/**
	 * Item edit form component factory.
	 * @return mixed
	 */
	protected function createComponentItemForm()
	{
		$form = new Form;
		$inst= new Ciselnik;
		$table = $this->table;
		if($table){
			$cols = $inst->getColumns($table)->fetchAll();
			foreach ($cols as $col) {
				$form->addText($col['name'], $col['name'].":" );	
			}
		}
		
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = array($this, 'itemFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function itemFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$table = $this->getParam('table');
			$item = new Ciselnik;
			$data = (array) $form->values;
			if ($id > 0) {
				$item->update($id, $table, $data);
				$this->flashMessage('Data byla změněna.');
			} else {
				$idn = $item->insert($table, $data);
				$this->flashMessage('Data byla přidána.');
			}
		}
		$this->redirect('default');
	}
	
	
	
	
}
