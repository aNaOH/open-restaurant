<?php

$router->mount('/tables', function() use ($router) {
    $router->get('/', function() {
        $tables = Table::getAll();
        ViewController::render('admin/tables/index', ['tables' => $tables]);
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

    $router->get('/edit/{id}', function($id) {
        $table = Table::getById($id);
        if ($table) {
            ViewController::render('admin/tables/edit', ['table' => $table]);
        } else {
            header("Location: /admin/tables");
            exit;
        }
    });

    $router->post('/edit/{id}', function($id) {
        if (isset($_POST['notes'])) {
            $table = Table::getById($id);
            if (!$table) {
                header("Location: /admin/tables");
                exit;
            }
            $table->notes = $_POST['notes'];
            $table->save();
            header("Location: /admin/tables");
            exit;
        } else {
            ViewController::render('admin/tables/edit', ['error' => 'Introduce el número de mesa.']);
        }
    });

    $router->get('/delete/{id}', function($id) {
        $table = Table::getById($id);
        if ($table) {
            Connection::doDelete(DBCONN, 'table', ['id' => $id]);
            header("Location: /admin/tables");
            exit;
        } else {
            ViewController::render('admin/tables/index', ['error' => 'Mesa no encontrada.']);
        }
    });
});