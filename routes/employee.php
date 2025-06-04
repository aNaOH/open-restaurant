<?php

$router->before('GET|POST', '/employee', function() {
    // Check if it's the 'begin' route
    if (!AuthHelpers::isEmployee()) {
        header('Location: /login');
        exit;
    }
});

$router->mount('/employee', function() use ($router) {
    $router->get('/', function() {
        $tables = Table::getAll();
        $orders = Order::getAll();
        ViewController::render('employee/index', [
            'tables' => $tables,
            'orders' => $orders
        ]);
    });
});