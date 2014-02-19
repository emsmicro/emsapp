<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;
/**
 * Uzivatel presenter
 */

class UzivatelPresenter extends SpravaPresenter
{
	/** @var Nette\Database\Table\Selection */
	private $items;
    /** Title constants */
	const TITUL_DEFAULT = 'Uživatelé';
    const TITUL_ADD 	= 'Nový uživatel';
    const TITUL_EDIT 	= 'Změna uživatele';
    const TITUL_DELETE 	= 'Smazání uživatele';
     /**
	 * @var string
	 * @titul
	 */     
	private $titul;


	public function startup()
	{
		parent::startup();
        $item = new Uzivatel;
		$this->items = $item->show();


	}


	/********************* view default *********************/

	/**
	 * @return void
	 */

	public function renderDefault()
	{

        $item = new Uzivatel;
		$this->template->items = $item->show()->orderBy('username');
        $this->template->titul = self::TITUL_DEFAULT;

	}



	/********************* views add & edit *********************/

	/**
	 * @return void
	 */

	public function renderAdd()
	{
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
		if ($this->user->getIdentity()->nrole=='Admin'){
			$formular = 'itemForm';
		} else {
			$formular = 'ritemForm';
		}
		$form = $this[$formular];
		if (!$form->isSubmitted()) {
	        $item = new Uzivatel;
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
        $item = new Uzivatel;
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
		$form->addText('username', 'UserName:')
			->setRequired('Uveďte název uživatele.');

        $form->addPassword('password', 'Heslo:');
//			->setRequired('Uveďte heslo.');

        $form->addPassword('a_password', 'Heslo:')
//			->setRequired('Uveďte heslo znovu.')
			->setOption('description', '(ještě jednou pro kontrolu)')
			->addRule(Form::EQUAL, 'Hesla se neshodují', $form['password']);

		$form->addText('jmeno', 'Jméno:');

		$form->addText('prijmeni', 'Příjmení:');

        $form->addText('email', 'E-mail:', 40)
			->setRequired('Uveďte e-mail.');

        $form->addTextArea('poznamka', 'Poznámka:')
            ->addRule(Form::MAX_LENGTH, 'Poznámka je příliš dlouhá', 255);

		/* Role */
		$comp = new Model;
		$urole = $comp->getRole();
		$form->addSelect('role', 'Role:', $urole)
				->setPrompt('Zvol roli')
				->addRule(Form::FILLED, 'Zvolte roli');
			
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
	        $item = new Uzivatel;
			//$pass = $this->getParam('a_password');
			$data = $item->getPrefixedFormFields($form->values);
			
			if ($id > 0) {
				if(trim($data['password'])==''){
					array_splice($data, 1, 1); 
				} else {
					$data['password'] = md5($data['password']);
				}
				$item->update($id, $data);
				$this->flashMessage('Uživatel byl změněn.');
			} else {
				if(trim($data['password'])==''){
					$form['save']->addError("Heslo u novéhu uživatele musí být zadáno!");
		            return ;
				}
				$item->insert($data);
				$this->flashMessage('Uživatel byl přidán.');
			}
		}
		$this->redirect('default');
	}
	
	/**
	 * Komponenta Redukovaná o heslo pro správce
	 */
	protected function createComponentRitemForm()
	{
		$form = new Form;
		$form->addText('username', 'UserName:')
			->setRequired('Uveďte název uživatele.');

		$form->addText('jmeno', 'Jméno:');

		$form->addText('prijmeni', 'Příjmení:');

        $form->addText('email', 'E-mail:')
			->setRequired('Uveďte e-mail.');

        $form->addTextArea('poznamka', 'Poznámka:')
            ->addRule(Form::MAX_LENGTH, 'Poznámka je příliš dlouhá', 255);

	/* Role */
		$comp = new Model;
		$urole = $comp->getRole();
		$form->addSelect('role', 'Role:', $urole)
				->setPrompt('Zvol roli')
				->addRule(Form::FILLED, 'Zvolte roli');
			
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'ritemFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	public function ritemFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
	        $item = new Uzivatel;
			//$pass = $this->getParam('a_password');
			$data = (array) $form->values;
			if ($id > 0) {
				$item->update($id, $data);
				$this->flashMessage('Uživatel byl změněn.');
			} else {
				$item->insert($data);
				$this->flashMessage('Uživatel byl přidán.');
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
	        $item = new Uzivatel;
			$item->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}

}
