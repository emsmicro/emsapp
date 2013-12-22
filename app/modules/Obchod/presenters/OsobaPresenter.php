<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;


class OsobaPresenter extends ObchodPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Osoby';
    const TITUL_ADD 	= 'Nová osoba';
    const TITUL_EDIT 	= 'Změna osoby';
    const TITUL_DELETE 	= 'Smazání osoby';

	/**
	 * @var string
	 * @titul
	 */ 
	private $titul;
	private $ido=0;
	
	
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

        $instance = new Osoba;
		$this->template->osoby = $instance->show()->orderBy('ofirma');
        $this->template->titul = self::TITUL_DEFAULT;

	}
	/*
	 * @return void
	 */
	public function renderPeople($id=0)
	{
        $instance = new Osoba;
		if($id==0){
			$id = $instance->getIdFromMySet(1);
		}
		$this->template->osoby = $instance->showPeople($id);
        $this->template->titul = "Kontakty - ".$this->getNameFromMySet(1);
	}
	/********************* view default *********************/
	/*
	 * @param int
	 * @return void
	 */
	public function renderDetail($id = 0)
	{
        $instance = new Osoba;
		$osoba = $instance->find($id)->fetch();
		
		$this->setIntoMySet(2, $id, 1);

		$this->template->osoba = $osoba;
	   	$this->template->titul = $osoba->titul_pred .' '. $osoba->prijmeni .' '. $osoba->jmeno .' '. $osoba->titul_za;
		$kont = new Kontakt;
        $kontakty = $kont->showO($id);
        $this->template->kontakty = $kontakty;
		$this->template->ido = $osoba->id;
		$this->ido = $osoba->id;
	}

	/********************* views add & edit *********************/


	/*
	 * @return void
	 */
	public function renderAdd()
	{
   		$section = $this->context->session->getSection('mySetting');
		if ((int) $section->id_firma > 0){
			$this['osobaForm']['id_firmy']->value = $section->id_firma;
			$this['osobaForm']['a_redir']->value = $section->id_firma;
	        $this->template->titul = "Nový kontakt - ".$section->firma;
		} else {
			$this['osobaForm']['a_redir']->value = 0;
	        $this->template->titul = self::TITUL_ADD;
		}
		$this['osobaForm']['save']->caption = 'Přidat';
	}
	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderEdit($id = 0)
	{
		$form = $this['osobaForm'];
		if (!$form->isSubmitted()) {
	        $instance = new Osoba;
            $row = $instance->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
			$section = $this->context->session->getSection('mySetting');
			if ((int) $section->id_firma > 0){
				$form['id_firmy']->value = $section->id_firma;
				$form['a_redir']->value = $section->id_firma;
		        $this->template->titul = "Změna kontaktu - ".$row->prijmeni." ".$row->jmeno;
			} else {
				$form['a_redir']->value = 0;
		        $this->template->titul = self::TITUL_EDIT;
			}
		}

	}



	/********************* view delete *********************/

	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */

	public function renderDelete($id = 0)
	{
        $instance = new Osoba;
		$this->template->osoba = $instance->find($id)->fetch();
		if (!$this->template->osoba) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = self::TITUL_DELETE;

	}
	

	/********************* contacts *********************/
	/*
	 * @param int
	 * @return void
	 */
	public function renderAddContact($ido = 0)
	{
		$this['contactForm']['save']->caption = 'Přidat';
		$this['contactForm']['id_osoby']->value = $ido;
        $this->template->titul = "Nový kontakt";
	}
	/*
	 * @param int, int
	 * @return void
	 * @throws BadRequestException
	 */	
	public function renderEditContact($id, $ido)
	{	$contact = new Kontakt;
		$this['contactForm']['id_osoby']->value = $ido;
		$form = $this['contactForm'];
		if (!$form->isSubmitted()) {
			
            $row = $contact->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = "Změna kontaktu";

	}
	/*
	 * @param int, int
	 * @return void
	 * @throws BadRequestException
	 */	
	public function renderDeleteContact($id,$ido)
	{
		$contact = new Kontakt;
		$this->template->contact = $contact->find($id)->fetch();
		if (!$this->template->contact) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = "Výmaz kontaktu";

	}

	
		/********************* osoba component factories *********************/



	/**
	 * Osoba edit form component factory.
	 * @return mixed
	 */
	protected function createComponentOsobaForm()
	{
		$form = new Form;
   		$section = $this->context->session->getSection('mySetting');
		if ((int) $section->id_firma > 0){
			$form->addHidden('id_firmy');
		} else {
			$comp = new Model;
			$ofirmy = $comp->getCompany();
			$form->addSelect('id_firmy', 'Firma:', $ofirmy)
				        ->setPrompt('Zvolte firmu')
				        ->addRule(Form::FILLED, 'Zvolte firmu');
		}
		$form->addHidden('a_redir');

        $form->addText('jmeno', 'Jméno:')
			->setRequired('Uveďte jméno.');

		$form->addText('prijmeni', 'Přijmení:')
			->setRequired('Uveďte přijmení.');

        $form->addText('jmeno2', 'Druhé jméno:');

        $form->addText('titul_pred', 'Titul před jménem:');

        $form->addText('titul_za', 'Titul za jménem:');

        $form->addTextArea('poznamka', 'Poznámka:')
            ->addRule(Form::MAX_LENGTH, 'Poznámka je příliš dlouhá', 2000);

		$oslov = new Model;
		$oosloveni = $oslov->getOsloveni();
		$form->addSelect('id_osloveni', 'Oslovení:', $oosloveni)
			        ->setPrompt('Zvol oslovení');

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'osobaFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function osobaFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
	        $instance = new Osoba;
			$data = $instance->getPrefixedFormFields($form->values);
			if ($id > 0) {
				$instance->update($id, $data);
				$this->flashMessage('Osoba byla změněna.');
			} else {
				$instance->insert($data);
				$this->flashMessage('Osoba byla přidána.');
			}
		}
		$cil = $this->getParam('redir');
		if ($cil>0) {
			$this->redirect('people, '.$cil);
		} else {
			$this->redirect('default');
		}
	}



	/**
	 * Osoba delete form component factory.
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
	        $instance = new Osoba;
			$instance->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}



	
	/********************* contact component factories *********************/



	/**
	 * contact edit form component factory.
	 * @return mixed
	 */
	protected function createComponentContactForm()
	{
		$form = new Form;
		$comp = new Model;
		$ktyp = $comp->getContactType($this->ido);
		$form->addSelect('id_typy_kontaktu', 'Typ:', $ktyp)
			        ->setPrompt('[..zvolte typ..]')
			        ->addRule(Form::FILLED, 'Zvol typ');

		$form->addText('hodnota', 'Hodnota:')
			->setRequired('Uveďte hodnotu.');
		$form->addHidden('id_osoby');

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'contactFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function contactFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$contact = new Kontakt;
			$data = $contact->getPrefixedFormFields($form->values);
			if ($id > 0) {
				$contact->update($id, $data);
				$this->flashMessage('Kontakt byl změněn.');
			} else {
				$contact->insert($data);
				$this->flashMessage('Kontakt byl přidán.');
			}
		}

		$this->redirect('detail' ,$this->getParam('ido'));
	}



	/**
	 * contact delete form component factory.
	 * @return mixed
	 */
	protected function createComponentDeleteContact()
	{
		$form = new Form;
		$form->addSubmit('delete', 'Smazat')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno');
		$form->onSuccess[] = callback($this, 'deleteContactSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function deleteContactSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$item = new Kontakt;
			$item->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('detail',$this->getParam('ido'));
	}

}
	
	
