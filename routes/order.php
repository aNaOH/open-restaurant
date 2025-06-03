<?php

// create a before event to check if an order exists except for the begin route
$router->before('GET|POST', '/order', function() {
    // Check if it's the 'begin' route
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($currentPath !== '/order/begin') {
        // Check if an order exists
        if (null == OrderHelpers::getOrder()) {
            // Redirect to home
            header('Location: /');
            exit;
        }
    }
});

$router->mount('/order', function() use ($router) {
    $router->get('/', function() {
        // Get the current order
        $order = OrderHelpers::getOrder();
        if ($order === null) {
            // Redirect to home if no order exists
            header('Location: /');
            exit;
        }
        // Asegura que la clave 'promos' siempre exista y sea array asociativo
        if (!isset($order['promos']) || !is_array($order['promos'])) {
            $order['promos'] = [];
            $_SESSION['order']['promos'] = [];
        }
        $categories = Category::getAll();
        // Remove from categories those that have no products
        $categories = array_filter($categories, function($category) {
            return count(Product::getByCategory($category->id)) > 0;
        });

        // Decode promo product snapshots before passing to Twig
        if (isset($order['promos']) && is_array($order['promos'])) {
            foreach ($order['promos'] as $code => $promoJson) {
                if (is_string($promoJson)) {
                    $decoded = json_decode($promoJson, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $order['promos'][$code] = $decoded;
                    } else {
                        unset($order['promos'][$code]); // Remove invalid JSON
                    }
                }
            }
        }

        // Render the order page with the current order
        ViewController::render('order/index', [
            'order' => $order,
            'categories' => $categories
        ]);
    });

    $router->get('/product/{id}', function($id) {
        // Get the current order
        $order = OrderHelpers::getOrder();
        if ($order === null) {
            // Redirect to home if no order exists
            header('Location: /');
            exit;
        }

        $product = Product::getById($id);
        if ($product === null) {
            // Redirect to order page if product does not exist
            header('Location: /order');
            exit;
        }

        if($product->type == EPRODUCT_TYPE::PROMOTION) {
            // If the product is a promotion, check if it has already been added to the order
            if (OrderHelpers::hasProductInOrder($product->id)) {
                // Redirect to order page if the product is already in the order
                header('Location: /order');
                exit;
            }

            // Check if the product is only promo code based
            if (!empty($product->code) && is_null($product->points)) {
                // Check if the promo code is already applied
                if (!OrderHelpers::hasPromoInOrder($product->code)) {
                    // Redirect to order page if the promo code is already applied
                    header('Location: /order');
                    exit;
                }

                $promo = $product->code;
            }
        }

        // Render the order page with the current order
        ViewController::render('order/product', [
            'order' => $order,
            'product' => $product,
            'promo' => $promo ?? null
        ]);
    });

    $router->post('/begin', function() {
        //Check if an order already exists
        if (OrderHelpers::getOrder() !== null) {
            // Redirect to the order page if an order already exists
            header('Location: /order');
            exit;
        }

        // Check if the table is provided
        if (!isset($_POST['table']) || empty($_POST['table'])) {
            header('Location: /');
            exit;
        }

        // Check if the table is valid (e.g., not empty, exists in the database, etc.)
        if (empty(trim($_POST['table']))) {
            // Redirect to home if the table is invalid
            header('Location: /');
            exit;
        }
        $table = Table::getById($_POST['table']);

        if ($table === null) {
            // Redirect to home if the table does not exist
            header('Location: /');
            exit;
        }
        

        // Begin a new order with the provided table
        OrderHelpers::beginOrder($table->id);
        // Redirect to the order page
        header('Location: /order');
        exit;
    });

    $router->get('/begin/{tableId}', function($tableId) {
        //Check if an order already exists
        if (OrderHelpers::getOrder() !== null) {
            // Redirect to the order page if an order already exists
            header('Location: /order');
            exit;
        }
        $table = Table::getById($tableId);

        if ($table === null) {
            // Redirect to home if the table does not exist
            header('Location: /');
            exit;
        }
        

        // Begin a new order with the provided table
        OrderHelpers::beginOrder($table->id);
        // Redirect to the order page
        header('Location: /order');
        exit;
    });

    $router->get("/stop", function() {
        // Stop the current order
        OrderHelpers::clearOrder();
        // Redirect to home
        header('Location: /');
        exit;
    });

    $router->get('/product-by-code/{code}', function($code) {
        // Find a promotional product by code
        $product = Product::getByPromoCode($code);
        if ($product && $product->type == EPRODUCT_TYPE::PROMOTION) {
            echo json_encode(['productId' => $product->id]);
        } else {
            echo json_encode(['error' => 'Código no válido o expirado.']);
        }
        exit;
    });

    $router->post('/apply-promo', function() {
        $input = json_decode(file_get_contents('php://input'), true);
        $code = isset($input['code']) ? trim($input['code']) : '';
        // No permitir aplicar el mismo código dos veces en el mismo pedido
        if (!OrderHelpers::addPromoToOrder($code)) {
            echo json_encode(['status' => 'error', 'message' => 'Este código ya ha sido aplicado a este pedido.']);
            exit;
        }
        $product = Product::getByPromoCode($code);
        if ($product && $product->type == EPRODUCT_TYPE::PROMOTION) {
            echo json_encode(['status' => 'ok', 'productId' => $product->id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Código no válido o expirado.']);
        }
        exit;
    });

    $router->post('/cancel-promo', function() {
        $input = json_decode(file_get_contents('php://input'), true);
        $code = isset($input['code']) ? trim($input['code']) : '';
        if (!$code) {
            echo json_encode(['status' => 'error', 'message' => 'Código no proporcionado.']);
            exit;
        }
        if (!OrderHelpers::removePromoFromOrder($code)) {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo quitar el código.']);
            exit;
        }
        echo json_encode(['status' => 'ok']);
        exit;
    });
});