<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2010 John Doe
 * @package    MyApplication
 */

use Nette\Application\UI\Form,
	Nette\Application as NA;

/**
 * Homepage presenter.
 *
 * @author     John Doe
 * @package    MyApplication
 */

final class HomepagePresenter extends BasePresenter
{
    const TITUL_DEFAULT = 'Úvodní stránka aplikace EMS nabídky';
    const TITUL_SUBTITL = 'Přehled modulů';

	private $todos	= array(
							'Obchod'=> array(
											'Evidence zákazníků a jejich kontaktů'=>'Firma',
											'Evidence osob zákazníků a jejich kontaktů'=>'Osoba',
											'Evidence nabídek vč. sledování jejich stavu rozpracovanosti'=>'Nabidka',
											'Evidence produktů vč. jejich ocenění a sledování stavu'=>'Produkt',
											'Evidence nákupních a prodejních kurzů měn'=>'Kurz',
											'Evidence režijních sazeb - jejich zahrnití do setů sazeb'=>'SetSazeb',
											),
							'TPV'	=> array(
											'Tvorba technologických postup zadáním dle typových výrobních operací'=>'Operace',
											'Evidence typových výrobních operací'=>'TypOperace',
											'Evidence sazeb typových operací - jejich zahrnutí do setů sazeb'=>'SetSazebO',
											),
							'Nákup'		=> array(
											'Evidence materiálových rozpisek produktů vč. cen'=>'Material',
											'Import materiálových rozpisek z CSV souborů'=>'Import',
											'Přístup do K2 pro přiřazení materiálu k položkám zboží a jejich ocenění'=>'K2',
											),
							'Správa'	=> array(
											'Správa uživatelů a rolí'=>'Uzivatel',
											'Správa číselníků míst'=>'Misto',
											'Zobrazení ostatních číselníků'=>'Ciselnik'
											),
							);

    public function startup()
    {
        parent::startup();
	}
	
    public function actionLogout()
    {
        $this->getUser()->logOut();
        $this->flashMessage('Právě jste se odlásili ze systému.');
        $this->redirect('Sign:in');
    }

	public function renderDefault()
	{
        $this->template->titul = self::TITUL_DEFAULT;
        $this->template->subtitle = self::TITUL_SUBTITL;
		//$this->template->islogin = !$this->getUser()->isLoggedIn();
		$this->template->aoffer = $this->getIdFromMySet(3);
		$this->template->aproduct = $this->getIdFromMySet(4);
		$this->template->is_todo = $this->is_todo;
		if($this->is_todo){$this->template->todos = $this->todos;}
		
	}

	public function renderEditMe($id = 0)
	{
		$form = $this['passForm'];
		if (!$form->isSubmitted()) {
	        $item = new Uzivatel;
            $row = $item->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = 'Změna údajů uživatele';
		$this->template->uzivatel = $row->jmeno. ' ' . $row->prijmeni;

	}

	public function actionSetCompany()
	{
		$this->redirect('Firma:');
	}
	
	public function actionSetOffer($id)
	{
		$this->setIntoMySet(2, $id);
		$this->redirect('Produkt:product',$id);
	}

	public function actionSetProduct($id)
	{
		$this->setIntoMySet(3, $id);
		if(in_array('Nakup', $this->user->getRoles())){
			$this->redirect('Material:');
		}
		if(in_array('Tpv', $this->user->getRoles())){
			$this->redirect('Operace:');
		}
		if(in_array('Sprava', $this->user->getRoles())){
			$this->redirect('Sprava:');
		}
		$this->redirect('Produkt:detail',$id);
	}
	
	
	protected function actionSetEraseSet(){
		//$this->template = $this->template->setFile(self::APP_DIR . "/Obchod/templates/firma/default.latte"); 
		$this->eraseMySet();
		$this->redirect("Firma:default");
	}


	protected function createComponentPassForm()
	{
		$form = new Form;
		$form->addText('username','Username:')
			->setRequired('Uveďte uživatelské jméno');
		
        $form->addPassword('password', 'Heslo:')
			->setRequired('Uveďte heslo.');

        $form->addPassword('a_password', 'Heslo pro kontrolu:')
			->setRequired('Uveďte heslo znovu.')
			->addRule(Form::EQUAL, 'Hesla se neshodují', $form['password']);
		
		$form->addText('jmeno','Jméno:');
		$form->addText('prijmeni','Přijmení:');
		
		$form->addText('email','Email:', 30)
			->setRequired('Uveďte email');		

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'passFormSubmitted');

		$form->addProtection('Vypršel ochranný časový limit, odešlete prosím formulář ještě jednou.');
		return $form;
	}


	public function passFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
	        $item = new Uzivatel;
			$data = $item->getPrefixedFormFields($form->values);

			$data['password'] = md5($data['password']);
			if ($id > 0) {
				$item->update($id, $data);
				$this->flashMessage('Údaje byly změněny.');
			}
		}
		$this->redirect('default');
	}
}

