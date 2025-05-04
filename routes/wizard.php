<?php

$router = new \Bramus\Router\Router();

$router->get("/", function() {
    ViewController::render('wizard/index');
});

$router->get("/db", function() {
    ViewController::render('wizard/db');
});

$router->get("/admin", function() {
    ViewController::render('wizard/admin');
});

$router->get("/config", function() {
    ViewController::render('wizard/config');
});

$router->get("/features", function() {
    ViewController::render('wizard/features');
});

$router->post("/db", function() {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';

    $conn = Connection::createDB($db_host, $db_name, $db_user, $db_pass);

    if($conn) {

        Connection::executeSqlScript($conn, './openRestaurant.sql');

        $config = new Config();

        $config->DB_HOST = $db_host;
        $config->DB_NAME = $db_name;
        $config->DB_USER = $db_user;
        $config->DB_PASS = $db_pass;

        $config->INSTALL_FINISHED = false;

        $config->save();

        header("Location: /admin");
        exit;
    } else {
        ViewController::render('wizard/db', ['error' => 'No se pudo conectar a la base de datos.']);
    }
    exit;
});

$router->post("/admin", function() {
    $admin_username = $_POST['admin_username'];
    $admin_email = $_POST['admin_email'];
    $admin_password = $_POST['admin_password'];
    $admin_password_confirm = $_POST['admin_password_confirm'];

    if (empty($admin_username) || empty($admin_email) || empty($admin_password) || empty($admin_password_confirm)) {
        ViewController::render('wizard/admin', ['error' => 'Los datos son obligatorios.']);
        exit;
    }

    if ($admin_password !== $admin_password_confirm) {
        ViewController::render('wizard/admin', ['error' => 'Las contraseñas no coinciden.']);
        exit;
    }

    $config = new Config();
    $config->ADMIN_USER = $admin_username;
    $config->ADMIN_EMAIL = $admin_email;
    $config->ADMIN_PASS = password_hash($admin_password, PASSWORD_BCRYPT);
    $config->save();

    header("Location: /config");
    exit;
});

$router->post("/config", function() {
    $restaurant_name = $_POST['restaurant_name'];
    $restaurant_address = $_POST['restaurant_address'];
    $restaurant_phone = $_POST['restaurant_phone'];
    $restaurant_email = $_POST['restaurant_email'];
    $restaurant_logo = $_FILES['restaurant_logo'];

    // Validate the logo file
    if ($restaurant_logo['error'] !== UPLOAD_ERR_OK) {
        ViewController::render('wizard/config', ['error' => 'Error uploading logo.']);
        exit;
    }
    if ($restaurant_logo['size'] > 2000000) { // 2MB limit
        ViewController::render('wizard/config', ['error' => 'Logo file is too large.']);
        exit;
    }
    $allowed_types = ['image/jpeg', 'image/png'];
    if (!in_array($restaurant_logo['type'], $allowed_types)) {
        ViewController::render('wizard/config', ['error' => 'Invalid logo file type.']);
        exit;
    }

    $upload_dir = './content/';
    // Rename the file to logo to avoid conflicts
    $logo_path = $upload_dir . 'logo.' . pathinfo($restaurant_logo['name'], PATHINFO_EXTENSION);
    if (!move_uploaded_file($restaurant_logo['tmp_name'], $logo_path)) {
        ViewController::render('wizard/config', ['error' => 'Error moving logo file.']);
        exit;
    }

    $config = new Config();
    $config->RESTAURANT_NAME = $restaurant_name;
    $config->RESTAURANT_ADDRESS = $restaurant_address;
    $config->RESTAURANT_PHONE = $restaurant_phone;
    $config->RESTAURANT_EMAIL = $restaurant_email;

    $config->save();

    header("Location: /features");
    exit;
});

$router->post("/features", function() {

    $discount_enabled = isset($_POST['promo_codes']) ? true : false;
    $fidelity_enabled = isset($_POST['loyalty_program']) ? true : false;

    $config = new Config();
    $config->DISCOUNT_ENABLED = $discount_enabled;
    $config->FIDELITY_ENABLED = $fidelity_enabled;
    $config->POINTS_PER_UNIT = 100; // Default value
    $config->INSTALL_FINISHED = true;

    $config->save();

    header("Location: /");
    exit;
});

$router->run();