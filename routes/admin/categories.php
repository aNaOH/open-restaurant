<?php

$router->mount('/categories', function() use ($router) {
    $router->get('/', function() {
        $categories = Category::getAll();
        ViewController::render('admin/categories/index', array_merge(SidebarHelpers::getBaseData(), ['categories' => $categories]));
    });

    $router->get('/add', function() {
        ViewController::render('admin/categories/add', SidebarHelpers::getBaseData());
    });

    $router->post('/add', function() {

        var_dump($_FILES);

        exit;

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
            ViewController::render('admin/categories/edit', array_merge(SidebarHelpers::getBaseData(), ['category' => $category]));
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
            ViewController::render('admin/categories/edit', array_merge(SidebarHelpers::getBaseData(), ['error' => 'Introduce el nombre de la categoría.']));
        }
    });

    $router->get('/delete/{id}', function($id) {
        $category = Category::getById($id);
        if ($category) {
            Connection::doDelete(DBCONN, 'category', ['id' => $id]);
            header("Location: /admin/categories");
            exit;
        } else {
            ViewController::render('admin/categories/index', array_merge(SidebarHelpers::getBaseData(), ['error' => 'Categoría no encontrada.']));
        }
    });
});