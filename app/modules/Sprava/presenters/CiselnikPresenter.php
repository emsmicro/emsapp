<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;
/*
 * Stat presenter
 */

class CiselnikPresenter extends SpravaPresenter
{

	public function startup()
	{
		parent::startup();

	}


	/********************* view default *********************/

	/*
	 * @return void
	 */

	public function renderDefault()
	{

		$item = new Ciselnik;
		$this->template->tcen = $item->showCis('typy_cen');
		$this->template->dfirem = $item->showCis('druhy_firem');
		$this->template->doperaci = $item->showCis('druhy_operaci');
		$this->template->merjed = $item->showCis('merne_jednotky');
		$this->template->osloveni = $item->showCis('osloveni');
		$this->template->role = $item->showCis('role');
		$this->template->tnakladu = $item->showCis('typy_nakladu');
		$this->template->tsazeb = $item->showCis('typy_sazeb');
		$this->template->tkontaktu = $item->showCis('typy_kontaktu');
		$mysec = parent::getMySection();
	}

}
