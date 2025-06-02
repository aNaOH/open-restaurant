<?php

include_once 'models/Table.php';
include_once 'models/Category.php';
include_once 'models/Product.php';

$router = new \Bramus\Router\Router();

$router->get("/", function() {
    $categories = Category::getAll();
    $products = Product::getAll();
    ViewController::render('index', [
        'restaurantName' => CONFIG->RESTAURANT_NAME,
        'showLogin' => CONFIG->FIDELITY_ENABLED,
        'categories' => $categories,
        'products' => $products
    ]);
});

$router->get("/login", function() {
    if (isset($_SESSION['admin'])) {
        header("Location: /admin");
        exit;
    }
    ViewController::render('login');
});

$router->post("/login", function() {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        if ($_POST['username'] == CONFIG->ADMIN_USER && password_verify($_POST['password'], CONFIG->ADMIN_PASS)) {
            $_SESSION['admin'] = [
                'username' => CONFIG->ADMIN_USER,
                'password' => CONFIG->ADMIN_PASS,
            ];
            header("Location: /admin");
            exit;
        } else {
            ViewController::render('login', ['error' => 'Usuario o contraseña incorrecto.']);
        }
    } else {
        ViewController::render('login', ['error' => 'Introduce usuario y contraseña.']);
    }
});

include_once 'routes/admin/index.php';
include_once 'routes/content/index.php';

$router->run();
