<?php

$router->mount('/categories', function() use ($router) {
    $router->get('/', function() {
        $categories = Category::getAll();
        ViewController::render('admin/categories/index', ['categories' => $categories]);
    });

    $router->get('/add', function() {
        ViewController::render('admin/categories/add');
    });

    $router->post('/add', function() {
        if (isset($_POST['name'])) {
            $category = new Category(null, $_POST['name']);
            $category->save();
            header("Location: /admin/categories");
            exit;
        } else {
            ViewController::render('admin/categories/add', ['error' => 'Introduce el nombre de la categoría.']);
        }
    });

    $router->get('/edit/{id}', function($id) {
        $category = Category::getById($id);
        if ($category) {
            ViewController::render('admin/categories/edit', ['category' => $category]);
        } else {
            header("Location: /admin/categories");
            exit;
        }
    });

    $router->post('/edit/{id}', function($id) {
        if (isset($_POST['name'])) {
            $category = Category::getById($id);
            if (!$category) {
                header("Location: /admin/categories");
                exit;
            }
            $category->name = $_POST['name'];
            $category->save();
            header("Location: /admin/categories");
            exit;
        } else {
            ViewController::render('admin/categories/edit', ['error' => 'Introduce el número de mesa.']);
        }
    });

    $router->get('/delete/{id}', function($id) {
        $category = Category::getById($id);
        if ($category) {
            Connection::doDelete(DBCONN, 'category', ['id' => $id]);
            header("Location: /admin/categories");
            exit;
        } else {
            ViewController::render('admin/categories/index', ['error' => 'Mesa no encontrada.']);
        }
    });
});