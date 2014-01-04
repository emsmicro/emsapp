<?php

// Uncomment this line if you must temporarily take down your site for maintenance.
// require '.maintenance.php';

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__));

// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/../app');

// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/../../lib');
define('UPL_DIR', WWW_DIR . '/../uploads');

// Let bootstrap create Dependency Injection container.
// load bootstrap file
$container = require APP_DIR . '/bootstrap.php';

// Run application.
$container->application->run();
