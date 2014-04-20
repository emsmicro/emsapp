<?php

use Nette\Application\UI\Form,
	Nette\Application as NA,
	Vodacek\Forms\Controls\DateInput;

/*
 * Nabidka presenter
 */
class NabidkaPresenter extends ObchodPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Nabídky';
    const TITUL_ADD 	= 'Nová nabídka';
    const TITUL_EDIT 	= 'Změna nabídky';
    const TITUL_DELETE 	= 'Smazání nabídky';
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
        $instance = new Nabidka;
		$this->items = $instance->show();
		$this->presnt_name = "Nabidka";

	}


	/********************* view default *********************/

	public function renderDefault()
	{
			
		$nab = new Nabidka;

		// User filter
		$ufilter = $this['uFilter'];
		$nab->filter = $ufilter->getFilter();
		$this->template->is_filter = TRUE;
		
		$rows = $nab->show();
		$cnt = count($rows);
		// stránkování
		$paginator = $this['vp']->getPaginator(); 
		$paginator->itemsPerPage = 30;
		$paginator->itemCount = $cnt;
		$nab->limit = $paginator->getLength();
		$nab->offset = $paginator->getOffset();
		$rowp = $nab->show();			
		
		$this->template->items = $rowp;
        $this->template->titul = self::TITUL_DEFAULT;
		
	}
	/*
	 * @param int
	 * @return void
	 */
	public function renderOffer($id)
	{
        $instance = new Nabidka;
		$this->template->items = $instance->showOffer($id);
		$section = $this->context->session->getSection('mySetting');
        $this->template->titul = "Nabídky - ".$section->firma;
	}
	/********************* view detail *********************/

	/**
	 * Zobrazi detail nabidky
	 * @param type $id .. id_nabidky
	 * @param type $act 1 .. aktivni, 0 .. neaktivno ceny
	 */
	public function renderDetail($id = 0, $act=1)
	{
		if($id==0){
			$this->redirect('default');
		}
		
        $nabidka = new Nabidka;
		$item = $nabidka->find($id)->fetch();
		$hist = $nabidka->getOfferHistory($id);
		$volume = $nabidka->sumVolume($id)->fetch();
		$isvol = $volume !== FALSE;

		// Kurzy
//		$rater = $this['rater'];
//		$this->template->is_rates = TRUE;

		$this->setIntoMySet(3, $id, 1);
		
		$produkt = new Produkt;
		$prods = $produkt->showProduct(0,$id);
		$prices = $produkt->getOfferPrices($id, $act);
		$iscen = count($prices)>0;
		
		$kalk = new Kalkul;
		$aval = $kalk->calcAddValNab($id);
		$sval = $kalk->sumAddValActiveNab($aval);
				
		$oper = new Operace;
		$acap = $oper->sumKapacitaNab($id);
		$this->template->capac = $acap;
		$this->template->iscap = count($acap);
		$this->template->mypar = $this->mpars;
		$this->template->isAct = $act;
		$this->template->aval = $aval;
		$this->template->sval = $sval;
		
		$this->template->cena_bar = $sval['cenagraf'];
		$this->template->nakl_bar = $sval['naklgraf'];
		$this->template->nakl_pie = $sval['naklpie'];
		$this->template->catg_bar = $sval['naklcatg'];
		$this->template->data_bar = $sval['nakldata'];
		
		$this->template->item = $item;
		$this->template->history = $hist;
		$this->template->products = $prods;
		$this->template->prices = $prices;
		$this->template->isvol = $isvol;
		$this->template->iscen = $iscen;
		$this->template->vol = $volume;
	   	$this->template->titul = $item->popis;

		$this->template->isPDF = false;
		
//		dd($aval, 'AVAL');
//		dd($prices, 'PRICES');
//		dd($this->mypar,"MY params");
//		dd($acap, 'ACAP');
//		dd($sval, 'SVAL');
		
	}

	/**
	 * Refresh all priceso of offer
	 * @param type $id = id_nabidky
	 */
	function actionRefreshOfferPrices($id) {
		$kalk = new Kalkul;
		$prices = $kalk->findOfferPrices($id);
		$res = array();
		$i=0;
		$ok=0;
		foreach ($prices as $price) {
			$i++;
			$res[$price->id] = $kalk->refreshProductPrices($price->id, 'N');
			if($res[$price->id]['ok']){$ok++;}
		}
		if($ok == $i){
			$this->flashMessage("Bylo úspěšně zaktualizováno $ok cen.");
		} elseif ($ok>0) {
			$ne = $i-$ok;
			$this->flashMessage("Bylo úspěšně zaktualizováno $ok cen, $ne cen se nepodařilo zaktualizovat","exclamation");
		} else {
			$this->flashMessage("Ani jednu z $i cen se nepodařilo zaktualizovat","warning");
		}
		$this->redirect('detail#ceny', $id);
	}
	
	/**
	 * Send offer by mail
	 * @param type $id
	 */
	function actionToMail($id = 0) {

        $template = $this->createTemplate()->setFile(APP_DIR."/modules/Obchod/templates/Nabidka/toPdf.latte");
        $nabidka = new Nabidka;
		$item = $nabidka->find($id)->fetch();
		$volume = $nabidka->sumVolume($id)->fetchAll();
		
		$produkt = new Produkt;
		$prods = $produkt->showProduct(0, $item->id);
		$prices = $produkt->getOfferPrices($id);
		$template->item = $item;
		$template->products = $prods;
		$isvol = count($volume)>0;
		$iscen = count($prices)>0;
		$template->isvol = $isvol;
		$template->iscen = $iscen;
		$template->vol = $volume;
		$template->prices = $prices;
		$template->isPDF = TRUE;
	   	$template->titul = $item->popis;
		$template->company = $this->company;
		$user = $this->getUser();
		$template->seler = $user->getIdentity()->jmeno . ' ' . $user->getIdentity()->prijmeni;
		$firma = new Firma;
		$afirma = $firma->find($item->id_firmy)->fetch();
		$template->osoba = '';
		$ido = $this->getIdFromMySet(2);
		if($ido>0){
			$template->osoba = $this->getNameFromMySet(2);
		}
		$template->firma = $afirma;
		$template->today = date('d.m.Y');
        $pdf = new PdfResponse\PdfResponse($template);
        // Název dokumentu
        $pdf->documentTitle = "Nabídka: $template->titul";
        // Dokument vytvořil:
        $pdf->documentAuthor = "Mikroelektronika spol. s r. o.";

		$pdf->outputDestination = 'S';

		$mail = new Nette\Mail\Message();
		$mail->setFrom('emsmicro@gmail.com')
			 ->addTo('v.mracko@mikroelektronika.cz')
			 ->setSubject('Test PDF nabídka')
//			 ->addAttachment('nab.pdf', $this->sendResponse($pdf), 'application/pdf')
			 ->setBody("Dobrý den,\nposíláme vám naši nabídku.");
		
		$mail->send();		
		

//        $this->sendResponse($pdf);
		$this->redirect('detail', $id);
    }
	

	
	 function actionToPdf($id = 0, $lang = 'en') {
		if($lang=='en'){
			$templ = "toPdf";
			setlocale(LC_ALL, "en_US.UTF-8");
		} else {
			$templ = "doPdf";
			setlocale(LC_ALL, "czech");
		}
        $template = $this->createTemplate()->setFile(APP_DIR."/modules/Obchod/templates/Nabidka/$templ.latte");
        $nabidka = new Nabidka;
		$item = $nabidka->find($id)->fetch();
		$volume = $nabidka->sumVolume($id)->fetchAll();
		
		$produkt = new Produkt;
		$prods = $produkt->showProduct(0,$item->id);
		$prices = $produkt->getOfferPrices($id);
		$template->item = $item;
		$template->products = $prods;
		$isvol = count($volume)>0;
		$iscen = count($prices)>0;
		$template->isvol = $isvol;
		$template->iscen = $iscen;
		$template->vol = $volume;
		$template->prices = $prices;
		$template->isPDF = true;
	   	$template->titul = $item->popis;
		$template->company = $this->company;
		$user = $this->getUser();
		$template->seler = $user->getIdentity()->jmeno . ' ' . $user->getIdentity()->prijmeni;
		$firma = new Firma;
		$afirma = $firma->find($item->id_firmy)->fetch();
		$template->osoba = '';
		$ido = $this->getIdFromMySet(2);
		if($ido>0){
			$template->osoba = $this->getNameFromMySet(2);
		}
		$template->firma = $afirma;
		$template->today = date('d.m.Y');
        $pdf = new PdfResponse\PDFResponse($template);



        // Všechny tyto konfigurace jsou nepovinné:

//        // Orientace stránky
//        $pdf->pageOrientaion = PDFResponse::ORIENTATION_LANDSCAPE;
//        // Formát stránky
//        $pdf->pageFormat = "A0";
//        // Okraje stránky
//        $pdf->pageMargins = "100,0,100,0,20,60";
//
//        // Způsob zobrazení PDF
//        $pdf->displayLayout = "continuous";
//        // Velikost zobrazení
//        $pdf->displayZoom = "fullwidth";
//
//        // Název dokumentu
        $pdf->documentTitle = "Nabídka: $template->titul";
//        // Dokument vytvořil:
        $pdf->documentAuthor = "Mikroelektronika spol. s r. o.";
//
//        // Callback - těsně před odesláním výstupu do prohlížeče
//        //$pdfRes->onBeforeComplete[] = "test";
//
//        $pdf->mPDF->IncludeJS("app.alert('This is alert box created by JavaScript in this PDF file!',3);");
//        $pdf->mPDF->IncludeJS("app.alert('Now opening print dialog',1);");

//        $pdf->mPDF->OpenPrintDialog();

        // Zde končí nepovinná konfigurace

        // Ukončíme presenter -> předáme řízení PDFresponse
        $this->sendResponse($pdf);
    }
	
	/********************* views add & edit *********************/


	/*
	 * @return void
	 */
	public function renderAdd()
	{
		$this['itemForm']['id_firmy']->value = $this->getIdFromMySet(1);
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
	        $instance = new Nabidka;
            $row = $instance->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		} else {
			if ($form->id_firmy==0){$form->id_firmy->value = $this->getIdFromMySet(1);}
		}
		$this->template->titul = self::TITUL_EDIT;
		$this->template->is_addon = TRUE;

	}

	/*
	 * @return void
	 */
	public function renderChangeStatus($id)
	{
		$nab = new Nabidka;
		$item = $nab->find($id)->fetch();
		if (!$item) {
			throw new NA\BadRequestException('Záznam nenalezen.');
		}
		$this->template->item = $item;
		$this->template->titul = "Změna stavu nabídky";
	}

	
	public function actionEraseStatus($id, $istav, $iuser)
	{
		$prod = new Nabidka;
		$prod->deleteStatus($id, $istav, $iuser);
		$this->flashMessage('Stav nabídky byl zrušen.');
		$this->redirect('detail', $id);

	}	

	/**
	 * Odemknutí nabídky - potvrzené
	 * @param type $id
	 * @param type $status 
	 */
	public function actionUnlock($id, $status)
	{
		$nab = new Nabidka;
		$nab->insertStatus($id, $status, $this->user->id);
		$this->flashMessage('Nabídka byla odpotvrzena (odemčena).');
		$this->goBack();

	}

	/**
	 * Uzamknutí nabídky - potvrdit
	 * @param type $id
	 * @param type $status 
	 */
	public function actionLock($id, $status)
	{
		$nab = new Nabidka;
		$ren = $nab->insertStatus($id, $status, $this->user->id);
		$prd = new Produkt;
		$rep = $prd->setStatusProdsByOffers($id, $status, $this->user->id);
		if($ren && $rep){
			$message = 'Nabídka vč. produktů byla potvrzena (uzamčena).';
		} elseif ($ren) {
			$message = 'Nabídka byla potvrzena (uzamčena).';
		} elseif ($rep) {
			$message = 'Produkt(y) nabídky byl(y) potvrzen(y) (uzamčen/y).';
		} else {
			$message = 'Nabídku ani produkty se nepodařilo uzamknout.';
		}
		$this->flashMessage($message);
		$this->goBack();

	}
	
	public function actionCopyOffer($id) {
		$instance = new Nabidka;
		$result = $instance->copyNabidka($id, $this->user->id);
		if(!$result or $result==0){
			$this->flashMessage('Zkopírování nabídky bylo neúspěšné. Přiřaďte produkt nabídce.','warning');
			$this->redirect('detail', $id);
		} else {
			$this->flashMessage("Nabídka byla zkopírována pod novým číslem $result. Aktualizujte náklady a ceny všech nových produktů.");
			$this->redirect('detail', $result);
		}		
	}
	
	
	/**
	 * Copy offer
	 * @param int id = id_produkt
	 * @return void
	 * @throws BadRequestException
	 */	
	public function renderCopyNabidka($id)
	{
		$form = $this['copyNabidkaForm'];
		if (!$form->isSubmitted()) {
			$item = new Nabidka;
			$this->template->item = $item->find($id)->fetch();
			if (!$this->template->item) {
				throw new Nette\Application\BadRequestException('Záznam nenalezen!');
			}
		} else {
			
		}
		$this->template->titul = "Kopie nabídky";

	}	
	
	
	/********************* view delete *********************/
	/*
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */
	public function renderDelete($id = 0)
	{
        $instance = new Nabidka;
		$this->template->item = $instance->find($id)->fetch();
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
		
		$comp= new Model;
		$firma= $comp->getCompany();
		$form->addSelect('id_firmy', 'Firma:', $firma)
			        ->setPrompt('[..zvolte firmu..]')
			        ->addRule(Form::FILLED, 'Zvol firmu');
		$form->addTextArea('popis', 'Popis:')
				->setRequired('Uveďte popis.');
		
			
		$form->addDate('prij_datum', 'Datum přijetí:', DateInput::TYPE_DATE)
				->setRequired('Uveďte datum přijetí.');

		$form->addDate('pozad_datum', 'Požadované datum:', DateInput::TYPE_DATE);
		
		$sets=$comp->getSetR();
		$form->addSelect('id_set_sazeb', 'Set reřijních sazeb:', $sets)
			        ->setPrompt('[..zvolte set režijních sazeb..]');

		$seto = $comp->getSetO();
		$form->addSelect('id_set_sazeb_o', 'Set sazeb operací:', $seto)
			        ->setPrompt('[..zvolte set sazeb operací..]');
		
		$form->addTextArea('poznamka', 'Poznamka:');
		$form->addText('folder', 'Umístění dat:', 60, 2000);	
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
			$item = new Nabidka;
			$nab = (array) $form->values;
			$nab['prij_datum'] = $item->getDateStringForInsertDB($nab['prij_datum']);
			$nab['pozad_datum'] = $item->getDateStringForInsertDB($nab['pozad_datum']);
			if ($id > 0) {
				$item->update($id, (array) $nab);
				$this->flashMessage('Nabídka byla změněna.');
			} else {
				$idn = $item->insert((array) $nab);
				if($idn>0){
					$item->insertStatus($idn, self::stBASED, $this->user->id);
					$this->redirect('Nabidka:detail',$idn);
				}
				$this->flashMessage('Nabídka byla přidána.');
			}
		}
		$this->redirect('default');
	}


	/**
	 * Item delete form component factory.
	 * @return mixed
	 */
	protected function createComponentChangeStatusForm()
	{
		$form = new Form;
		$role = $this->user->getRoles();
		$model= new Model;
		$stavy= $model->getStatus($role[0]);
		$form->addSelect('id_stav', 'Stav nabídky:', $stavy)
			        ->setPrompt('[..vyberte nový stav nabídky..]')
			        ->addRule(Form::FILLED, 'Vyberte stav.');
		$form->addSubmit('change', 'Nastavit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'chngstatFormSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function chngstatFormSubmitted(Form $form)
	{
		if ($form['change']->isSubmittedBy()) {
			$idn = $this->getParam('id');
			$stav = (array) $form->values;
			if($idn>0){
		        $nab = new Nabidka;
				$nab->insertStatus($idn, $stav['id_stav'], $this->user->id);
				$this->redirect('Nabidka:detail',$idn);
			}
			$this->flashMessage('Status změněn.');
		}

		$this->redirect('default');
	}

	/**
	 * Confirm copy offer
	 * @return mixed
	 */
	protected function createComponentCopyNabidkaForm()
	{
		$form = new Form;
		$form->addSubmit('copynab', 'Ano')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Ne')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'copyNabFormSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}


	/**
	 * Submit Copy Offer Form Submited
	 * @param Form $form 
	 */
	public function copyNabFormSubmitted(Form $form)
	{
		$id = $this->getParam('id');
		if ($form['copynab']->isSubmittedBy()) {
	        $instance = new Nabidka;
			$result = $instance->copyNabidka($id, $this->user->id);
			if(!$result || $result==0){
				$this->flashMessage('Zkopírování nabídky bylo neúspěšné. Přiřaďte produkt nabídce.','warning');
			} else {
				$this->flashMessage("Nabídka byla zkopírována pod novým číslem $result. Aktualizujte náklady a ceny všech nových produktů.");
				$this->redirect('detail', $result);
			}
		}

		$this->redirect('detail', $this->getParam('id'));
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
	        $instance = new Nabidka;
			$instance->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}
	
	
		
	
	
	

}
