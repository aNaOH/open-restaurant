<?php

$router = new \Bramus\Router\Router();

$router->before('GET|POST', '/admin', function() {
    if (!isset($_SESSION['admin'])) {
        header("Location: /login");
        exit;
    }

    $session = $_SESSION['admin'];
    if(!isset($session['username']) || !isset($session['password'])) {
        session_destroy();
        header("Location: /login");
        exit;
    }

    if($session['username'] != CONFIG->ADMIN_USER || $session['password'] != CONFIG->ADMIN_PASS) {
        session_destroy();
        header("Location: /login");
        exit;
    }
});

$router->get("/", function() {
    ViewController::render('index');
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

$router->get("/admin", function() {
    ViewController::render('admin/index');
});

$router->run();
