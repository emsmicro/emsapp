<?php

use Nette\Application\UI\Form,
	Nette\Application as NA,
	Vodacek\Forms\Controls\DateInput;

/**
 * Set sazeb operaci presenter
 */

class SablonaPresenter extends TpvPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Šablony technologických postupů';
    const TITUL_ADD 	= 'Nová šablona';
    const TITUL_EDIT 	= 'Změna šablony';
    const TITUL_DELETE 	= 'Smazání šablony';
    const TITUL_GROUP 	= 'Hromadné zadání definice šablony';
    /**
	 * @var string
	 * @titul
	 */  
	private $titul;
	/** @var Nette\Database\Table\Selection */
    private $items;
	/** @var Nette\Database\Table\Selection */
	private $sazby;
	/*
	 * @var int
	 * @tid
	 * @idss
	 */
	private $ids;
	


	public function startup()
	{
		parent::startup();
		$item = new Sablona;
		$this->items = $item->show();

	}


	/********************* view default *********************/

	/**
	 * @return void
	 */

	public function renderDefault()
	{
		$item = new Sablona;
		$this->template->items = $item->show();
        $this->template->titul = self::TITUL_DEFAULT;
	}
	/********************* views detail *********************/
	
	/**
	 * Detail Šablony TP vč. typových operací
	 * @param type $id
	 */
	public function renderDetail($id = 0)
	{
        $instance = new Sablona;
		$item = $instance->find($id)->fetch();
		$dr = $instance->getOperAllKind();
		if($item && $dr){
			$this->template->item = $item;
			$this->template->druhy = $dr;
			$this->template->titul = "Šablona: ".$item->nazev;
			$typo = $instance->showSablTypOper($id);
			$this->template->typo = $typo;
			$this->template->ids = $id;
			$this->ids = $id;
		}
	}


	/********************* views add & edit *********************/

	/**
	 * @return void
	 */

	public function renderAdd()
	{
		$this['itemForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADD;
		$this->template->is_addon = TRUE;

	}

	/**
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */

	public function renderEdit($id = 0)
	{
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
			$item = new Sablona;
            $row = $item->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDIT;

	}



	/**
	 * Delete item from Sablony
	 * @param type $id
	 * @throws Nette\Application\BadRequestException
	 */
	public function renderDelete($id = 0)
	{
		$item = new Sablona;
		$this->template->item = $item->find($id)->fetch();
		if (!$this->template->item) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = self::TITUL_DELETE;

	}

	/**
	 * Add typ_operace into sablona
	 * @param type $id = id_sablony
	 * @param type $idd = id_druhy_operaci
	 */
	public function renderAddTypo($id, $idd = 0)
	{
			$item = new Sablona;
			$sabl = $item->find($id)->fetch();
			$por = (int) $sabl->mporadi/10;
			$sporadi = (string) ($por*10 + 10);
			$poradi = str_pad($sporadi, 3, "0", STR_PAD_LEFT);
			$this['typoForm']['poradi']->value = $poradi;
			$this['typoForm']['save']->caption = 'Přidat';
			if($idd > 0){
				$dr = $item->getOperAllKind($idd);
				if($dr){
					$this->template->titul = "Nová typová operace šablony";					
					$this->template->subtitul = "Šablona: ".$sabl->zkratka.", druh: ".$dr[0]->nazev;					
				} else {
					$this->template->titul = "Nová typová operace šablony";
					$this->template->subtitul = "Šablona: ".$sabl->zkratka;					
				}
			} else {
				$this->template->titul = "Nová typová operace šablony";
				$this->template->subtitul = "Šablona: ".$sabl->zkratka;					
			}
	}

	/**
	 * 
	 * @param type $id = id_sablony
	 * @param type $idt = id_typy_operaci
	 * @throws NA\BadRequestException
	 */
	public function renderEditTypo($id, $idt)
	{
		$item = new Sablona;
		$sabl = $item->findSablTyp($id, $idt)->fetch();
		$this['typoForm']['id_typy_operaci']->value = $idt;
		$form = $this['typoForm'];
		if (!$form->isSubmitted()) {
			if (!$sabl) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($sabl);
			$this->template->titul = "Změna typové operace šablony";
			$this->template->subtitul = "";					
		}
	}
	
	/**
	 * @param int id = id_set_sazeb_o
	 * @return void
	 */
	public function renderAddGroup($id=0)
	{
		$oper = new SazbaO;
		$data = $oper->getTypesOper($id)->fetchAll();
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
	/********************* view delete rate *********************/
	/**
	 * @param int
	 * @throws BadRequestException
	 * @return void
	 */
	public function renderDeleteTypo($id, $idt)
	{
		$sabl = new Sablona;
		$this->template->sabl = $sabl->find($id)->fetch();
		if (!$this->template->sabl) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = "Výmaz operace v šabloně";
	}



	/********************* component factories *********************/



	/**
	 * Item add and edit form component factory.
	 * @return mixed
	 */
	protected function createComponentItemForm()
	{
		$form = new Form;
 
		$form->addText('zkratka', 'Zkratka:', 50)
				->setRequired('Uveďte zkratku.' );
		
		$form->addTextArea('nazev', 'Název:', 60, 4)
				->setRequired('Uveďte název.');
			
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'itemFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function itemFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$item = new Sablona;
			if ($id > 0) {
				$item->update($id, $form->values);
				$this->flashMessage('Položka byla změněna.');

			} else {
				$item->insert($form->values);
				$this->flashMessage('Položka byla přidána.');
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
			$item = new Sablona;
			$item->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}
/********************* rate component factories *********************/



	/**
	 * Rate add and edit form component factory.
	 * @return mixed
	 */
	protected function createComponentTypoForm()
	{
		$form = new Form;
		$sabl = new Sablona;
		$idd = (int) $this->getParam('idd');
		$typo = $sabl->getOperationType($idd);
		$form->addSelect('id_typy_operaci', 'Operace:', $typo)
					//->setPrompt('Zvolte typovou operaci')
					->addRule(Form::FILLED, 'Vyberte typovou operaci');

		$form->addText('poradi', 'Pořadí:',3)
				->setRequired('Uveďte pořadí operace v postupu.');

		$form->addTextArea('nazev', 'Název:', 60, 4)
				->setRequired('Uveďte název.');

		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'typoFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function typoFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			$idt = (int) $this->getParam('idt');
			$rate = new Sablona;
			$data = (array) $form->values;
			$data['id_typy_operaci'] = (int) $data['id_typy_operaci'];
			if ($id > 0 && $idt > 0) {
				$rate->updateTypo($id, $idt, $data);
				$this->flashMessage('Položka byla změněna.');
			} else {
				$data['id_tp_sablony'] = $id;
				$rate->insertTypo($data);
				$this->flashMessage('Položka byla přidána.');
			}
		}

		$this->redirect('detail',$this->getParam('id'));

	}



	/**
	 * Rate delete form component factory.
	 * @return mixed
	 */
	protected function createComponentDeleteTypo()
	{
		$form = new Form;
		$form->addSubmit('delete', 'Smazat')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno');
		$form->onSuccess[] = callback($this, 'deleteTypoSubmitted');
		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}



	public function deleteTypoSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$item = new Sablona;
			$item->deleteTypo($this->getParam('sid'), $this->getParam('sidt'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('detail',$this->getParam('id'));
	}

	protected function createComponentAddGroupForm()
	{
		$form = new Nette\Application\UI\Form;
		$oper = new SazbaO;
		$id_set_sazeb_o = $this->getParam('id');
		$data = $oper->getTypesOper($id_set_sazeb_o)->fetchAll();
		$container = $form->addContainer('mpole');
		$i = 0;
		foreach($data as $k => $v){
			$i++;
			$container->addText('hodnota'.$i, 'Hodnota:')->setValue($v['hodnota'])
				->setAttribute('class', 'cislo')
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
					->controlPrototype
						->autocomplete('off')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
			$container->addHidden('hodn'.$i)->setValue($v['hodnota']);
			$container->addHidden('idto'.$i)->setValue($v['idto']);
			$container->addHidden('idso'.$i)->setValue($v['idso']);
		}
		$form->addHidden('id_set_sazeb_o')->setValue($id_set_sazeb_o);
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(NULL);
		$form->onSuccess[] = callback($this, 'groupoFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	public function groupoFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$oper = new SazbaO;
			$rows = (array) $form['mpole']->values;
			$gdata = array();
			$idata = array();
			$idss = $form['id_set_sazeb_o']->value;
			$j = 0;
			$r = 0;
			$h = 0;
			$h0 = 0;
			$idto = 0;
			$idso = 0;
			foreach($rows as $k => $v ){
				$j++;
				switch($j){
					case 1:
						$h = floatval($v);
					case 2:
						$h0 = floatval($v);
					case 3:
						$idto = intval($v);
					case 4:
						$idso = intval($v);
				}
				if($j == 4) {
					if ($h <> $h0){
						$r++;
						$idata[$r]['idso'] = $idso;
						$gdata[$r]['hodnota'] = $h;
						$gdata[$r]['id_typy_operaci'] = $idto;
						$gdata[$r]['id_set_sazeb_o'] = (int) $idss;
					}
					$j = 0;
					$h = 0;
					$h0 = 0;
					$idto = 0;
					$idso = 0;
				}
			}
			if($r > 0){
					$pocet = $oper->insUpdGroupo($gdata, $idata, $idss, $r);
					$instext = "";
					if($pocet['i'] > 0){$instext = ", vloženo ".$pocet['i'];}
					$this->flashMessage("Bylo aktualizováno ".$pocet['u'].$instext." záznamů sazeb typových operací.");
				
			} else {
					$this->flashMessage('Hromadné uložení operací nebylo provedeno, neboť nebyly změněny žádné údaje.');
			}
		}

		$this->redirect('detail', $form['id_set_sazeb_o']->value);
	}
	
}
