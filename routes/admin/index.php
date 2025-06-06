<?php
// Rutas del panel de administración principal y configuración
$router->before('GET|POST', '/admin', function() {
    // Verifica si el usuario es administrador
    if(!AuthHelpers::isAdmin()) {
        header('Location: /login');
        exit;
    }
});

$router->mount('/admin', function() use ($router) {
    // Panel principal de administración
    $router->get('/', function() {
        $tables = Table::getAll();
        ViewController::render('admin/index');
    });

    // Configuración general
    $router->get('/config', function() {
        global $config;
        // Obtener todas las zonas horarias disponibles
        $all_timezones = \DateTimeZone::listIdentifiers();
        $unique_timezones = [];
        $offsets = [];
        $now = new \DateTime('now');
        foreach ($all_timezones as $tz) {
            $timezone = new \DateTimeZone($tz);
            $offset = $timezone->getOffset($now);
            if (!isset($offsets[$offset])) {
                $offsets[$offset] = $tz;
                $unique_timezones[] = $tz;
            }
        }
        ViewController::render('admin/config', [
            'discount_enabled' => $config->DISCOUNT_ENABLED,
            'fidelity_enabled' => $config->FIDELITY_ENABLED,
            'points_per_unit' => $config->POINTS_PER_UNIT,
            'restaurant_name' => $config->RESTAURANT_NAME,
            'restaurant_address' => $config->RESTAURANT_ADDRESS,
            'restaurant_phone' => $config->RESTAURANT_PHONE,
            'restaurant_email' => $config->RESTAURANT_EMAIL,
            'stripe_public_key' => $config->STRIPE_PUBLIC_KEY,
            'stripe_secret_key' => $config->STRIPE_SECRET_KEY,
            'timezone' => $config->TIMEZONE,
            'timezones' => $unique_timezones
        ]);
    });
    
    // Guardar configuración de características
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

        if (!$loyaltyProgram) {
            // Elimina todos los usuarios que no sean administradores ni empleados
            // Asumimos que EUSER_ROLE::ADMIN = 0 y EUSER_ROLE::EMPLOYEE = 1
            Connection::doDelete(DBCONN, 'User', [
                'role' => [
                    'param' => 'role',
                    'value' => 0,
                    'operator' => '='
                ]
            ]);
        }
        if (!$loyaltyProgram && !$promoCodes) {
            // Elimina todos los productos promocionales
            // Asumimos que EPRODUCT_TYPE::PROMOTION = 2
            Connection::doDelete(DBCONN, 'Product', [
                'type' => 2
            ]);
        }
        // Redirigir o mostrar mensaje
        header('Location: /admin/config');
        exit;
    });
    
    // Guardar datos del restaurante
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
    
    // Guardar logo del restaurante
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


    // Guardar claves de Stripe
    $router->post('/config/stripe', function() {
        global $config;
        $config->STRIPE_PUBLIC_KEY = isset($_POST['stripe_public_key']) ? trim($_POST['stripe_public_key']) : '';
        $config->STRIPE_SECRET_KEY = isset($_POST['stripe_secret_key']) ? trim($_POST['stripe_secret_key']) : '';
        $config->save();
        header('Location: /admin/config');
        exit;
    });

    // Guardar zona horaria
    $router->post('/config/timezone', function() {
        global $config;
        $config->TIMEZONE = isset($_POST['timezone']) ? $_POST['timezone'] : 'Europe/Madrid';
        $config->save();
        header('Location: /admin/config');
        exit;
    });

    // Incluir submódulos de administración
    include_once 'routes/admin/tables.php';
    include_once 'routes/admin/categories.php';
    include_once 'routes/admin/products.php';
    include_once 'routes/admin/composed.php';
    include_once 'routes/admin/promotional.php';
    include_once 'routes/admin/users.php';
});