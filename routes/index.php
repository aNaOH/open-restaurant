<?php

$router = new \Bramus\Router\Router();

$router->before('GET|POST', '/admin', function() {
    if (!isset($_SESSION['admin'])) {
        header("Location: /login");
        exit;
    }

    $session = $_SESSION['admin'];
    if(!isset($session['username']) || !isset($session['password'])) {
        header("Location: /login");
        exit;
    }

    if($session['username'] != CONFIG->ADMIN_USER || $session['password'] != CONFIG->ADMIN_PASS) {
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

$router->get("/admin", function() {
    ViewController::render('admin/index');
});

$router->run();
