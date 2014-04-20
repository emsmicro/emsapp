<?php


use Nette\Security\Permission;


class AuthorizatorFactory extends Nette\Object
{
	private $permission;
	
	/**
	 * 
	 * @return \Nette\Security\IAuthorizator
	 */
	public function create()
    {
        $permission = new Permission;

//		$zdroje		= array('Homepage'	=> array(	'Homepage'	=> array('default'),
//													'Sign'		=> array('in'),
//												),
//							'Obchod'	=> array(	'Obchod'	=> array('default','dash'),
//													'Firma'		=> array('default','detail','add','edit','delete','addContact','editContact','deleteContact','eraseSet'),
//													'Osoba'		=> array('default','people','detail','add','edit','delete','addContact','editContact','deleteContact'),
//													'Nabidka'	=> array('default','offer','detail','add','edit','delete','changeStatus'),
//													'Produkt'	=> array('default','product','detail','add','edit','delete','chngStatus','changeStatus','setProduct',
//																			'addAmount','editAmount','deleteAmount','costsUpd','priceUpd', 'assign', 'eraseOffer'),
//													'Kurz'		=> array('default','add','edit','delete'),
//													'SetSazeb'	=> array('default','detail','add','edit','delete','addRate','editRate','deleteRate'),
//													'Nakup'		=> array('default','material'),
//													'Material'	=> array('default','list','detail'),
//													'Tpv'		=> array('default','operace'),
//													'Operace'	=> array('default','detail'),
//												),
//							'Tpv'		=> array(	'Tpv'		=> array('default','operace'),
//													'Firma'		=> array('default','detail','eraseSet'),
//													'Operace'	=> array('default','detail','add','edit','delete','changeStatus','addGroup'),
//													'TypOperace'=> array('default','detail','add','edit','delete'),
//													'SetSazebO'	=> array('default','detail','add','edit','delete','addRate','editRate','deleteRate'),
//													'AtrCasu'	=> array('default','detail','add','edit','delete','addGroup'),
//													'Nabidka'	=> array('default','offer','detail'),
//													'Produkt'	=> array('default','product','detail','chngStatus','viewCosts'),
//													'Nakup'		=> array('default'),
//													'Material'	=> array('default','list','detail'),
//												),
//							'Nakup'		=> array(	'Nakup'		=> array('default','material'),
//													'Firma'		=> array('default','detail','eraseSet'),
//													'Material'	=> array('default','list','detail','add','edit','editr','delete','changeStatus'),
//													'Import'	=> array('default','check','confirm'),
//													'K2'		=> array('default','detail','find','prices','select','setPrice','setPriceValue'),
//													'Nabidka'	=> array('default','offer','detail'),
//													'Produkt'	=> array('default','product','detail','chngStatus','viewCosts'),
//												),
//							'Sprava'	=> array(	'Sprava'	=> array('default'),
//													'Uzivatel'	=> array('default','add','edit','delete'),
//													'Prava'		=> array('default'),
//													'Misto'		=> array('default','adds','edits','addk','editk','addo','edito'),
//                                                    'Ciselnik'	=> array('default'),
//													'Query'		=> array('default'),
//												)
//						);
				
        // roles
		$prava = new Prava;
		$roles = $prava->getRole();
		$permission = $this->setRolesDB($roles);
		$this->permission = $permission;
		
        // resources sets
		$zdroje = $prava->getResources();
		$this->setResourcesDB($zdroje);

		// privileges sets
//		$this->setPrivilegesDB('guest',	'Homepage');
//		$this->setPrivilegesDB('Nakup',	'Homepage');
//		$this->setPrivilegesDB('Tpv',	'Homepage');
//		$this->setPrivilegesDB('Sprava','Homepage');
//		$this->setPrivilegesDB('Obchod','Homepage');
//
//		$this->setPrivilegesDB('Nakup',	'Nakup');
//		$this->setPrivilegesDB('Tpv',	'Tpv');
//		$this->setPrivilegesDB('Sprava','Sprava');
//		$this->setPrivilegesDB('Obchod','Obchod');
		
		$this->setPrivilegesDB();
		
		$permission->allow('admin', Permission::ALL, Permission::ALL);
		//$permission->allow('Admin', Permission::ALL, Permission::ALL);

		$this->permission = $permission;
//		dump($permission);
//		exit;
		//dd($permission, "PERMISS");
		return $permission;
   }
   
   
   protected function setRolesDB($roles){
	   $perm = new Permission;
	   $perm->addRole('guest');
	   foreach ($roles as $role){
			$perm->addRole($role->role);
	   }
	   return $perm;
   }

	protected function setResourcesDB($res_modules){
		foreach ($res_modules as $it) {
				$presenter = $it->presenter;
				if (!$this->permission->hasResource($presenter)){
					$this->permission->addResource($presenter);
				}
				$action = $it->funkce;
				if (!$this->permission->hasResource($presenter .':'. $action)){
					$this->permission->addResource($presenter .':'. $action);
				}
		}
	}

	protected function setPrivilegesDB(){
		$this->permission->allow('guest', 'Homepage:default', Permission::ALL);
		$prava = new Prava;
		$res_modules = $prava->getPermissions();

		foreach ($res_modules as $it) {
			$role = $it->role;
			$module = $it->modul;
			if($module=='Base'){$module='Homepage';}
			$presenter = $it->presenter;
			$action = $it->funkce;

			$this->permission->allow($role, $presenter, $action);
			$this->permission->allow($role, $presenter .':'. $action, Permission::ALL);

		}
	}

   
	protected function setResources($res_modules){
		foreach ($res_modules as $module => $presenters) {
			foreach ($presenters as $presenter => $actions) {
					if (!$this->permission->hasResource($presenter)){
						$this->permission->addResource($presenter);
					}
					//echo '<br />'.$presenter.':';
					foreach ($actions as $i => $action) {
						//echo $action.', ';
						if (!$this->permission->hasResource($presenter .':'. $action)){
							$this->permission->addResource($presenter .':'. $action);
						}
					}
			}
		}
	}

	protected function setPrivileges($role, $res_modules, $section){
		foreach ($res_modules as $module => $presenters) {
			if ($module == $section){
				foreach ($presenters as $presenter => $actions) {
					foreach ($actions as $i => $action) {
						$this->permission->allow($role, $presenter, $action);
						$this->permission->allow($role, $presenter .':'. $action, Permission::ALL);
					}
				}
			}
		}
	}



	
}