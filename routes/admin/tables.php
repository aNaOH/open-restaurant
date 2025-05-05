<?php

$router->mount('/tables', function() use ($router) {
    $router->get('/', function() {
        $tables = Table::getAll();
        ViewController::render('admin/tables/index', array_merge(SidebarHelpers::getBaseData(), [
            'tables' => $tables,
        ]));
    });

    $router->get('/add', function() {
        ViewController::render('admin/tables/add', SidebarHelpers::getBaseData());
    });

    $router->post('/add', function() {
        if (isset($_POST['id'])) {
            $table = new Table($_POST['id'], $_POST['notes']);
            $table->save();
            header("Location: /admin/tables");
            exit;
        } else {
            ViewController::render('admin/tables/add', array_merge(SidebarHelpers::getBaseData(), [
                'error' => 'Introduce el número de mesa.',
            ]));
        }
    });

    $router->get('/edit/{id}', function($id) {
        $table = Table::getById($id);
        if ($table) {
            ViewController::render('admin/tables/edit', array_merge(SidebarHelpers::getBaseData(), [
                'table' => $table,
            ]));
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
            ViewController::render('admin/tables/edit', array_merge(SidebarHelpers::getBaseData(), [
                'error' => 'Introduce el número de mesa.',
                'table' => Table::getById($id),
            ]));
        }
    });

    $router->get('/delete/{id}', function($id) {
        $table = Table::getById($id);
        if ($table) {
            Connection::doDelete(DBCONN, 'table', ['id' => $id]);
            header("Location: /admin/tables");
            exit;
        } else {
            ViewController::render('admin/tables/index', array_merge(SidebarHelpers::getBaseData(), [
                'error' => 'Mesa no encontrada.',
            ]));
        }
    });
});