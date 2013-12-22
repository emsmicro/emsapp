<?php

use Nette\Diagnostics\Debugger,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\SimpleRouter,
	NEtte\Config\Configurator;



// Load Nette Framework
require $params['libsDir'] .'/Nette/loader.php';
// Load Dibi library
require $params['libsDir'] .'/Dibi/Dibi.php';

// Enable Nette Debugger for error visualisation & logging
Debugger::$strictMode = TRUE;
Debugger::$logDirectory = $params['logDir'] ;

//Debugger::enable(Debugger::PRODUCTION);
Debugger::enable(Debugger::DEVELOPMENT);

// Load configuration from config.neon file
$configurator = new Configurator;
$configurator->container->params += $params;
$configurator->container->params['tempDir'] = __DIR__ . '/../temp';
$container = $configurator->loadConfig(__DIR__ . '/config.neon');
$container = $configurator->loadConfig(__DIR__ . '/config.local.neon');

// Setup router using mod_rewrite detection

if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
	$container->router = new RouteList;
	$container->router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);
	$container->router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
	$container->router[] = new Route('<presenter>/<action>[/<id>][/<idp>]', 'Homepage:default');
	$container->router[] = new Route('<presenter>/<action>[/<id>][/<src>]', 'Homepage:default');
	$container->router[] = new Route('<presenter>/<action>[/<src>]', 'Homepage:default');
	$container->router[] = new Route('<presenter>/<action>[/<idp>]', 'Homepage:default');
	$container->router[] = new Route('<presenter>/<action>[/<id_firmy>][/<idp>]', 'Homepage:default');
	$container->router[] = new Route('<presenter>/<action>[/<iprod>][/<istav>][/<iuser>]', 'Homepage:default');

} else {
	$container->router = new SimpleRouter('Homepage:default');
}
 
dibi::connect($container->params['database']);

//dibi::getProfiler()->setFile($params['logDir'] .'\log.sql');

$application = $container->application;
$application->errorPresenter = 'Error';

//DateInput register
Vodacek\Forms\Controls\DateInput::register();


// Run the application!
$application->run();

/**
 * Dumpování proměnných do DebugBaru
 * @param type $var
 * @param string $title
 * @return type 
 */
function dd($var, $title='')
{
        $backtrace = debug_backtrace();
        $source = (isset($backtrace[1]['class'])) ?
                $backtrace[1]['class'] :
                basename($backtrace[0]['file']);
        $line = $backtrace[0]['line'];
        if($title !== '')
                $title .= ' – ';
        return Nette\Diagnostics\Debugger::barDump($var, $title . $source . ' (' . $line .')');
}