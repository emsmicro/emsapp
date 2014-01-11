<?php

use Nette\Application\UI\Form,
	Nette\Application as NA,
	Vodacek\Forms\Controls\DateInput;

/**
 * Set sazeb operaci presenter
 */

class PostupPresenter extends TpvPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Technologické postupy';
    const TITUL_ADD 	= 'Nový postup';
    const TITUL_EDIT 	= 'Změna postupu';
    const TITUL_DELETE 	= 'Smazání postupu';
    const TITUL_GROUP 	= 'Hromadné zadání operací postupu';
    /**
	 * @var string
	 * @titul
	 */  
	private $titul;
	/** @var Nette\Database\Table\Selection */
    private $items;

	private $idt;
	private $idproduct;
	


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
		$item = new Postup;
		$id_produkty = $this->getIdFromMySet(4);
		$items = $item->show($id_produkty)->fetchAll();
		$this->template->items = $items;
		$this->template->idp = $id_produkty;
		$this->template->namep = $this->getNameFromMySet(4);
		if (count($items)==1 and $id_produkty > 0){
			$id = $items[0]['id'];
			$this->redirect('detail', $id);
		}
        $this->template->titul = self::TITUL_DEFAULT;
	}
	/********************* views detail *********************/
	
	/**
	 * Detail TP vč. šablon
	 * @param type $id = id_tpostup
	 */
	public function renderDetail($id = 0)
	{
        $post = new Postup;
		$item = $post->find($id)->fetch();
		$idp = (int) $this->getIdFromMySet(4);
		if($item){
			if($idp == 0){
				$idp = $item->id_produkty;
			}
			$this->idproduct = $idp;
			if(intval($this->getIdFromMySet(4))==0){$this->setIntoMySet(4, $item->id_produkty);}
			$this->template->item = $item;
			$this->template->titul = "Postup: ".$item->nazev;
			$sabl = $post->getOperPostupSablona($id);
			$this->template->sabl = $sabl;
			$this->template->idt = $id;
			$oper = new Operace;
			$noop = $oper->show($this->idproduct, true, $id)->fetchAll();
			$this->template->noop = $noop;
			$this->idt = $id;
			$this->template->unlocked = $post->isProductLocked($this->idproduct)<1;
		}
	}


	/********************* views add & edit *********************/

	/**
	 * Add TP
	 * @return void
	 */
	public function renderAdd()
	{
		$this->idproduct = $this->getIdFromMySet(4);
		if ($this->idproduct<1){
			$this->flashMessage('S modulem POSTUPY nelze pracovat. Nebyl aktivován žádný produkt v rámci nabídky.','exclamation');
			$this->redirect('default');
		}
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
			$prod = new Produkt;
			$defa = $prod->find($this->idproduct)->fetch();
			$form['id_produkty']->value = $defa->id;
			$form['zkratka']->value = $defa->zkratka . " :TP";
			$form['nazev']->value = $defa->nazev . " (TP)";
			$form['id_k2']->value = $defa->id_k2;
			$form['save']->caption = 'Přidat';
		}
        $this->template->titul = self::TITUL_ADD;
		$this->template->is_addon = TRUE;

	}

	/**
	 * Edit TP
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */

	public function renderEdit($id = 0)
	{
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
			$item = new Postup;
            $row = $item->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDIT;

	}



	/**
	 * Delete item from tpostup
	 * @param type $id
	 * @throws Nette\Application\BadRequestException
	 */
	public function renderDelete($id = 0)
	{
		$item = new Postup;
		$this->template->item = $item->find($id)->fetch();
		if (!$this->template->item) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = self::TITUL_DELETE;

	}

	/**
	 * Add sablona into TP
	 * @param type $id = id_tpostup
	 */
	public function renderAddSabl($id)
	{
			$item = new Postup;
			$post = $item->find($id)->fetch();
			$por = (int) $post->mporadi;
			$poradi = $por+1;
			$this['sablForm']['poradi']->value = $poradi;
			$this['sablForm']['save']->caption = 'Přidat';
			$this->template->titul = "Nová skupina operací postupu";
			$this->template->subtitul = "Postup: ".$post->zkratka;					
	}

	/**
	 * 
	 * @param type $id = id_tpostup
	 * @param type $ids = id_sablony
	 * @throws NA\BadRequestException
	 */
	public function renderEditSabl($id, $ids)
	{
		$item = new Postup;
		$post = $item->findTPSabl($id, $ids)->fetch();
		$form = $this['sablForm'];
		$this['sablForm']['id_sablony']->value = $ids;
		if (!$form->isSubmitted()) {
			if (!$post) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($post);
			$this->template->titul = "Změna šablony postupu";
			$this->template->subtitul = "";					
		}
	}
	
	/**
	 * 
	 * @param type $id = id_tpostup
	 * @param type $ids = id_sablony
	 */
	public function renderAddGroup($id = 0, $ids = 0)
	{
		$this->idproduct = $this->getIdFromMySet(4);
//		$items = new Operace;
//		$data = $items->getTypesOper($this->idproduct, $id, $ids);
		$items = new Postup;
		$data = $items->getOperPostupSablona($id, $ids, $isAssoc = 0);
        $this->template->titul = self::TITUL_GROUP;
		$this->template->npostup = 'Postup: ' . $data[0]['npostup'];
		$this->template->nsablona = 'Šablona: ' . $data[0]['nsablona'];
		$form = $this['addGroupForm'];
		$form['id_tpostup']->value = $id;
		$form['id_sablony']->value = $ids;
		// reset default render
		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = NULL;
		$renderer->wrappers['pair']['container'] = NULL;
		$renderer->wrappers['label']['container'] = NULL;
		$renderer->wrappers['control']['container'] = NULL;
		$this->template->items = $data;
		$this->template->form = $form;
	}
	/********************* view delete rate *********************/
	/**
	 * @param int
	 * @throws BadRequestException
	 * @return void
	 */
	public function renderDeleteSabl($id, $ids)
	{
		$post = new Postup;
		$this->template->post = $post->find($id)->fetch();
		if (!$this->template->post) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = "Výmaz šablony v postupu";
	}


	/**
	 * Add operation by current record tpostup_sablony
	 * @param type $id ... id_tpostup
	 * @param type $ids .. id_sablony
	 * @param type $p .... poradi
	 */
	public function renderAddOper($id, $ids, $p)
	{
			$item = new Postup;
			$oper = $item->getPostupSablonaPoradi($id, $ids, $p)->fetch();
			$form = $this['operForm'];
			$this['operForm']['a_id_produkty']->value = $oper->id_produkty;
			$this['operForm']['a_id_operace']->value = 0;
			if (!$form->isSubmitted()) {
				if (!$oper) {
					throw new NA\BadRequestException('Záznam nenalezen.');
				}
				$form['save']->caption = 'Přidat';
				$form->setDefaults($oper);
				$this->template->titul = "Nová operace postupu";
				$this->template->subtitul1 = "Postup: ".$oper->npostup;					
				$this->template->subtitul2 = "Šablona: ".$oper->nsablona;					
			}
	}	
	
	

	/********************* component factories *********************/



	/**
	 * Item add and edit form component factory.
	 * @return mixed
	 */
	protected function createComponentItemForm()
	{
		$form = new Form;
		$id = (int) $this->getParam('id');
		
		$form->addText('zkratka', 'Zkratka:', 50)
				->setRequired('Uveďte zkratku.' );
		
		$form->addTextArea('nazev', 'Název:', 60, 4)
				->setRequired('Uveďte název.');
			
		$form->addText('id_k2', 'K2 číslo:')
			->setAttribute('class', 'cislo')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::INTEGER, 'Hodnota musí být celé číslo.');
		
		if($id==0){
			$post = new Postup;
			$sabl = $post->getSablony();
			$form->addSelect('a_id_sablony', 'Šablona:', $sabl)
						->setPrompt('.. Zvolte šablonu operací ..');

			$form->addText('a_poradi', 'Pořadí:',3)
					->setAttribute('class', 'cislo')
					->setOption('description', '(pořadí skupiny operací v TP)')
					->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
						->controlPrototype
							->autocomplete('off');
		}
		$form->addHidden('id_produkty');
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'itemFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function itemFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$item = new Postup;
			$id = (int) $this->getParam('id');
			$data = $item->getPrefixedFormFields($form->values);
			if ($id > 0) {
				$item->update($id, $data);
				$this->flashMessage('Postup byl změněn.');
			} else {
				$sabl = $item->getPrefixedFormFields($form->values,'a_');				
				$id = $item->insert($data);
				if($sabl['id_sablony']>0){
					$sabl['id_tpostup'] = $id;
					$item->insertSabl($sabl);
				}
				$this->flashMessage('Postup byl založen.');
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
			$item = new Postup;
			$item->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}
/********************* rate component factories *********************/



	/**
	 * Sablony to TP add and edit form component factory.
	 * @return mixed
	 */
	protected function createComponentSablForm()
	{
		$form = new Form;
		$post = new Postup;
		$sabl = $post->getSablony();
		$form->addSelect('id_sablony', 'Šablona:', $sabl)
			        ->setPrompt('.. Zvolte šablonu operací ..')
					->addRule(Form::FILLED, 'Vyberte šablonu skupiny operací');

		$form->addText('poradi', 'Pořadí:',3)
				->setRequired('Uveďte pořadí operace v postupu.')
				->setAttribute('class', 'cislo')
				->setOption('description', '(pořadí skupiny operací v TP)')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::INTEGER, 'Hodnota musí být celé číslo.');

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'sablFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function sablFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$ids = (int) $this->getParam('ids');
			$rate = new Postup;
			$data = (array) $form->values;
			$data['id_sablony'] = (int) $data['id_sablony'];
			if ($id > 0 && $ids > 0) {
				$rate->updateSabl($id, $ids, $data);
				$this->flashMessage('Položka byla změněna.');
			} else {
				$data['id_tpostup'] = $id;
				$rate->insertSabl($data);
				$this->flashMessage('Položka byla přidána.');
			}
		}

		$this->redirect('detail',$this->getParam('id'));

	}



	/**
	 * Rate delete form component factory.
	 * @return mixed
	 */
	protected function createComponentDeleteSabl()
	{
		$form = new Form;
		$form->addSubmit('delete', 'Smazat')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno');
		$form->onSuccess[] = callback($this, 'deleteSablSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function deleteSablSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$item = new Postup;
			$item->deleteSabl($this->getParam('sid'), $this->getParam('sids'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('detail',$this->getParam('id'));
	}

	protected function createComponentAddGroupForm()
	{
		$form = new Nette\Application\UI\Form;
		$idp = $this->getIdFromMySet(4);
		$id = (int) $this->getParam('id');
		$ids = (int) $this->getParam('ids');
		$items = new Postup;
		$data = $items->getOperPostupSablona($id, $ids, $isAssoc = 0);

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
			$container->addHidden('idto'.$i)->setValue($v['id_typy_operaci']);
			$container->addHidden('ido'.$i)->setValue($v['ido']);
			$container->addHidden('poradi'.$i)->setValue($v['poradi']);
		}
		$form->addHidden('id_tpostup');
		$form->addHidden('id_sablony');
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'groupoFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	public function groupoFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$oper = new Operace;
			$ret = $oper->prepGroupOperData($form['mpole']->values, 
											(int) $form['id_tpostup']->value, 
											(int) $form['id_sablony']->value);
						
			$gdata = $ret['gdata'];
			$idata = $ret['idata'];
			$r = $ret['r'];
			
			if( $r > 0 ){
				$id_produkt = $this->getIdFromMySet(4);
				$oper = new Operace;
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
		if($this->getParam('src')==1){
			$this->redirect('Postup:default');
		} else {
			$this->redirect('default');
		}
	}
	
	
	
	/**
	 * Item add and edit form component factory.
	 * @return mixed
	 */
	protected function createComponentOperForm()
	{
		$form = new Form;
		$comp = new Model;
		$typ = $comp->getOperationType();
		$id = (int) $this->getParam('id');
		$tamin = 0;
		if ($id > 0){
			$instance = new Operace;
            $row = $instance->find($id)->fetch();
			$tamin = $row['ta_min'];
		}
		$form->addHidden('ta_min');
		$form->addHidden('ta_rezerva');

		$form->addSelect('id_typy_operaci', 'Typ:', $typ)
			        ->setPrompt('Zvolte typ operace')
			        ->addRule(Form::FILLED, 'Zvolte typ operace');
		
		$form->addText('poradi', 'Pořadí:', 4)
				->setRequired('Uveďte pořadí operace ve skupině.')
				->controlPrototype
					->autocomplete('off')
				->addCondition($form::FILLED);

		$form->addTextArea('popis', 'Popis:');
		//$form->addHidden('id_typy_operaci');
		$form->addHidden('id_sablony');
		$form->addHidden('id_tpostup');
		$form->addHidden('a_id_produkty');
		$form->addHidden('a_id_operace');

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
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'operFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function operFormSubmitted(Form $form)
	{
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
			$id_operace = $prod['id_operace'];
			if($data['id_sablony']==''){$data['id_sablony']=NULL;}
			if($data['id_tpostup']==''){$data['id_tpostup']=NULL;}
			dump($data,$idp);
			//exit;
			if ($id_operace > 0) {
				$item->update($id_operace, $data);
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
			if($this->getParam('src')==1){
				$this->redirect('Postup:default');
			} else {
				$this->redirect('default');
			}
		}
	}
	
	
	
	
	
}
