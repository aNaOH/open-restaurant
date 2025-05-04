<?php

include_once 'vendor/autoload.php';

include_once 'globals/config.php';
include_once 'globals/connection.php';

include_once 'controllers/ViewController.php';

define('CONFIG', new Config());

$is_installed = CONFIG->isInstalled();
if (!$is_installed) {
    include_once 'routes/wizard.php';
    exit;
}

define('DBCONN', new Connection(CONFIG->DB_HOST, CONFIG->DB_NAME, CONFIG->DB_USER, CONFIG->DB_PASS));

session_start();

include_once 'routes/index.php';
