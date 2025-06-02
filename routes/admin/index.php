<?php

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

$router->mount('/admin', function() use ($router) {
    $router->get('/', function() {
        $tables = Table::getAll();
        ViewController::render('admin/index', SidebarHelpers::getBaseData());
    });

    $router->get('/config', function() {
        global $config;
        ViewController::render('admin/config', array_merge(SidebarHelpers::getBaseData(), [
            'discount_enabled' => $config->DISCOUNT_ENABLED,
            'fidelity_enabled' => $config->FIDELITY_ENABLED,
            'points_per_unit' => $config->POINTS_PER_UNIT,
            'restaurant_name' => $config->RESTAURANT_NAME,
            'restaurant_address' => $config->RESTAURANT_ADDRESS,
            'restaurant_phone' => $config->RESTAURANT_PHONE,
            'restaurant_email' => $config->RESTAURANT_EMAIL
        ]));
    });
    
    $router->post('/config/features', function() {
        global $config;
        // Leer valores del formulario
        $promoCodes = isset($_POST['promo_codes']) ? true : false;
        $loyaltyProgram = isset($_POST['loyalty_program']) ? true : false;
        $pointsPerUnit = isset($_POST['points_per_unit']) ? intval($_POST['points_per_unit']) : 100;
        $config->DISCOUNT_ENABLED = $promoCodes;
        $config->FIDELITY_ENABLED = $loyaltyProgram;
        $config->POINTS_PER_UNIT = $pointsPerUnit;
        $config->save();
        // Redirigir o mostrar mensaje
        header('Location: /admin/config');
        exit;
    });
    
    $router->post('/config/restaurant', function() {
        global $config;
        $config->RESTAURANT_NAME = isset($_POST['restaurant_name']) ? $_POST['restaurant_name'] : '';
        $config->RESTAURANT_ADDRESS = isset($_POST['restaurant_address']) ? $_POST['restaurant_address'] : '';
        $config->RESTAURANT_PHONE = isset($_POST['restaurant_phone']) ? $_POST['restaurant_phone'] : '';
        $config->RESTAURANT_EMAIL = isset($_POST['restaurant_email']) ? $_POST['restaurant_email'] : '';
        $config->save();
        header('Location: /admin/config');
        exit;
    });
    
    $router->post('/config/logo', function() {
        $success = false;
        $error = null;

        if (isset($_FILES['restaurant_logo']) && $_FILES['restaurant_logo']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'content/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            //eliminar archivos antiguos si existen
            foreach (glob($uploadDir . 'logo.*') as $file) {
                @unlink($file);
            }
            $extension = pathinfo($_FILES['restaurant_logo']['name'], PATHINFO_EXTENSION);
            $uploadFile = $uploadDir . 'logo.' . $extension;
            if (move_uploaded_file($_FILES['restaurant_logo']['tmp_name'], $uploadFile)) {
                // Opcional: eliminar otros logos antiguos
                foreach (glob($uploadDir . 'logo.*') as $file) {
                    if ($file !== $uploadFile) @unlink($file);
                }
                $success = true;
            } else {
                $error = 'No se pudo mover el archivo subido.';
            }
        } else {
            $error = 'No se recibió ningún archivo válido.';
        }
        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Logo subido correctamente.']);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $error ?: 'Error desconocido.']);
        }
        exit;
    });
    
    include_once 'routes/admin/tables.php';
    include_once 'routes/admin/categories.php';
    include_once 'routes/admin/products.php';
    include_once 'routes/admin/composed.php';
    include_once 'routes/admin/promotional.php';
});