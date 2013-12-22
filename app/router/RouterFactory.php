<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();
		//$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
		$router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
		$router[] = new Route('<presenter>/<action>[/<id>][/<idp>]', 'Homepage:default');
		$router[] = new Route('<presenter>/<action>[/<id>][/<src>]', 'Homepage:default');
		$router[] = new Route('<presenter>/<action>[/<src>]', 'Homepage:default');
		$router[] = new Route('<presenter>/<action>[/<idp>]', 'Homepage:default');
		$router[] = new Route('<presenter>/<action>[/<id_firmy>][/<idp>]', 'Homepage:default');
		$router[] = new Route('<presenter>/<action>[/<iprod>][/<istav>][/<iuser>]', 'Homepage:default');
		
		return $router;
	}

}
