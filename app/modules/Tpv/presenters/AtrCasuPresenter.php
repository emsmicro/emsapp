<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;
/**
 * Operace presenter
 */

class AtrCasuPresenter extends TpvPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Atributy spotřeby času';
    const TITUL_ADD 	= 'Nový atribut spotřeby času';
    const TITUL_EDIT 	= 'Změna atributu spotřeby času';
    const TITUL_DELETE 	= 'Smazání atribut spotřeby času';
    const TITUL_GROUP 	= 'Přiřazení atributu spotřeby času typovým operacím';
     /**
	 * @var string
	 * @titul
	 */ 
	private $titul;
	

	public function startup()
	{
		parent::startup();
        $instance = new AtrCasu;

	}


	/********************* view default *********************/

	/**
	 * @return void
	 */

	public function renderDefault()
	{
        $instance = new AtrCasu;
		$items = $instance->showto()->orderBy('typ')->fetchAll();
		$this->template->items = $items;
        $this->template->titul = self::TITUL_DEFAULT;
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
	 * @return void
	 */
	public function renderAdd()
	{
		$this['itemForm']['save']->caption = 'Přidat';
        $this->template->titul = self::TITUL_ADD;

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
	        $instance = new AtrCasu;
            $row = $instance->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDIT;

	}

	/**
	 * @return void
	 */
	public function renderSetGroup($id)
	{
		$atrib = new AtrCasu;
		$data = $atrib->getTypesOper($id)->orderBy('zkratka','DESC');
		$row = $atrib->find($id)->fetch();
		if (!$row) {
			throw new NA\BadRequestException('Záznam nenalezen.');
		}
		$this->template->titul = self::TITUL_GROUP;
		$this->template->subtitul = $row->nazev;
		$this->template->ida = $row->id;
		$form = $this['setGroupForm'];
		$form['id_atr_casu']->value = $id;
		// reset default render
		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = NULL;
		$renderer->wrappers['pair']['container'] = NULL;
		$renderer->wrappers['label']['container'] = NULL;
		$renderer->wrappers['control']['container'] = NULL;
		$this->template->items = $data;
		$this->template->form = $form;
	}


	/********************* view delete *********************/

	/**
	 * @param int
	 * @return void
	 * @throws BadRequestException
	 */

	public function renderDelete($id = 0)
	{
        $instance = new AtrCasu;
		$this->template->item = $instance->find($id)->fetch();
		if (!$this->template->item) {
			throw new Nette\Application\BadRequestException('Záznam nenalezen!');
		}
		$this->template->titul = self::TITUL_DELETE;

	}


	/********************* component factories *********************/

	/**
	 * Item add and edit form component factory.
	 * @return mixed
	 */
	protected function createComponentItemForm()
	{
		$form = new Form;

		$typa = array(
			1 => 'Přímý čas (Ta)',
			2 => 'Dávkový čas (Tp)'
		);
		$form->addSelect('typ', 'Typ:', $typa);
		
		$form->addText('zkratka', 'Zkratka:');
		$form->addText('nazev', 'Název:', 50);
		
		$form->addText('cas_sec', 'Čas:', 5)
				->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->setOption('description', '[sec/ks]')
				->addCondition($form::FILLED)
						->addRule($form::FLOAT, 'Hodnota "%label" musí být celé nebo reálné číslo.');
		
		
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
			$item = new AtrCasu;
			$data = (array) $form->values;
			$data['cas_sec'] = floatval($data['cas_sec']);
			if ($id > 0) {
				$item->update($id, $data);
				$this->flashMessage('Položka byla změněna.');
			} else {
				$item->insert($data);
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
	        $instance = new Operace;
			$instance->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}


	protected function createComponentSetGroupForm()
	{
		$form = new Nette\Application\UI\Form;
		$oper = new AtrCasu;
		$id = $this->getParam('id');
		$data = $oper->getTypesOper($id)->orderBy('zkratka','DESC');
		$container = $form->addContainer('mpole');
		$i = 0;
		foreach($data as $row => $v){
			$i++;
			$chck = $v['yes']=='true' ? true : false;
			$container->addCheckbox('yes_'.$v['idto'], ' zvolit')->setValue($chck);
		}
		$form->addHidden('id_atr_casu');
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'groupaFormSubmitted');

		//$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	public function groupaFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = $this->getParam('id');
			$atrib = new AtrCasu;
			$rows = (array) $form['mpole']->values;
			$gdata = array();
			$i=0;
			$r=0;
			foreach($rows as $k => $v ){
				if($v){
					$r++;
					$gdata[$r]['id_atr_casu'] = (int) $id;
					$gdata[$r]['id_typy_operaci'] = (int) substr($k, 4);
				}
			}
			if($r>0){
					$pocet = $atrib->insertGroupa($gdata, $id, $r);
					$this->flashMessage('Uloženo '.$pocet.' záznamů přiřazení k typovým operacím.');
			} else {
					$atrib->deleteATO($id);
					$this->flashMessage('Hromadné uložení přiřazení k typovým operacím bylo zrušeno.','exclamation');
					$this->redirect('AtrCasu:default');
			}
		}

		$this->redirect('default');
	}
}
