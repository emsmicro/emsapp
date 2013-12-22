<?php


use	Nette\Http\User;

abstract class SecuredPresenter extends BasePresenter
{
	/** Status constants */
	const stBASED		= 1;
	const stINPROGRESS	= 2;
	const stWAIT4PRICES = 3;
	const stTPVSTARTED	= 4;
	const stTPVFINISHED = 5;
	const stMATERPRICES = 6;
	const stPRODPRICES	= 7;
	const stOFFERSENDED = 8;
	const stOFFERCHANGE = 9;
	const stSENDEDAGAIN = 10;
	const stACCEPTED	= 11;
	const stREFUSED		= 12;
	/**
	 * Pole akcí pro práci s MySetting .. právo má každý
	 * @var type 
	 */
	private $setting_actions = array(	'setCompany', 
										'setContact', 
										'setOffer', 
										'setProduct', 
										'setEraseSet'
									);
	public $user;
	
	public function startup()
	{
		parent::startup();
        $this->user = $this->getUser();
		$this->hasPermissions($this->name, $this->action);
	}

	public function hasPermissions($presenter, $action)
	{

        if (!$this->user->isLoggedIn()) {
            if ($this->user->getLogoutReason() === Nette\Http\User::INACTIVITY) {
				$this->flashMessage('Uplynula doba neaktivity! Systém vás z bezpečnostných důvodů odhlásil.', 'warning');
            }

            $backlink = $this->getApplication()->storeRequest();
            $this->redirect('Sign:in', array('backlink' => $backlink));
			return false;

        } else {
			$in_list = in_array($action, $this->setting_actions);
			$is_guest = in_array('guest', $this->user->getRoles());
			if (!$is_guest && $in_list) {return true;} //práva všem na mySetting kromě guesta
			if (!$this->user->isAllowed($presenter, $action)) {
				if($action == 'default') {
					$sekce = "Na vstup do sekce » " . ucfirst($presenter) . " «" ;
				} else {
					$sekce = "Na provedení akce » " . ucfirst($action) . " «";
				}
				$this->flashMessage($sekce .' nemáte dostatečná oprávnění!', 'warning');
				//navrat na page, ze ktere byla akce volána
				$this->goBack();
				return false;
            } else {
				return true;
			}
        }
	}


	public function testPermission($presenter, $action)
	{
	        $user = $this->getUser();
			$role = $user->getRoles();
			if (!$user->isAllowed($presenter, $action)) {
				$this->flashMessage('Na vstup do této sekce nemáte dostatečná oprávnění!', 'warning');
				return false;
            } else {
				return true;
			}
	}

	public function actionSetCompany($id=0)
	{
		//$this->redirect('Firma:');
		if($id==0){
			$this->redirect('Firma:');
		} else {
			$this->setIntoMySet(1, $id);
			$this->redirect('Nabidka:offer',$id);
		}
	}

	public function actionSetContact($id)
	{
		$this->setIntoMySet(2, $id);
		$this->redirect('Nabidka:offer',$id);
	}
	
	public function actionSetOffer($id)
	{
		$this->setIntoMySet(3, $id);
		$this->redirect('Produkt:product',$id);
	}

	public function actionSetProduct($id)
	{
		$this->setIntoMySet(4, $id);
		if(in_array('Nakup', $this->user->getRoles())){
			$this->redirect('Material:');
		}
		if(in_array('Tpv', $this->user->getRoles())){
			$this->redirect('Operace:');
		}
		if(in_array('Sprava', $this->user->getRoles())){
			$this->redirect('Sprava:');
		}
		$this->redirect('Produkt:detail',$id);
	}

	/**
	 * Komponenta uživatelského filtru
	 * @return type
	 */
	protected function createComponentUFilter()
	{

		$control = new FilterControl();
		$control->setPresenter($this->getPresenter()->name);
		$control->setRender($this->getPresenter()->action);
		$control->setUser($this->user->getIdentity()->id);
		$gfil = $control->getFilter();
		return $control;
	}	
	
	
	/**
	 * Factory component paginator
	 * @param type $name
	 * @return \VisualPaginator\VisualPaginator 
	 */
	protected function createComponentVp($name) {
//		$vp = new \VisualPaginator\VisualPaginator($this, $name);
		return new \VisualPaginator\VisualPaginator($this, $name);
	}	
	
		
}
