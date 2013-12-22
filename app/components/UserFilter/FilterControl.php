<?php

use Nette\Application\UI\Form,
	Nette\Application\UI\Control;


/**
 * Komponenta Filtru
 */
class FilterControl extends Control
{
	const MESS_PROTECT	= 'Vypršel ochranný časový limit, odešlete prosím formulář ještě jednou.';

	/** @var     int */
	private $presenter;
	private $render;
	private $userID;

	public function setPresenter($presName)
	{
		$this->presenter = $presName;
	}

	public function setRender($rendName)
	{
		$this->render = $rendName;
	}

	public function setUser($userID)
	{
		$this->userID = $userID;
	}	

	public function getFilter()
	{
		$fm = new FilterModel();
		return $fm->getUserFilter($this->userID, $this->presenter, $this->render)->fetchSingle();
	}	

	/**
	 * 
	 */
	public function render()
	{
		$fm = new FilterModel();
		$this->template->setFile(__DIR__ . '/FilterControl.latte');
		$tfilter = $fm->getUserFilter($this->userID, $this->presenter, $this->render)->fetchSingle();
		$form = $this['filterForm'];
		$form['filter']->value = ($tfilter);
		$this->template->render();
	}

	
	/**
	 * stat filter form component factory.
	 * @return mixed
	 */
	protected function createComponentFilterForm()
	{
		$form = new Form;
		$form->addText('filter', 'Hledat', 30)
					->setAttribute('placeholder', 'Zadejte filtr ...');		
		$form->addSubmit('setfilter', ' ')->setAttribute('class', 'default');
		$form->onSuccess[] = callback($this, 'filterFormSubmitted');

		$form->addProtection(self::MESS_PROTECT);
		return $form;
	}


	public function filterFormSubmitted(Form $form)
	{
		if ($form['setfilter']->isSubmittedBy()) {
			$fm = new FilterModel();
			$fm->setUserFilter($this->userID, $this->presenter, $this->render, $form['filter']->value);
			$this->flashMessage('Filtr byl změněn.');
		}
		$this->redirect('this');
	}	

}
