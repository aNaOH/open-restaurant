<?php

include_once 'models/Table.php';
include_once 'models/Category.php';
include_once 'models/Product.php';
include_once 'models/User.php';

$router = new \Bramus\Router\Router();

$router->get("/", function() {
    $categories = Category::getAll();
    // Remove from categories those that have no products
    $categories = array_filter($categories, function($category) {
        return count(Product::getByCategory($category->id)) > 0;
    });
    ViewController::render('index', array_merge(SidebarHelpers::getBaseData(), [
        'restaurantName' => CONFIG->RESTAURANT_NAME,
        'categories' => $categories
    ]));
});

$router->get("/login", function() {
    if(AuthHelpers::isLoggedIn()) {
        header("Location: /");
        exit;
    }
    ViewController::render('auth/login', array_merge(SidebarHelpers::getBaseData(), [
        'restaurantName' => CONFIG->RESTAURANT_NAME
    ]));
});

$router->get("/register", function() {
    if(AuthHelpers::isLoggedIn()) {
        header("Location: /");
        exit;
    }
    ViewController::render('auth/register', array_merge(SidebarHelpers::getBaseData(), [
        'restaurantName' => CONFIG->RESTAURANT_NAME
    ]));
});

$router->get("/logout", function() {
    if(AuthHelpers::isLoggedIn()) {
        session_destroy();
    }
    header("Location: /");
    exit;
});

$router->post("/login", function() {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            ViewController::render('auth/login', array_merge(SidebarHelpers::getBaseData(), [
                'error' => 'Por favor, completa todos los campos.',
                'restaurantName' => CONFIG->RESTAURANT_NAME
            ]));
            return;
        }

        $user = User::getByEmail($email);
        if ($user && password_verify($password, $user->password)) {
            $_SESSION['user'] = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'points' => $user->points,
                'role' => $user->role
            ];
            header("Location: /");
            exit;
        } else {
            ViewController::render('auth/login', array_merge(SidebarHelpers::getBaseData(), [
                'error' => 'Usuario o contraseña incorrectos.',
                'restaurantName' => CONFIG->RESTAURANT_NAME
            ]));
        }
    } else {
        ViewController::render('auth/login', array_merge(SidebarHelpers::getBaseData(), [
            'error' => 'Por favor, completa todos los campos.',
            'restaurantName' => CONFIG->RESTAURANT_NAME
        ]));
    }
});

$router->post("/register", function() {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        ViewController::render('auth/register', array_merge(SidebarHelpers::getBaseData(), [
            'error' => 'Por favor, completa todos los campos.',
            'restaurantName' => CONFIG->RESTAURANT_NAME
        ]));
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ViewController::render('auth/register', array_merge(SidebarHelpers::getBaseData(), [
            'error' => 'El correo electrónico no es válido.',
            'restaurantName' => CONFIG->RESTAURANT_NAME
        ]));
        return;
    }
    if ($password !== $confirm_password) {
        ViewController::render('auth/register', array_merge(SidebarHelpers::getBaseData(), [
            'error' => 'Las contraseñas no coinciden.',
            'restaurantName' => CONFIG->RESTAURANT_NAME
        ]));
        return;
    }
    // Validación de contraseña segura
    $passwordErrors = [];
    if (strlen($password) < 8) {
        $passwordErrors[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos una letra mayúscula.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos una letra minúscula.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos un número.';
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos un carácter especial.';
    }
    if (!empty($passwordErrors)) {
        ViewController::render('auth/register', array_merge(SidebarHelpers::getBaseData(), [
            'error' => implode(' ', $passwordErrors),
            'restaurantName' => CONFIG->RESTAURANT_NAME
        ]));
        return;
    }
    if (User::getByEmail($email)) {
        ViewController::render('auth/register', array_merge(SidebarHelpers::getBaseData(), [
            'error' => 'El correo electrónico ya está registrado.',
            'restaurantName' => CONFIG->RESTAURANT_NAME
        ]));
        return;
    }
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $user = new User(null, $email, $name, $hashedPassword, EUSER_ROLE::USER);
    if ($user) {
        $user->save();
        header("Location: /");
        exit;
    } else {
        ViewController::render('auth/register', array_merge(SidebarHelpers::getBaseData(), [
            'error' => 'No se pudo crear la cuenta. Intenta de nuevo.',
            'restaurantName' => CONFIG->RESTAURANT_NAME
        ]));
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
