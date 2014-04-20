<?php

use Nette\Application\UI\Form,
	Nette\Security as NS;



class SignPresenter extends BasePresenter
{
	/** @persistent */
	public $backlink = '';



	/**
	 * Sign in form component factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = new Form;
		$form->addText('username', '')
			->setRequired('Prosím, zadejte název uživatele.')
			->setAttribute('placeholder', 'Uživatel');	

		$form->addPassword('password', '')
			->setRequired('Prosím zadejte heslo.')
			->setAttribute('placeholder', 'Heslo');	

		$form->addSubmit('send', 'Login');

		$form->onSuccess[] = callback($this, 'signInFormSubmitted');
		return $form;
	}



	public function signInFormSubmitted($form)
	{
		try {
			$values = $form->values;
			$this->user->login($values->username, $values->password);
			$this->getPresenter()->restoreRequest($this->backlink);
			$this->getPresenter()->restoreRequest($this->getPresenter()->backlink);
			$module = $this->user->getRoles();
			$bmodule = $module[0];
			if(in_array($bmodule, array('guest','admin'))){
				$this->getPresenter()->redirect("Homepage:");
			} else {
				$this->getPresenter()->redirect("$bmodule:");
			}


			//$this->redirect('Homepage:');

		} catch (NS\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}



	public function actionOut()
	{
		$this->getUser()->logout();
		$this->eraseMySet();
		$this->flashMessage('Uživatel byl odhlášen.');
		$this->redirect('in');
	}

}
