<?php
// Rutas del panel de empleado
// Incluye autenticación de empleados y gestión de pedidos del día
$router->before('GET|POST', '/employee', function() {
    // Verifica si el usuario es empleado o admin
    if (!AuthHelpers::isEmployee()) {
        header('Location: /login');
        exit;
    }
});

$router->mount('/employee', function() use ($router) {
    // Panel principal de empleados
    $router->get('/', function() {
        $tables = Table::getAll();
        $orders = Order::getAll();
        
        ViewController::render('employee/index', [
            'tables' => $tables,
            'orders' => $orders
        ]);
    });

    // Ver detalles de un pedido específico
    $router->get('/{order_id}', function($order_id) {
        $order = Order::getById($order_id);

        if (!$order) {
            header('Location: /employee');
            exit;
        }

        // Comprueba si el pedido es de hoy
        if (date('Y-m-d', strtotime($order->date)) !== date('Y-m-d')) {
            header('Location: /employee');
            exit;
        }

        $products = $order->getProducts();
        $table = Table::getById($order->table_id);
        
        if(!$table) {
            header('Location: /employee');
            exit;
        }

        ViewController::render('employee/order', [
            'order' => $order,
            'products' => $products,
            'table' => $table
        ]);
    });

    // Cambiar el estado de un producto en un pedido
    $router->post('/{order_id}/change-product-status', function($order_id) {
        $order = Order::getById($order_id);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado.']);
            exit;
        }

        if (date('Y-m-d', strtotime($order->date)) !== date('Y-m-d')) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El pedido no es de hoy.']);
            exit;
        }

        if (isset($_POST['product_id']) && isset($_POST['status'])) {
            $product_id = $_POST['product_id'];
            $status = strtolower($_POST['status']) === 'true' ? 1 : 0; // Acepta 'true'/'false' como booleano

            $success = $order->updateProductStatus($product_id, $status);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'product_id' => $product_id,
                'status' => $status
            ]);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Datos incompletos para cambiar el estado del producto.']);
            exit;
        }
    });
});