<?php

use Nette\Application\UI\Form,
	Nette\Application as NA,
	Vodacek\Forms\Controls\DateInput;
/*
 * Kurz presenter
 */

class KurzPresenter extends ObchodPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Kurzy';
    const TITUL_ADD 	= 'Nový kurzy';
    const TITUL_EDIT 	= 'Změna kurzu';
    const TITUL_DELETE 	= 'Smazání kurzu';
    /*
	 * @var string
	 * @titul
	 */ 
	private $titul;
	/** @var Nette\Database\Table\Selection */
	private $items;


	public function startup()
	{
		parent::startup();
        $item = new Kurz;
		$this->items = $item->show();
	}


	/********************* view default *********************/


	/*
	 * @return void
	 */
	public function renderDefault()
	{

		$item = new Kurz;
		$this->template->items = $item->show()->orderBy('platnost_do');
        $this->template->titul = self::TITUL_DEFAULT;

	}



	/********************* views add & edit *********************/


	/*
	 * @return void
	 */
	public function renderAdd()
	{
		$this['itemForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADD;
		$this->template->is_addon = TRUE;

	}


	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderEdit($id = 0)
	{
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
			$item = new Kurz;
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


	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderDelete($id = 0)
	{
		$item = new Kurz;
		$this->template->item = $item->find($id)->fetch();
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

		$currency = new Model;
		$mzkratka = $currency->getCurrency();
		$form->addSelect('k_id_meny', 'Měna:', $mzkratka)
			        ->setPrompt('[..zvolte měnu..]');
			        //->addRule(Form::FILLED, 'Vyberte měnu.');

		$form->addText('m_nazev', 'Nová měna:');
		$form->addText('m_zkratka', 'Zkratka měny:');
 
		$form->addText('k_kurz_nakupni', 'Kurz nákupní:', 10, 13)
				->setRequired('Uveďte kurz nákupní.')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Kurz musí být celé nebo reálné číslo.');

		$form->addText('k_kurz_prodejni', 'Kurz prodejní:', 10, 13)
				->setRequired('Uveďte kurz prodejní.')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Kurz musí být celé nebo reálné číslo.');
			
		$form->addDate('k_platnost_od', 'Platnost od:', DateInput::TYPE_DATE)
				->setRequired('Uveďte platnost kurzu od.');

		$form->addDate('k_platnost_do', 'Platnost do:', DateInput::TYPE_DATE);

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
			$item = new Kurz;
			$kurz = $item->getPrefixedFormFields($form->values,'k_');
			$mena = $item->getPrefixedFormFields($form->values,'m_');
			$kurz['kurz_nakupni'] = floatval($kurz['kurz_nakupni']);
			$kurz['kurz_prodejni'] = floatval($kurz['kurz_prodejni']);
			$kurz['platnost_od'] = $item->getDateStringForInsertDB($kurz['platnost_od']);
			$kurz['platnost_do'] = $item->getDateStringForInsertDB($kurz['platnost_do']);
			if ($id > 0) {
				$id_meny = (int) $kurz['id_meny'];
				//není-li vybraná měna a je zadána jako nová - bude insert měny
				if ($id_meny == 0 && $mena['nazev']<>'' && $mena['zkratka']<>'') {
					$id_meny = $item->insertCis('meny', (array) $mena);
					$kurz['id_meny'] = (string) $id_meny;
				}
				$item->update($id, (array) $kurz);
				$this->flashMessage('Kurz byl změněn.');
			} else {
				$id_meny = (int) $kurz['id_meny'];
				//není-li vybraná měna a je zadána jako nová - bude insert měny
				if ($id_meny == 0 && $mena['nazev']<>'' && $mena['zkratka']<>'') {
					$id_meny = $item->insertCis('meny', (array) $mena);
					$kurz['id_meny'] = (string) $id_meny;
				}

				$item->insert((array) $kurz);
				$this->flashMessage('Kurz byl přidán.');
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
		$form->addProtection('Please submit this form again (security token has expired).');
		return $form;
	}



	public function deleteFormSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$item = new Kurz;
			$item->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}

}
