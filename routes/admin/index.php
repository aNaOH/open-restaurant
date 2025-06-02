<?php

$router->before('GET|POST', '/admin', function() {
    if (!isset($_SESSION['admin'])) {
        header("Location: /login");
        exit;
    }

    $session = $_SESSION['admin'];
    if(!isset($session['username']) || !isset($session['password'])) {
        session_destroy();
        header("Location: /login");
        exit;
    }

    if($session['username'] != CONFIG->ADMIN_USER || $session['password'] != CONFIG->ADMIN_PASS) {
        session_destroy();
        header("Location: /login");
        exit;
    }
});

$router->mount('/admin', function() use ($router) {
    $router->get('/', function() {
        $tables = Table::getAll();
        ViewController::render('admin/index', SidebarHelpers::getBaseData());
    });

    $router->get('/config', function() {
        ViewController::render('admin/config', SidebarHelpers::getBaseData());
    });
    
    include_once 'routes/admin/tables.php';
    include_once 'routes/admin/categories.php';
    include_once 'routes/admin/products.php';
});