<?php

$router = new \Bramus\Router\Router();

$router->get("/", function() {
    ViewController::render('wizard/index');
});

$router->get("/db", function() {
    ViewController::render('wizard/db');
});

$router->post("/db", function() {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];

    $config = new Config();

    $config->DB_HOST = $db_host;
    $config->DB_NAME = $db_name;
    $config->DB_USER = $db_user;
    $config->DB_PASS = $db_pass;

    $config->INSTALL_FINISHED = false;

    $config->save();

    header("Location: /config");
    exit;
});

$router->get("/config", function() {
    ViewController::render('wizard/config');
});

$router->run();