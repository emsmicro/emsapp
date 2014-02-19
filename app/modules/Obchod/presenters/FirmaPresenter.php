<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;

/*
 * Set sazeb operaci presenter
 */
class FirmaPresenter extends ObchodPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Zákazníci';
    const TITUL_ADD 	= 'Nový zákazník';
    const TITUL_EDIT 	= 'Změna zákazníka';
    const TITUL_DELETE 	= 'Smazání zákazníka';
    private $titul;
	private $ido=0;




	public function startup()
	{
		parent::startup();
	}


	/********************* view default *********************/


	public function renderDefault()
	{
	/*
	 * @return void
	 */
        $instance = new Firma;
		// User filter
		$ufilter = $this['uFilter'];
		$instance->filter = $ufilter->getFilter();
		$this->template->is_filter = TRUE;
		
		$data = $instance->show()->orderBy('nazev')->fetchAll();
		$this->template->firmy = $data;
		//dumpBar($data, 'Data');
        $this->template->titul = self::TITUL_DEFAULT;
		
	}

	/********************* view detail *********************/
	/*
	 * @param int
	 * @return void
	 */
	public function renderDetail($id = 0)
	{
        $instance = new Firma;
		$firma = $instance->find($id)->fetch();
		$this->template->firma = $firma;
		$this->template->titul = $firma->nazev;

		$this->setIntoMySet(1, $id, 0);
		
		$kont = new Kontakt;
        $kontakty = $kont->showF($id);
        $this->template->kontakty = $kontakty;
		$this->ido = $firma->id;  
		$this->template->ido = $firma->id;
		
        $osoba = new Osoba;
		$this->template->osoby = $osoba->showPeople($id);
		
        $nab = new Nabidka;
		
		$rows = $nab->findByCompany($id);;
		$cnt = count($rows);
		// stránkování
		$paginator = $this['vp']->getPaginator(); 
		$paginator->itemsPerPage = 25;
		$paginator->itemCount = $cnt;
		$nab->limit = $paginator->getLength();
		$nab->offset = $paginator->getOffset();
		$rowp = $nab->findByCompany($id);;	
		$this->template->nabidky = $rowp;
		
		$prod = new Produkt;
		$prods = $prod->getOffersCompany($id);
		$this->template->produkty = $prods;
		//dd($prods, 'ProdCeny');
		//$this->template->nump = count($prods->fetchAll());
	}

	public function actionEraseSet(){
		$this->eraseMySet();
		$this->renderDefault();
	}

	/********************* views add & edit *********************/


	/*
	 * @return void
	 */
	public function renderAdd()
	{
		$this['firmaForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADD;

	}


	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderEdit($id = 0)
	{
		$form = $this['firmaForm'];
		if (!$form->isSubmitted()) {
	        $instance = new Firma;
            $row = $instance->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDIT;

	}



	/********************* view delete *********************/


	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */

	public function renderDelete($id = 0)
	{
        $instance = new Firma;
		$this->template->firma = $instance->find($id)->fetch();
		if (!$this->template->firma) {
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
		$this['contactForm']['id_firmy']->value = $ido;
        $this->template->titul = "Nový kontakt";
	}
	/*
	 * @param int, int
	 * @return void
	 * @throws BadRequestException
	 */	
	public function renderEditContact($id, $ido)
	{
		$contact = new Kontakt;
		$this['contactForm']['id_firmy']->value = $ido;
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
	public function renderDeleteContact($id, $ido)
	{
		$contact = new Kontakt;
		$this->template->contact = $contact->find($id)->fetch();
		if (!$this->template->contact) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = "Výmaz kontaktu";

	}


	/********************* component factories *********************/



	/**
	 * Item edit form component factory.
	 * @return mixed
	 */
	protected function createComponentFirmaForm()
	{
		$form = new Form;
		$form->addText('nazev', 'Název:')
			->setRequired('Uveďte název zákazníka.');

	/* Druh firmy */
		$comp = new Model;
			$dzkratka = $comp->getCompanyKind();
			$form->addSelect('id_druhy_firem', 'Druh firmy:', $dzkratka)
			        ->setPrompt('[..zvolte druh firmy..]')
			        ->addRule(Form::FILLED, 'Zvol druh firmy');

		$form->addText('zkratka', 'Zkratka:')
				->setRequired('Uveďte zkratku zákazníka.');
		$form->addHidden('id_adresy');
		$form->addText('a_ulice', 'Ulice:');
		$form->addText('a_cp', 'č.p.:');

	/* Obec */
			$obce = $comp->getCity();
			$form->addSelect('a_id_obce', 'Obec:', $obce)
			        ->setPrompt('[..zvolte obec nebo zadejte novou..]');
			$form->addText('o_nazev', 'Nová obec:');
			$form->addText('o_psc', 'Nové PSČ:');
                 //->addRule(Form::PATTERN, 'PSČ musí mít 5 číslic', '([0-9]\s*){5}');

	/* Kraj */
			$kraje = $comp->getProvince();
			$form->addSelect('a_id_kraje', 'Kraj:', $kraje)
			        ->setPrompt('[..zvolte kraj..]');
			$form->addText('k_nazev', 'Nový kraj:');
			$form->addText('k_zkratka', 'Zkratka kraje:');

	/* Stát */
			$staty = $comp->getCountry();
			$form->addSelect('a_id_staty', 'Stát:', $staty)
			        ->setPrompt('[..zvolte stát..]');
			$form->addText('s_nazev', 'Nový stát:');
			$form->addText('s_zkratka', 'Zkratka státu:');


		$form->addText('ico', 'IČO:');
		$form->addText('dic', 'DIČ:');
		$form->addText('cislo_uctu', 'Číslo účtu:');
		$form->addText('banka', 'Banka:');
        $form->addTextArea('poznamka', 'Poznámka:')
            ->addRule(Form::MAX_LENGTH, 'Poznámka je příliš dlouhá', 2000);

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'firmaFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function firmaFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
	        $instance = new Firma;
			//výběr dat z formuláře podle prefixů - pro update, insert v příslušných tabulkách
			$firma = $instance->getPrefixedFormFields($form->values);
			$adresa = $instance->getPrefixedFormFields($form->values,'a_');
			$mesto = $instance->getPrefixedFormFields($form->values,'o_');
			$kraj = $instance->getPrefixedFormFields($form->values,'k_');
			$stat = $instance->getPrefixedFormFields($form->values,'s_');
			$id_adresy = (int) $firma['id_adresy'];
			$id_obce = (int) $adresa['id_obce'];
			$id_kraje = (int) $adresa['id_kraje'];
			$id_staty = (int) $adresa['id_staty'];
			if ($id > 0) {
				// update
				//není-li vybraná obec a je zadaná jako nová - bude insert obce
				if ($id_obce == 0 && $mesto['nazev']<>'') {
					$id_obce = $instance->insertCis('obce', (array) $mesto);
					$adresa['id_obce'] = (string) $id_obce;
				}
				//je-li vybraná obec a je zadaná jako nová - bude update obce !!!
				if ($id_obce > 0 && $mesto['nazev']<>'' && $mesto['psc']<>'') {
					$instance->updateCis('obce', $id_obce, (array) $mesto);
				}

				//není-li vybraný kraj a je zadán jako nový - bude insert kraje
				if ($id_kraje == 0 && $kraj['nazev']<>'' && $kraj['zkratka']<>'') {
					$id_kraje = $instance->insertCis('kraje', (array) $kraj);
					$adresa['id_kraje'] = (string) $id_kraje;
				}
				//je-li vybraný kraj a je zadán jako nový - bude update kraje !!!
				if ($id_kraje > 0 && $kraj['nazev']<>'' && $kraj['zkratka']<>'') {
					$instance->updateCis('kraje', $id_kraje, (array) $kraj);
				}

				//není-li vybraný stát a je zadán jako nový - bude insert st8tu
				if ($id_staty == 0 && $stat['nazev']<>'' && $stat['zkratka']<>'') {
					$id_staty = $instance->insertCis('staty', (array) $stat);
					$adresa['id_staty'] = (string) $id_staty;
				}
				//je-li vybraný stát a je zadán jako nový - bude update st8tu !!!
				if ($id_staty > 0 && $stat['nazev']<>'' && $stat['zkratka']<>'') {
					$instance->updateCis('staty', $id_staty, (array) $stat);
				}

				$instance->updateCis('adresy', $id_adresy, (array) $adresa);
				$instance->update($id, (array) $firma);
				$this->flashMessage('Zákazník byl změněn.');

			} else {
				//insert

				if ($id_obce == 0 && $mesto['nazev']<>'') {
					$id_obce = $instance->insertCis('obce', (array) $mesto);
					$adresa['id_obce'] = (string) $id_obce;
				}
				//je-li vybraná obec a je zadaná jako nová - bude update obce !!!
				if ($id_obce > 0 && $mesto['nazev']<>'' && $mesto['psc']<>'') {
					$instance->updateCis('obce', $id_obce, (array) $mesto);
				}

				//není-li vybraný kraj a je zadán jako nový - bude insert kraje
				if ($id_kraje == 0 && $kraj['nazev']<>'' && $kraj['zkratka']<>'') {
					$id_kraje = $instance->insertCis('kraje', (array) $kraj);
					$adresa['id_kraje'] = (string) $id_kraje;
				}
				//je-li vybraný kraj a je zadán jako nový - bude update kraje !!!
				if ($id_kraje > 0 && $kraj['nazev']<>'' && $kraj['zkratka']<>'') {
					$instance->updateCis('kraje', $id_kraje, (array) $kraj);
				}

				//není-li vybraný stát a je zadán jako nový - bude insert st8tu
				if ($id_staty == 0 && $stat['nazev']<>'' && $stat['zkratka']<>'') {
					$id_staty = $instance->insertCis('staty', (array) $stat);
					$adresa['id_staty'] = (string) $id_staty;
				}
				//je-li vybraný stát a je zadán jako nový - bude update st8tu !!!
				if ($id_staty > 0 && $stat['nazev']<>'' && $stat['zkratka']<>'') {
					$instance->updateCis('staty', $id_staty, (array) $stat);
				}

				if ($id_adresy == 0) {
					$id_adresy = $instance->insertCis('adresy', (array) $adresa);
					$firma['id_adresy'] = (string) $id_adresy;
				} else {
					$instance->updateCis('adresy', $id_adresy, (array) $adresa);
				}
				$instance->insert((array) $firma);
				$this->flashMessage('Zákazník byl přidán.');
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
	        $instance = new Firma;
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
		$form->addHidden('id_firmy');

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
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
				$this->flashMessage('Kontaktní údaj byl změněn.');
			} else {
				$contact->insert($data);
				$this->flashMessage('Kontaktní údaj byl přidán.');
			}
		}

		$this->redirect('detail',$this->getParam('ido'));

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
