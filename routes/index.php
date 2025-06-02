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

$router->get('/logo', function() {
        //Search for the logo in the content folder, it can be a .png or .jpg file
        //if found, return it with the correct MIME, if not, return 404
        $logo = glob('content/logo.{jpg,png}', GLOB_BRACE);
        if (count($logo) > 0) {
            $logo = $logo[0];
            $ext = pathinfo($logo, PATHINFO_EXTENSION);
            header('Content-Type: image/' . $ext);
            readfile($logo);
        } else {
            header("HTTP/1.0 404 Not Found");
            exit;
        }
    });

include_once 'routes/admin/index.php';

$router->run();
