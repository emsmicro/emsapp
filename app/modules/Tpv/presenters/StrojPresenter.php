<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;

/**
 * Typy Operaci Presenter
 */
class StrojPresenter extends TpvPresenter
{
    /** Title constants */
    const TITUL_DEFAULT = 'Stroje - strojní technologická zařízení';
    const TITUL_ADD 	= 'Nový stroj';
    const TITUL_EDIT 	= 'Změna stroje';
    const TITUL_DELETE 	= 'Smazání stroje';
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
        $instance = new Stroj;
		$this->items = $instance->show();

	}


	/********************* view default *********************/

	/**
	 * @return void
	 */

	public function renderDefault()
	{

        $instance = new Stroj;
		$this->template->items = $instance->show()->orderBy('zkratka');
        $this->template->titul = self::TITUL_DEFAULT;
		$this->template->params = $this->mpars;
	}
/********************* view detail *********************/
	/**
	 * @param int
	 * @return void
	 */
	
	public function renderDetail($id)
	{
        $instance = new Stroj;
		$item = $instance->find($id)->fetch();

		$this->template->item = $item;
	   	$this->template->titul = $item->zkratka;
		$this->template->params = $this->mpars;
	}

	/********************* views add & edit *********************/

	/**
	 * @return void
	 */

	public function renderAdd()
	{
        $this->template->titul = self::TITUL_ADD;
		$this->template->params = $this->mpars;
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
	        $instance = new Stroj;
            $data = $instance->find($id)->fetch();
			if (!$data) {
				throw new NA\BadRequestException('Záznam nenalezen.');
			}
			$data['sazba_instalace']	= $data['sazba_instalace']*100;
			$data['vytizeni']			= $data['vytizeni']*100;
			$data['vyuziti_prikonu']	= $data['vyuziti_prikonu']*100;
			$data['naklady_udrzba']		= $data['naklady_udrzba']*100;
			$data['naklady_ostatni']	= $data['naklady_ostatni']*100;
			$form->setDefaults($data);
		}
		$this->template->titul = self::TITUL_EDIT;
		$this->template->params = $this->mpars;

	}

	public function actionRecalAll()
	{
		$inst = new Stroj;
		$inst->recalculateAll();
		$this->flashMessage('Sazby strojů byly zaktualizovány.');
		$this->redirect('default');

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
		
        $form->addText('zkratka', 'Zkratka:', 35)
			->setRequired('Uveďte zkratku.');

		$form->addText('nazev', 'Název:', 90)
			->setRequired('Uveďte název.');
		
        $form->addText('rok_porizeni', 'Rok pořízení:', 6)
			->setRequired('Uveďte rok pořízení.')
			->addCondition($form::FILLED)
					->addRule($form::INTEGER, 'Hodnota musí být celé číslo.');
		
		$form->addText('poriz_cena', 'Pořizovací cena (PC):')
			->setAttribute('class', 'cisloLong')
			->setOption('description', '[Kč]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('sazba_instalace', 'Podíl nákladů na instalaci:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[% z PC], obvykle 2 %')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('smennost', 'Plán. směnnost:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[směn/den]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('vytizeni', 'Vytížení:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[%], má vliv na roční kapacitu')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('stari', 'Stáří stroje:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[let]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$form->addText('doba_odpisu', 'Kalk. doba odepisování:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[let]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('plocha', 'Zabraná plocha:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[m2], vč. pochůzné a odkládací plochy')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$form->addText('prikon', 'Elelktrický příkon:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[kVA, kW]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$form->addText('vyuziti_prikonu', 'Využití příkonu:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[%]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$form->addText('spotreba_dusiku', 'Spotřeba dusíku:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[m3/hod]')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');
		
		$form->addText('naklady_udrzba', 'Podíl nákladů na údržbu:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[% z PC], obvykle 4 %')
			->addFilter(array('Nette\Forms\Controls\TextBase', 'filterFloat'))
				->controlPrototype
					->autocomplete('off')
			->addCondition($form::FILLED)
					->addRule($form::FLOAT, 'Hodnota musí být celé nebo reálné číslo.');

		$form->addText('naklady_ostatni', 'Podíl ost. provozních nákladů:')
			->setAttribute('class', 'cislo')
			->setOption('description', '[% z PC], obvykle 2 %')
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
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
	        $instance = new Stroj;
			$data = $form->values;
			if ($id > 0) {
				$instance->update($id, $data, $this->mpars);
				$this->flashMessage('Položka byla změněna.');
			} else {
				$instance->insert($data, $this->mpars);
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
	        $instance = new Stroj;
			$instance->delete($this->getParam('id'));
			$this->flashMessage('Smazáno.');
		}

		$this->redirect('default');
	}

}
