<?php

use Nette\Application\UI\Form,
	Nette\ArrayHash as NH,
	Nette\Application as NA;

class ImportPresenter extends MaterialPresenter
{
    /** Title constants */
    const TITUL_UPLOAD 	= 'Výběr CSV souboru pro import';
    const TITUL_CHECK 	= 'Kontrola před importem';
    const TITUL_CONFIRM	= 'Potvrzení importu dat do DB materiálu';
	
	private $titul;

	/** @var Nette\Database\Table\Selection */

	private $head;
	private $file;
	private $npole = array( 'zkratka'       => 'Zkratka',
							'nazev'         => 'Název',
							'id_k2'         => 'Číslo K2',
							'mena'			=> 'Měna (název)',
							'cena_cm'       => 'Cena [mena/ks]',
							'mnozstvi'      => 'Množství [ks]');
	
	public function startup()
	{
		parent::startup();
		if(!$this->isMySet(4)){
			//lze importovat, jen když je aktivován v MySetting nabídka/produkt
			$this->flashMessage('S modulem IMPORT nelze pracovat. Nebyl aktivován žádný produkt.','exclamation');
			$this->redirect('Nakup:default');
		}

	}


	/********************* view default *********************/


	/**
	 *		Upload CSV file
	 *		@return void
	 */

	public function renderDefault($id=0, $wh=0)
	{
        $this->template->titul = self::TITUL_UPLOAD;
	}

	/**
	 *  CHcek uploaded data assign db fields
	 *	@return void
	 */
	public function renderCheck($file, $cnt = 10)
	{
		$import		= new Import;
		$csv		= $file;
		$head		= $import->headerOfCsv($csv);
		$this->head = $head;
		$this->file = $file;
		$data		= $import->dataOfCsv($csv, $cnt);
		$radku		= $import->rowsOfCsv($csv);
		$sloupcu	= $import->colsOfCsv($csv);
		$meny		= $import->getMenyAsList();
		$form = $this['checkForm'];
		if (!$form->isSubmitted()) {
			if ($head){
				$form = $this['checkForm'];
				$form['skipfirst']->value = true;
				if ($cnt==10){
					$this->flashMessage('Soubor byl úspěšně načten.');
				} else {
					$this->flashMessage('Načtení dat nebylo úspěšné, soubor nemá žádný záznam.','warning');
				}
				$this->template->titul = self::TITUL_EDIT;
			}
		}
		$this->template->data	= $data;
		$this->template->head	= $head;
		$this->template->form	= $form;
		$this->template->soubor = $file;
		$this->template->meny	= $meny;
		$this->template->pocet	= $radku<$cnt ? $radku : $cnt;
		$this->template->titul	= "Načtený soubor: [" . $file . "]";

	}

	/**
	 *  Confirm form for import data into db
	 *	@return void
	 */
	public function actionConfirm($skip1 = true)
	{
		//Confirm import CSV file
		$import = new Import;
		$meny	= $import->getMenyAsList();
		$poles	= $import->fromCacheSome('prirazeni');
		$head	= (array) $import->fromCacheHead();
		$i		= 0;
		$info	= array();
		$pair	= array();
		$inaz	= 0;
		$izkr	= 0;
		foreach($poles as $p){
			$i++;
			if($p){
				$info[$this->npole[$p]] =  $head[$i];
				$pair[$p] =  $i;
				if($p == 'nazev'){$inaz = $i;}
				if($p == 'zkratka'){$izkr = $i;}
			}
		}	
		//test, zda nemusí být pole zkratka či název nahrazeno tím druhým
		if ($inaz>0){
			if($izkr==0){
				$info['Zkratka'] =  $head[$inaz];
				$pair['zkratka'] =  $inaz;
			}
		} else {
			if($izkr>0){
				$info['Název'] =  $head[$izkr];
				$pair['nazev'] =  $izkr;
			}
		}
		$this['confirmForm']['skip1']->value = $skip1;
		$import->toCacheSome('pairs', $pair);
		$this->template->aprodukt = $this->getNameFromMySet(4);
		$this->template->info = $info;
		$this->template->meny = $meny;
		$this->template->file = $import->fromCacheFile();
		$this->template->isok = true;
        $this->template->titul = self::TITUL_CONFIRM;
	}

	
	/********************* views add & edit *********************/


	/**
	 * upload csv form component factory.
	 * @return mixed
	 */
	protected function createComponentUploadForm()
	{
		$form = new Form;
		$form->addUpload('file', 'CSV soubor s rozpiskou:')
				//->addRule(Form::MIME_TYPE, 'Zvolený soubor musí být ve formátu CSV.',array('text/csv','text/plain'))
				->addRule(Form::MAX_FILE_SIZE, 'Maximální velikost souboru je 2 MB.', 2 * 1024 * 1024 /* v bytech */);
		$form->addSubmit('save', 'Načíst')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'uploadFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	/**
	 * Upload file into uplDir (after submit)
	 * @param Form $form
	 * @return type 
	 */
	public function uploadFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {

				 $values = $form->getValues();

                 $file = $form['file']->getValue();
				 $typ = $file->getContentType();
				 if(strpos(strtoupper($file->name),'CSV')==0){
	                $form['save']->addError("Vybraný soubor [$file->name] není typu CSV, ale $typ !");
		            return ;
				 }
                 if ($file->isOK())
                 {
                        $file->move(UPL_DIR. '/' . $file->name);
						$this->redirect('check',$file->name);
                 }
                 else
                 {
                        $file->addError("Upload souboru nebyl úspěšný");
		                $this->flashMessage('Soubor NEBYL uložen.','warning');
                 }
        } else {
				$this->redirect('Nakup:default');
		}
	}
	
	/**
	 * factory for Check Form (view sample data from CSV)
	 * @return Form 
	 */
	protected function createComponentCheckForm()
	{
		$form = new Form;
		$imp = new Import;
		$csv = $imp->fromCacheFile();
		$head = $imp->fromCacheHead();
		// pomoc z fóra Container
        $mpole = $form->addContainer('mpole');
        foreach(array_values($head) as $k => $v){
                $mpole->addSelect($k, $v . ' »» ', $this->npole)
                        ->setPrompt('[zvolte pole]')
						->setDefaultValue($this->defValSloupce($v));
        }		
		$form->addCheckbox('skipfirst','Vynechat 1. řádek názvů');
		$form->addSubmit('save', 'Importovat')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'checkFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	private function defValSloupce($h) {
		if($h==''){return NULL;}
		foreach($this->npole as $key => $val) {
			$pos = strpos($key, $h);
			if($pos !== FALSE){
				return $key;
			}
		}
		return NULL;
	}
	/**
	 * after submitted CheckForm, filled empty columns when exists
	 * @param Form $form
	 * @return type 
	 */
	public function checkFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$import = new Import;
			$values = $form->getValues();
			$fields = (array) $form['mpole']->values;
			$import->toCacheSome('prirazeni', $fields);
			$ok = true;
			$nok = true;
			$zok = true;
			$chybi = '';
			$nrequired = array('zkratka','nazev','mnozstvi');
			foreach($nrequired as $req){
				if (!in_array($req, $fields)) {
					switch ($req) {
						case 'nazev':
							$nok = false;
							$chybi .= 'Název, ';
							break;
						case 'zkratka':
							$zok = false;
							$chybi .= 'Zkratka, ';
							break;
						case 'mnozstvi':
							$chybi .= 'Množství, ';
							break;
					}
					$ok = false;
				}
			}
			if (!$ok){
				if (!$ok && ($nok || $zok)){
					//název nebo zkratka bude nahrazen polem zkratka resp. název
				} else {
					$chybi = substr($chybi, 0, strlen($chybi)-2);
					$hlas = "Nejsou přiřazena všechna povinná pole: $chybi.";
			        $form['save']->addError($hlas);
				    return ;
				}
			}
			$this->flashMessage('Data mohou být naimportována.');
            $skip1 = $values->skipfirst;
			$this->redirect('confirm',$skip1);
		} else {
			$this->flashMessage('Import byl stornován.','exclamation');
			$this->redirect('Material:default');
		}
	}

	/**
	 * factory for Confirm Form
	 * @return Form 
	 */
	protected function createComponentConfirmForm()
	{
		$form = new Form;
		$form->addHidden('skip1');
		$form->addSubmit('import', 'NAIMPORTOVAT')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno');
		$form->onSuccess[] = callback($this, 'confirmFormSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}


	/**
	 * after ConformForm submitted, insert data into nmaterial table and change status to WAIT4PRICES
	 * @param Form $form 
	 */
	public function confirmFormSubmitted(Form $form)
	{
		if ($form['import']->isSubmittedBy()) {
	        $import = new Import;
            $file = $import->fromCacheFile();
			$pary = $import->fromCacheSome('pairs');
			$id_produkt = $this->getIdFromMySet(4);
			$id_nabidka = $this->getIdFromMySet(3);
            $skip1 = $form['skip1']->getValue();
			$cnt_rows = $import->goImport($file, $pary, $id_produkt, $skip1);
			if($id_nabidka>0){
				$nab = new Nabidka();
				$nab->insertStatus($id_nabidka, self::stWAIT4PRICES, $this->user->id);
				$prod = new Produkt();
				$prod->insertProductStatus($id_produkt, self::stWAIT4PRICES, $this->user->id);
				$this->redirect('Material:default');
			}
			if($cnt_rows>0){
				$this->flashMessage("Import byl dokončen úspěšně, bylo vloženo $cnt_rows záznamů.");
			} else {
				$this->flashMessage("Import BOMu nebyl dokončen.", "exclamation");
			}
			unlink($file);
		} else {
			$this->flashMessage('Import byl stornován.','exclamation');
		}
		$this->redirect('Material:default');
	}

	
}
