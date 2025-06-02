<?php

include_once 'models/Table.php';
include_once 'models/Category.php';
include_once 'models/Product.php';
include_once 'models/User.php';

$router = new \Bramus\Router\Router();

$router->get("/", function() {
    $categories = Category::getAll();
    $products = Product::getAll();
    ViewController::render('index', array_merge(SidebarHelpers::getBaseData(), [
        'restaurantName' => CONFIG->RESTAURANT_NAME,
        'categories' => $categories,
        'products' => $products
    ]));
});

$router->get("/login", function() {
    if(AuthHelpers::isLoggedIn()) {
        header("Location: /");
        exit;
    }
    ViewController::render('login');
});

$router->post("/login", function() {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            ViewController::render('login', ['error' => 'Introduce usuario y contraseña.']);
            return;
        }

        $user = User::getByEmail($email);
        if ($user && password_verify($password, $user->password)) {
            $_SESSION['user'] = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name
            ];
            header("Location: /");
            exit;
        } else {
            ViewController::render('login', ['error' => 'Usuario o contraseña incorrectos.']);
        }
    } else {
        ViewController::render('login', ['error' => 'Introduce correo y contraseña.']);
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
