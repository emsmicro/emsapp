<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;

/**
 * Typy Operaci Presenter
 */
class TypOperacePresenter extends TpvPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Typové operace';
    const TITUL_ADD 	= 'Nová typová operace';
    const TITUL_EDIT 	= 'Změna typové operace';
    const TITUL_DELETE 	= 'Smazání typové operace';
    /**
	 * @var string
	 * @titul
	 */
	private $titul;
	
	/** @var Nette\Database\Table\Selection */
	private $items;
	

	public function startup()
	{
		parent::startup();
//      $instance = new TypOperace;
//		$this->items = $instance->show();

	}


	/********************* view default *********************/

	/**
	 * @return void
	 */

	public function renderDefault()
	{

        $instance = new TypOperace;
		$this->template->items = $instance->show()->orderBy('poradi');
        $this->template->titul = self::TITUL_DEFAULT;

	}
/********************* view detail *********************/
	/**
	 * @param int
	 * @return void
	 */
	
	public function renderDetail($id = 0)
	{
        $instance = new TypOperace;
		$item = $instance->find($id)->fetch();

		$this->template->item = $item;
	   	$this->template->titul = $item->nazev;
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
	 * @throws BadRequestException
	 * @return void
	 */

	public function renderEdit($id = 0)
	{
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
	        $instance = new TypOperace;
            $row = $instance->find($id)->fetch();
			if (!$row) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$row['ta_rezerva'] = $row['ta_rezerva']*100;
			$form->setDefaults($row);
		}
		$this->template->titul = self::TITUL_EDIT;

	}



	/********************* view delete *********************/
	/**
	 * @param int
	 * @throws BadRequestException
	 * @return void
	 */
	public function renderDelete($id = 0)
	{
        $instance = new TypOperace;
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
		$comp = new Model;
		$druh = $comp->getOperationKind();
		$form->addSelect('id_druhy_operaci', 'Druh:', $druh)
			        ->setPrompt('Zvolte druh operace')
			        ->addRule(Form::FILLED, 'Zvolte druh operace');		
        $form->addText('zkratka', 'Zkratka:', 35)
			->setRequired('Uveďte zkratku.');

		$form->addText('nazev', 'Název:', 60)
			->setRequired('Uveďte název.');

		$stroje = $comp->getStroje();
		$form->addSelect('id_stroje', 'Stroj:', $stroje)
			        ->setPrompt('[..zvolte stroj nebo nic..]');		
		
		$ttarify = $comp->getTariffType();
		$form->addSelect('id_typy_tarifu', 'Operátor:', $ttarify)
			        ->setPrompt('[..zvolte tarif operátora..]')
			        ->addRule(Form::FILLED, 'Zvolte tarif operátora');
				
		$form->addText('poradi', 'Pořadí:',5)
			->setOption('description', 'typické pořadí v technologickém postupu')
			->setRequired('Uveďte pořadí v TP.');

		$form->addText('ta_min', 'Min. čas:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[min/ks] = minimální výrobní čas')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('ta_rezerva', 'Rezerva:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[%] = navýšení výrobníhu času')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$form->addText('tp_default', 'Přípr. čas:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[min/ks] = defaultní přípravný čas')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

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
	        $instance = new TypOperace;
			$data = $form->values;
			$data['ta_rezerva'] = $data['ta_rezerva']/100;
			if ($id > 0) {
				$instance->update($id, $data);
				$this->flashMessage('Položka byla změněna.');
			} else {
				$instance->insert($data);
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
	        $instance = new TypOperace;
			$instance->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}

}
