<?php

require_once 'models/User.php';

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

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'redirect' => '/config']);
        exit;
    } else {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No se pudo conectar a la base de datos.']);
        exit;
    }
});

$router->post("/admin", function() {
    $admin_username = $_POST['admin_username'];
    $admin_email = $_POST['admin_email'];
    $admin_password = $_POST['admin_password'];
    $admin_password_confirm = $_POST['admin_password_confirm'];

    if (empty($admin_username) || empty($admin_email) || empty($admin_password) || empty($admin_password_confirm)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Los datos son obligatorios.']);
        exit;
    }

    if ($admin_password !== $admin_password_confirm) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Las contraseñas no coinciden.']);
        exit;
    }
    // Validación de contraseña segura
    $passwordErrors = [];
    if (strlen($admin_password) < 8) {
        $passwordErrors[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $admin_password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos una letra mayúscula.';
    }
    if (!preg_match('/[a-z]/', $admin_password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos una letra minúscula.';
    }
    if (!preg_match('/[0-9]/', $admin_password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos un número.';
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $admin_password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos un carácter especial.';
    }
    if (!empty($passwordErrors)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => implode(' ', $passwordErrors)]);
        exit;
    }

    define('DBCONN', Connection::connectToDB(CONFIG->DB_HOST, CONFIG->DB_NAME, CONFIG->DB_USER, CONFIG->DB_PASS));

    $admin = new User(null, $admin_email, $admin_username, password_hash($admin_password, PASSWORD_BCRYPT), EUSER_ROLE::ADMIN);
    $admin->save();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => '/features']);
    exit;
});

$router->post("/config", function() {
    $restaurant_name = $_POST['restaurant_name'];
    $restaurant_address = $_POST['restaurant_address'];
    $restaurant_phone = $_POST['restaurant_phone'];
    $restaurant_email = isset($_POST['restaurant_email']) ? $_POST['restaurant_email'] : '';
    $restaurant_logo = $_FILES['restaurant_logo'];

    // Validate the logo file
    if ($restaurant_logo['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Error uploading logo.']);
        exit;
    }
    if ($restaurant_logo['size'] > 2000000) { // 2MB limit
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Logo file is too large.']);
        exit;
    }
    $allowed_types = ['image/jpeg', 'image/png'];
    if (!in_array($restaurant_logo['type'], $allowed_types)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid logo file type.']);
        exit;
    }

    $upload_dir = './content/';
    // Rename the file to logo to avoid conflicts
    $logo_path = $upload_dir . 'logo.' . pathinfo($restaurant_logo['name'], PATHINFO_EXTENSION);
    if (!move_uploaded_file($restaurant_logo['tmp_name'], $logo_path)) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error moving logo file.']);
        exit;
    }

    $config = new Config();
    $config->RESTAURANT_NAME = $restaurant_name;
    $config->RESTAURANT_ADDRESS = $restaurant_address;
    $config->RESTAURANT_PHONE = $restaurant_phone;
    $config->RESTAURANT_EMAIL = $restaurant_email;

    $config->save();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => '/admin']);
    exit;
});

$router->post("/features", function() {

    $discount_enabled = isset($_POST['codeEnabled']) ? true : false;
    $fidelity_enabled = isset($_POST['fidelityEnabled']) ? true : false;

    $config = new Config();
    $config->DISCOUNT_ENABLED = $discount_enabled;
    $config->FIDELITY_ENABLED = $fidelity_enabled;
    $config->POINTS_PER_UNIT = 100; // Default value
    $config->INSTALL_FINISHED = true;

    $config->save();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => '/login']);
    exit;
});

$router->run();