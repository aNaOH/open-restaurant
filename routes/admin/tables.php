<?php

$router->mount('/tables', function() use ($router) {
    $router->get('/', function() {
        ViewController::render('admin/tables/index');
    });

    $router->get('/add', function() {
        ViewController::render('admin/tables/add');
    });

    $router->post('/add', function() {
        if (isset($_POST['id'])) {
            $table = new Table($_POST['id'], $_POST['notes']);
            $table->save();
            header("Location: /admin/tables");
            exit;
        } else {
            ViewController::render('admin/tables/add', ['error' => 'Introduce el número de mesa.']);
        }
    });
});