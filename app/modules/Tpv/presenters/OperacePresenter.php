<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;
/**
 * Operace presenter
 */

class OperacePresenter extends TpvPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'TPV - operace';
    const TITUL_ADD 	= 'Nová operace';
    const TITUL_EDIT 	= 'Změna operace';
    const TITUL_DELETE 	= 'Smazání operace';
    const TITUL_GROUP 	= 'Hromadné zadání operací';
     /**
	 * @var string
	 * @titul
	 */ 
	private $titul;
	private $idproduct;
	

	public function startup()
	{
		parent::startup();
		if(!$this->isMySet(4)){
			//lze pracovat, jen když je aktivován v MySetting nabídka/produkt
			$this->flashMessage('S modulem OPERACE nelze pracovat. Nebyl aktivován žádný produkt v rámci nabídky.','exclamation');
			$this->redirect('Produkt:default');
		}
		$this->idproduct = $this->getIdFromMySet(4);
        $instance = new Operace;
		$this->template->subtitle = 'Produkt: ' . $this->getIdFromMySet(4) .' - '. $this->getNameFromMySet(4);
		$this->template->company = 'Zákazník: ' . $this->getNameFromMySet(1);

	}


	/********************* view default *********************/

	/**
	 * $id = id_produktu
	 * @return void
	 */

	public function renderDefault($id=0)
	{
        $instance = new Operace;
		if($id>0){
			$this->setIntoMySet(4, $id);
			$this->idproduct = $this->getIdFromMySet(4);
		}
		$idp = $this->idproduct;
		$idn = $this->getIdFromMySet(3);
		$operace = $instance->showNaklady($idp,$idn)->fetchAll();
		//$operace = $instance->show($idp)->fetchAll();
		$isoper = count($operace);
		$this->template->isoper = $isoper;
		$this->template->items = $operace;
        $this->template->titul = self::TITUL_DEFAULT;
		$this->template->idp=$idp;
		$this->template->stavy4 = $instance->getProductHistory($idp,4);
		$this->template->stavy5 = $instance->getProductHistory($idp,5);
		$this->template->unlocked = $instance->isProductLocked($idp)<1;
	}
	/********************* view detail *********************/
	/**
	 * @param int
	 * @return void
	 */	
	public function renderDetail($id = 0)
	{
        $instance = new Operace;
		$item = $instance->find($id)->fetch();

		$this->template->item = $item;
	   	$this->template->titul = $item->popis;
	}

	/********************* views add & edit *********************/

	/**
	 * 
	 * @param type $src = id_tpostup
	 */
	public function renderAdd($src = 0)
	{
		$this['itemForm']['a_id_produkty']->value = $this->getIdFromMySet(4);
		$this['itemForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADD;

	}

	/**
	 * 
	 * @param type $id
	 * @param type $src = id_tpostup
	 * @throws NA\BadRequestException
	 */
	public function renderEdit($id = 0, $src = 0)
	{
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
	        $instance = new Operace;
            $row = $instance->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		} else {
			if ($form->id_produkty==0){$form->id_produkty->value = $this->getIdFromMySet(4);}
		}
		$this->template->titul = self::TITUL_EDIT;
		if ($src > 0){
			if($row['npostup']<>""){
				$this->template->subtitle = 'Postup: ' . $row['npostup'];
			}
			if($row['nsablona']<>""){
				$this->template->company = 'Šablona: ' . $row['nsablona'];
			}
		}
	}

	/**
	 * Hromadné zadání operací
	 * @param type $src = id_tpostup
	 */
	public function renderAddGroup($src = 0)
	{
		$idp = $this->getIdFromMySet(4);
		$oper = new Operace;
		$data = $oper->getTypesOper($idp);
        $this->template->titul = self::TITUL_GROUP;
		$form = $this['addGroupForm'];
		// reset default render
		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = NULL;
		$renderer->wrappers['pair']['container'] = NULL;
		$renderer->wrappers['label']['container'] = NULL;
		$renderer->wrappers['control']['container'] = NULL;
		$this->template->items = $data;
		$this->template->form = $form;
	}

	/**
	 * Kalkulace spotreby casu
	 * @param type $id = id_operace
	 * @param type $src = id_tpostup
	 */
	public function renderTcalc($id, $src = 0)
	{
		$oper = new Operace;
		$id_produkt = $this->getIdFromMySet(4);
		$data = $oper->showCalcOper($id, $id_produkt)->fetchAll();
		$op = $oper->find($id)->fetch();
        $this->template->titul = "Kalkulace spotřeby času";
		$this->template->subtitul = $this->getNameFromMySet(4);
		$this->template->subtitul2 = $op->popis;
		$this->template->ta = $op->ta_cas;
		$this->template->tp = $op->tp_cas;
		$this->template->ta_min = $op->ta_min;
		$this->template->ta_rez = $op->ta_rezerva * 100;
		$form = $this['tcalcForm'];
		$form['id_produkt']->value = $id_produkt;
		$form['ta_min']->value = $op['ta_min'];
		$form['ta_rezerva']->value = $op['ta_rezerva'];
		// reset default render
		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = NULL;
		$renderer->wrappers['pair']['container'] = NULL;
		$renderer->wrappers['label']['container'] = NULL;
		$renderer->wrappers['control']['container'] = NULL;
		$this->template->items = $data;
		$this->template->form = $form;
		$this->template->unlocked = $oper->isProductLocked($id_produkt)<1;
	}

	/********************* view delete *********************/

	/**
	 * 
	 * @param type $id
	 * @param type $src = id_tpostup
	 * @throws Nette\Application\BadRequestException
	 */
	public function renderDelete($id = 0, $src = 0)
	{
        $instance = new Operace;
		$this->template->id = $id;
		if($id>0){
			$items = $instance->find($id)->fetch();
			$this->template->item = $items;
			$this->template->nazev = $items->popis;
			if (!$this->template->item) {
				throw new Nette\Application\BadRequestException('Záznam nenalezen!');
			}
		} else {
			$id_produkt = $this->getIdFromMySet(4);
			$this['deleteForm']['id_produkt']->value = $id_produkt;
			if($id_produkt>0){
				$this->template->item = true;
				if($id<0){
					$this->template->nazev = "všechny operace produktu ".$this->getNameFromMySet(4).", u kterých nebyla použita šablona";
				} else {
					$this->template->nazev = "všechny operace produktu ".$this->getNameFromMySet(4);
				}
			} else {
				throw new Nette\Application\BadRequestException('Není vybrán produkt, záznamy nelze odstranit!');
			}
		}
		$this->template->titul = self::TITUL_DELETE;

	}	
	
	
	public function actionDeleteOne($id)
	{
		if($id>0){
			$oper = new Operace;
			$oper->delete($id);
			$this->flashMessage("Operace $id byla odstraněna.");
		}
		$this->goBack();		
	}
	
	/**
	 * Zmena statusu produktu
	 * @param type $id
	 * @param type $status
	 */
	public function actionChangeStatus($id, $status)
	{
		$prod = new Produkt;
		$prod->insertProductStatus($id, $status, $this->user->id);
		$this->flashMessage('Stav produktu byl změněn.');
		$this->goBack();

	}

	
	/**
	 * export TPV
	 */
	public function renderExport()
	{

		$id = $this->idproduct;
		// nastavím hlavičky a název souboru, dále pošlu soubor do prohlížeče pro stažení
		header("Content-Type: application/csv, windows-1250");
		header("Content-Disposition: attachment;filename=tpv_$id.csv");
		header("Cache-Control: max-age=0");
		
		//$this->template->setFile(__DIR__ . '/../templates/Operace/default.latte');
        $instance = new Operace;

		$idn = $this->getIdFromMySet(3);
		$rows = $instance->showNaklady($id,$idn)->fetchAll();
		dd($rows);
		$this->template->items = $rows;
		$this->template->idp=$id;
       // napojím data
		$this->template->registerHelper('iconv', function($value, $from = 'utf-8', $to = 'windows-1250') {
					return iconv($from, $to, $value);
			});		

	}	
	
	
	
	
	/********************* component factories *********************/

	/**
	 * Item add and edit form component factory.
	 * @return mixed
	 */
	protected function createComponentItemForm()
	{
		$form = new Form;
		$comp = new Model;
		$typ = $comp->getOperationType();
		$sab = $comp->getProduktSablony($this->getIdFromMySet(4));
		$id = (int) $this->getParam('id');
		$tamin = 0;
		if ($id > 0){
			$instance = new Operace;
            $row = $instance->find($id)->fetch();
			$tamin = $row['ta_min'];
		}
		$form->addHidden('ta_min');
		$form->addHidden('ta_rezerva');

		$form->addSelect('id_sablony', 'Šablona:', $sab)
			        ->setPrompt('Zvolte šablonu technol. postupu');
			        //->addRule(Form::FILLED, 'Zvolte šablonu TP');

		$form->addSelect('id_typy_operaci', 'Typ:', $typ)
			        ->setPrompt('Zvolte typ operace')
			        ->addRule(Form::FILLED, 'Zvolte typ operace');

		$form->addText('poradi', 'Pořadí:', 4)
				->setRequired('Uveďte pořadí operace ve skupině.')
				->controlPrototype
					->autocomplete('off')
				->addCondition($form::FILLED);
		
		$form->addTextArea('popis', 'Popis:');
		//$form->addHidden('id_sablony');
		$form->addHidden('id_tpostup');
		$form->addHidden('a_id_produkty');

		$tastr = "";
		if($tamin > 0){
			$tastr = ", minimálně ".round($tamin,2)." min/ks";
		}
		
		$form->addText('ta_cas', 'Čas Ta:', 6)
				->setAttribute('class', 'cislo')
				->setOption('description', '[min/ks] - výrobní čas'.$tastr)
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		/*
				->addRule($form::RANGE, 'Hodnota musít alespoň %d minut.', array(5, 9999))
						->addRule(function (Nette\Forms\IControl $control) {
								  return (bool) ($control->getValue() > $tamin);
							 }, 'Minimum je  minut');				
		 * 
		 */
		
		$form->addText('tp_cas', 'Čas Tp:', 6)
				->setAttribute('class', 'cislo')
				->setOption('description', '[min] - přípravný čas')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$form->addText('naklad', 'JN:', 6)
				->setAttribute('class', 'cislo')
				->setOption('description', '[Kč] - jednorázový náklad')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'itemFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function itemFormSubmitted(Form $form)
	{
		$src = $this->getParam('src');
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$item = new Operace;
			$data = (array) $form->values;
			$data = $item->getPrefixedFormFields($form->values);
			$prod = $item->getPrefixedFormFields($form->values,'a_');
			$data['naklad'] = floatval($data['naklad']);
			$data['ta_cas'] = floatval($data['ta_cas']);
			$data['tp_cas'] = floatval($data['tp_cas']);
			unset($data['ta_min']);
			unset($data['ta_rezerva']);
			$idp = $prod['id_produkty'];
			if($data['id_sablony']==''){$data['id_sablony']=NULL;}
			if($data['id_tpostup']==''){$data['id_tpostup']=NULL;}
			if($src>0){
				$data['id_tpostup'] = $src;
			}
			if ($id > 0) {
				$item->update($id, $data);
				$this->flashMessage('Položka byla změněna.');
			} else {
				$item->insert($data, (int) $idp);
				$this->flashMessage('Položka byla přidána.');
			}
			if(is_null($data['id_tpostup'])){
				$this->redirect('Postup:default');
			} else {
				$this->redirect('Postup:detail', $data['id_tpostup']);
			}
		} else {
			if($src > 0){
				$this->redirect('Postup:default');
			} else {
				$this->redirect('default');
			}
		}
	}


	/**
	 * Item delete form component factory.
	 * @return mixed
	 */
	protected function createComponentDeleteForm()
	{
		$form = new Form;
		$form->addHidden('id_produkt');
		$form->addSubmit('delete', 'Smazat')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno');
		$form->onSuccess[] = callback($this, 'deleteFormSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}


	public function deleteFormSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$data = $form->values;
			$id = $this->getParam('id');
	        $instance = new Operace;
			$idp = 0;
			if($id <= 0){
				$idp = (int) $data['id_produkt'];
				if($idp > 0){
					$instance->delete($id,$idp);
					if ($id == 0){$this->flashMessage('Všechny operace produktu byly smazány.');}
					if ($id < 0){$this->flashMessage('Všechny operace bez šablony aktuálního produktu byly smazány.');}
				} else {
					$this->flashMessage('Záznamy nelze smazat, není zvolen produkt.', 'exclamation');
				}
			} else {
				$instance->delete($id);
				$this->flashMessage('Smazáno.');
			}
		}
		if($this->getParam('src') > 0){
			$this->redirect('Postup:default');
		} else {
			$this->redirect('default');
		}
	}


	protected function createComponentAddGroupForm()
	{
		$form = new Nette\Application\UI\Form;
		$oper = new Operace;
		$idp = $this->getIdFromMySet(4);
		$data = $oper->getTypesOper($idp);
		$container = $form->addContainer('mpole');
		$i=0;
		foreach($data as $row => $v){
			$i++;
			$ta = $v['ta_cas'];
			if ($ta==0){$ta = '';}
			$tp = $v['tp_cas'];
			if ($tp==0){$tp = '';}
			$na = $v['naklad'];
			if ($na==0){$na = '';}

			$container->addText('popis'.$i,  'Popis:',300)->setValue($v['popis']);
			//$container->addText('druh'.$i,  'Druh:',30)->setValue($v['zkratka']);
			$container->addText('ta_cas'.$i, 'Ta [min]:')->setValue($ta)
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
			$container->addText('tp_cas'.$i, 'Tp [min]:')->setValue($tp)
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
			$container->addText('naklad'.$i, 'Náklad [Kč]:')->setValue($na)
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
			$container->addHidden('pop'.$i)->setValue($v['popis']);
			$container->addHidden('tac'.$i)->setValue($ta);
			$container->addHidden('tpc'.$i)->setValue($tp);
			$container->addHidden('nak'.$i)->setValue($na);
			$container->addHidden('idto'.$i)->setValue($v['idto']);
			$container->addHidden('ido'.$i)->setValue($v['ido']);
		}
		$form->addSubmit('save', 'Uložit'); //->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'groupoFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	public function groupoFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$oper  = new Operace;
			$ret = $oper->prepGroupOperData($form['mpole']->values);
			
			$gdata = $ret['gdata'];
			$idata = $ret['idata'];
			$r = $ret['r'];
			
			if( $r > 0 ){
				$id_produkt = $this->getIdFromMySet(4);
				if ($id_produkt  == 0){
					$this->flashMessage('Hromadné uložení operací nebude provedeno, neboť není vybrán aktivní produkt.','exclamation');
					$this->redirect('Produkt:default');
				} else {
					$pocet = $oper->insUpdGroupOper($gdata, $idata, $id_produkt, $r);
					$prod = new Produkt();
					$prod->insertProductStatus($id_produkt, self::stTPVSTARTED, $this->user->id);
					$instext = "";
					if($pocet['i'] > 0){$instext = ", vloženo ".$pocet['i'];}
					$this->flashMessage("Bylo aktualizováno ".$pocet['u'].$instext." záznamů výrobních operací.");
				}
			} else {
					$this->flashMessage('Hromadné uložení operací nebylo provedeno, neboť nebyly provedeny žádné změny.');
			}
		}
		if($this->getParam('src') > 0){
			$this->redirect('Postup:default');
		} else {
			$this->redirect('default');
		}
	}
	
	
	protected function createComponentTcalcForm()
	{
		$form = new Nette\Application\UI\Form;
		$oper = new Operace;
		$id = $this->getParam('id');
		$id_produkt = $this->getIdFromMySet(4);
		$data = $oper->showCalcOper($id, $id_produkt)->fetchAll();
		$container = $form->addContainer('mpole');
		foreach($data as $k => $v){
			$mn = $v['mnozstvi'];
			if ($mn==0){$mn = '';}
			$container->addText('ks_'.$v['ida'])->setValue($mn)
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
			$container->addHidden('mn_'.$v['ida'])->setValue($mn);
			$container->addHidden('ts_'.$v['ida'])->setValue($v['cas_sec']);
			$container->addHidden('tt_'.$v['ida'])->setValue($v['typ']);
			$container->addHidden('ao_'.$v['ida'])->setValue($v['idao']);
		}
		$form->addHidden('id_produkt');
		$form->addHidden('ta_min');
		$form->addHidden('ta_rezerva');
		
		$form->addSubmit('save', 'Uložit a přepočítat')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'tcalcFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	public function tcalcFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$id_produkt = (int) $form['id_produkt']->value;
			$ta_min = (float) $form['ta_min']->value;
			$ta_rez = (float) $form['ta_rezerva']->value;
			$oper = new Operace;
			$rows = (array) $form['mpole']->values;
			$gdata = array();
			$idata = array();
			$i = 0;
			$r = 0;
			$mno = 0;
			$mmm = 0;
			$tcs = 0;
			$ida = 0;
			$idt = 0;
			$iao = 0;
			$sum1 = 0;
			$sum2 = 0;
			foreach($rows as $k => $v){
				$i++;
				$ida = (int) substr($k, 3);
				if(substr($k,0,3) == "ks_"){
					$mno = (float) $v;
				} elseif(substr($k,0,3) == "ts_"){
					$tcs = (float) $v;
				} elseif(substr($k,0,3) == "tt_"){
					$idt = (float) $v;
				} elseif(substr($k,0,3) == "mn_"){
					$mmm = (float) $v;
				} elseif(substr($k,0,3) == "ao_"){
					$iao = (int) $v;
				}
				if ($i == 5){
					if ($mno <> $mmm){
						$r++;
						$idata[$r]['idao'] = $iao;
						$gdata[$r]['id_operace']  = $id;
						$gdata[$r]['id_produktu'] = $id_produkt;
						$gdata[$r]['id_atr_casu'] = $ida;
						$gdata[$r]['mnozstvi']	  = $mno;
						$gdata[$r]['cas_min']	  = $mno * $tcs / 60;
					}
					if($idt == 1){
						$sum1 += $mno * $tcs / 60;
					} elseif ($idt == 2){
						$sum2 += $mno * $tcs / 60;
					}
					$i = 0;
					$mno = 0;
					$mmm = 0;
					$tcs = 0;
					$ida = 0;
					$idt = 0;
				}
			}
			$odata = array();
			$sum1 = $sum1 * (1 + $ta_rez);
			if ($sum1 < $ta_min){$sum1 = $ta_min;}
			$odata['ta_cas'] = $sum1;
			$odata['tp_cas'] = $sum2;
			if($r > 0){
					$pocet = $oper->insUpdTcalc($gdata, $idata, $id, $r);
					$oper->update($id, $odata);
					$instext = "";
					if($pocet['i'] > 0){$instext = ", vloženo ".$pocet['i'];}
					$this->flashMessage("Bylo aktualizováno ".$pocet['u'].$instext." záznamů kalkulace spotřeby času.");
			} else {
					$this->flashMessage('Hromadné uložení operací nebylo provedeno, neboť nebyly provedeny žádné změny.');
			}
		}

		if($this->getParam('src') > 0){
			$this->redirect('Postup:default');
		} else {
			$this->redirect('default');
		}
	}	
	
	
	
	
	
}
