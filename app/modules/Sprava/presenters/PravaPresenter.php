<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;
/**
 * Operace presenter
 */

class PravaPresenter extends SpravaPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Přehled nastavení přístupových práv dle rolí';
    const TITUL_ADD 	= 'Nová role';
    const TITUL_EDIT 	= 'Změna role';
    const TITUL_DELETE 	= 'Smazání role';
    const TITUL_GROUP 	= 'Nastavení přístupových práv pro roli';
     /**
	 * @var string
	 * @titul
	 */ 
	private $titul;
	

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
        $instance = new Prava;
		$items = $instance->show()->orderBy('idr')->orderBy('modul')->orderBy('presenter')->orderBy('poradi')->fetchAll();
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
        $instance = new Prava;
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
	        $instance = new Prava;
            $row = $instance->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDIT;

	}

	/**
	 * $id .. id role
	 * 
	 * @return void
	 */
	public function renderSetRights($id, $m)
	{
		$prav = new Prava;
		$data = $prav->getRights($id, $m)->orderBy('modul')->orderBy('presenter')->orderBy('poradi');
		$row = $prav->find($id)->fetch();
		if (!$row) {
			throw new NA\BadRequestException('Záznam nenalezen.');
		}
		$this->template->titul = self::TITUL_GROUP;
		$this->template->subtitul = $row->popis;
		$this->template->idr = $row->id;
		$form = $this['setRightsForm'];
		$form['id_role']->value = $id;
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
        $instance = new Prava;
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

		$form->addText('nazev', 'Název:', 30);
		$form->addText('popis', 'Popis:', 30);
		
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
			$item = new Prava;
			$data = (array) $form->values;
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
	        $instance = new Prava;
			$instance->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}


	protected function createComponentSetRightsForm()
	{
		$form = new Nette\Application\UI\Form;
		$pra = new Prava;
		$id = $this->getParam('id');
		$data = $pra->getRights($id)->orderBy('id')->fetchAll();
		dd($data,'DATA');
		//exit;
		$container = $form->addContainer('mpole');
		$i=0;
		foreach($data as $row => $v){
			$i++;
			$chck = $v['yes']=='true' ? true : false;
			$container->addCheckbox('yes_'.$v['id'], ' povolit')->setValue($chck)
							->setAttribute("class","child");
		}
		$form->addCheckbox('chckall', '  Vše')
				->setAttribute("class","checkall");
//				->setAttribute("onclick()","CheckAll();");
		$form->addHidden('id_role');
		$form->addSubmit('save', 'Uložit')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Storno')->setValidationScope(FALSE);
		$form->onSuccess[] = callback($this, 'rightsFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}

	public function rightsFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = $this->getParam('id');
			$m = $this->getParam('m');
			$pra = new Prava;
			$rows = (array) $form['mpole']->values;
			$gdata = array();
			$i=0;
			$r=0;
			foreach($rows as $k => $v ){
				if($v){
					$r++;
					$gdata[$r]['id_role'] = (int) $id;
					$gdata[$r]['id_permission'] = (int) substr($k, 4);
				}
			}
			if($r>0){
					$pocet = $pra->insertRights($gdata, $id, $r, $m);
					$this->flashMessage('Uloženo '.$pocet.' záznamů práv k funkcím agend.');
			} else {
					$pra->deleteATR($id,$m);
					$this->flashMessage('Hromadné uložení práv role bylo zrušeno.','exclamation');
					$this->redirect('Prava:default');
			}
		}

		$this->redirect('default');
	}
}
