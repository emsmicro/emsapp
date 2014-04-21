<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;
/**
 * Uzivatel presenter
 */

class AgendaPresenter extends SpravaPresenter
{
	/** @var Nette\Database\Table\Selection */
	private $items;
    /** Title constants */
	const TITUL_DEFAULT = 'Agendy/funkce aplikace';
    const TITUL_ADD 	= 'Nová agenda/funkce';
    const TITUL_EDIT 	= 'Změna agendy/funkce';
    const TITUL_DELETE 	= 'Smazání agendy/funkce';
     /**
	 * @var string
	 * @titul
	 */     
	private $titul;


	public function startup()
	{
		parent::startup();

	}


	/********************* view default *********************/

	/**
	 * @return void
	 */

	public function renderDefault()
	{

        $item = new Agenda;
		$this->template->items = $item->show()->orderBy('modul')->orderBy('presenter')->orderBy('poradi');
        $this->template->titul = self::TITUL_DEFAULT;

	}



	/********************* views add & edit *********************/

	/**
	 * @return void
	 */

	public function renderAdd($m='', $p='', $n=0)
	{
		$this['itemForm']['modul']->value = $m=='' ? null : $m ;
		$this['itemForm']['presenter']->value = $p;
		$this['itemForm']['poradi']->value = $n+1;
		$this['itemForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADD;

	}
	
	/**
	 * @param int
	 * @throws BadRequestException
	 * @return void
	 */
	public function renderEdit($id = 0)
	{
		$formular = 'itemForm';
		$form = $this[$formular];
		if (!$form->isSubmitted()) {
	        $item = new Agenda;
            $row = $item->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->formular = $formular;
		$this->template->titul = self::TITUL_EDIT;

	}



	/********************* view delete *********************/

	/**
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */

	public function renderDelete($id = 0)
	{
        $item = new Agenda;
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
		$mods = array(
			'Base' => 'Home (Base)',
			'Obchod' => 'Obchod',
			'Nakup' => 'Nákup',
			'Tpv' => 'TPV',
			'Sprava' => 'Správa',
		);
		$form->addSelect('modul', 'Modul:', $mods)
				->setPrompt('Zvol modul')
				->addRule(Form::FILLED, 'Zvolte modul aplikace.');
		
		$form->addText('presenter', 'Agenda:')
			->setRequired('Uveďte název agendy (presenteru).')
			->setOption('description', '(presenter)');

		$form->addText('poradi', 'Pořadí:',5)
				->addCondition($form::FILLED)
						->addRule($form::INTEGER, 'Hodnota musí být celé číslo.');

        $form->addText('funkce', 'Funkce:')
			->setRequired('Uveďte název funkce aplikace.');

        $form->addText('popis', 'Popis:', 60)
			->setRequired('Uveďte popis funkce aplikace.');

		$form->addSubmit('savenext', 'Uložit a další');
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'itemFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}
	
	public function itemFormSubmitted(Form $form)
	{
		$subsave = $form['save']->isSubmittedBy();
		$subsavn = $form['savenext']->isSubmittedBy();
		if ($subsave || $subsavn ) {
			$id = (int) $this->getParam('id');
			$poradi = $form['poradi']->value;
	        $item = new Agenda;
			$data = (array) $form->values;
			if ($id > 0) {
				$item->update($id, $data);
				$this->flashMessage('Agenda byla změněna.');
			} else {
				$m = $this->getParam('m');
				$p = $this->getParam('p');
				$item->insert($data);
				$this->flashMessage('Agenda byla přidána.');
			}
			if ($subsavn){
				$this->redirect('add',$m, $p, $poradi);
			} else {
				$this->redirect('default');
			}
		} else {
			$this->redirect('default');
		}
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
	        $item = new Agenda;
			$item->delete($this->getParam('id'));
			$this->flashMessage('Agenda byla smazána.');
		}
		$this->redirect('default');
	}

}
