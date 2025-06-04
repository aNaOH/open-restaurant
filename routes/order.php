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

    $router->get('/cart', function() {
        // Get the current order
        $order = OrderHelpers::getOrder();
        if ($order === null) {
            // Redirect to home if no order exists
            header('Location: /');
            exit;
        }
        // Calcular total_dinero y total_puntos en el backend
        $total_dinero = 0;
        $total_puntos = 0;
        if (isset($order['items']) && is_array($order['items'])) {
            foreach ($order['items'] as $item) {
                $price = isset($item['product_snapshot']['price']) ? $item['product_snapshot']['price'] : 0;
                $points = isset($item['product_snapshot']['points']) ? $item['product_snapshot']['points'] : 0;
                $total_dinero += $price * $item['quantity'];
                $total_puntos += $points * $item['quantity'];
            }
        }
        // Determinar si se debe mostrar Stripe (solo si total_dinero > 0)
        $show_stripe = $total_dinero > 0;
        // Render the cart page with the current order and totals

        ViewController::render('order/cart', [
            'order' => $order,
            'stripe_public_key' => CONFIG->STRIPE_PUBLIC_KEY,
            'total_dinero' => $total_dinero,
            'total_puntos' => $total_puntos,
            'show_stripe' => $show_stripe,
            'points_per_unit' => defined('CONFIG->POINTS_PER_UNIT') ? CONFIG->POINTS_PER_UNIT : 100,
            // Siempre obtener el usuario actualizado de la base de datos
            'user' => (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) ? User::getById($_SESSION['user']['id']) : null
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
        // Permitir recibir el código tanto por JSON como por POST tradicional
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $code = null;
        if ($data && isset($data['code'])) {
            $code = trim($data['code']);
        } elseif (isset($_POST['code'])) {
            $code = trim($_POST['code']);
        }
        if (!$code) {
            echo json_encode(['status' => 'error', 'message' => 'Código no proporcionado.']);
            exit;
        }
        // Eliminar la promo del pedido
        if (!OrderHelpers::removePromoFromOrder($code)) {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo quitar el código.']);
            exit;
        }
        // Quitar del carrito cualquier producto asociado a esa promo
        $order = OrderHelpers::getOrder();
        if ($order && isset($order['items']) && is_array($order['items'])) {
            foreach ($order['items'] as $item) {
                if (isset($item['metadata']['promo']) && $item['metadata']['promo'] === $code) {
                    OrderHelpers::removeItemFromOrder($item['product_id'], $item['metadata']);
                }
            }
        }
        echo json_encode(['status' => 'ok']);
        exit;
    });

    $router->post('/add/{id}', function($id) {
        $order = OrderHelpers::getOrder();
        if ($order === null) {
            echo json_encode(['status' => 'error', 'message' => 'No hay pedido activo.']);
            exit;
        }
        $product = Product::getById($id);
        if ($product === null) {
            echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado.']);
            exit;
        }
        $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        // --- Validación de puntos para productos promocionales ---
        $isPromoWithPoints = ($product->type == EPRODUCT_TYPE::PROMOTION && $product->points && $product->points > 0);
        if ($isPromoWithPoints) {
            // Solo usuarios logueados pueden usar puntos
            if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para canjear este producto con puntos.']);
                exit;
            }
            $user = User::getById($_SESSION['user']['id']);
            if (!$user || $user->points < ($product->points * $quantity)) {
                $faltan = $product->points * $quantity - ($user ? $user->points : 0);
                echo json_encode(['status' => 'error', 'message' => 'No tienes suficientes puntos para añadir este producto. Te faltan ' . $faltan . ' puntos.']);
                exit;
            }
        }

        // Recopilar componentes seleccionados como array de snapshots
        $components = [];
        foreach ($_POST as $key => $value) {
            if (preg_match('/^(group|category|product)_/', $key)) {
                if (preg_match('/^product_(\d+)$/', $key, $m)) {
                    $component = Product::getById((int)$m[1]);
                    if ($component) {
                        $components[] = [ 'id' => $component->id, 'name' => $component->name ];
                    }
                } elseif (preg_match('/^group_\d+$/', $key) || preg_match('/^category_\d+$/', $key)) {
                    if (preg_match('/^product_(\d+)$/', $value, $m2)) {
                        $component = Product::getById((int)$m2[1]);
                        if ($component) {
                            $components[] = [ 'id' => $component->id, 'name' => $component->name ];
                        }
                    }
                }
            }
        }

        $metadata = [
            'components' => $components,
            'notes' => $notes
        ];
        // Si viene por código promocional, añadirlo a los metadatos
        if (isset($_POST['promo']) && $_POST['promo']) {
            $metadata['promo'] = $_POST['promo'];
        }
        // Forzar used_points en el backend si es promo con puntos
        if ($isPromoWithPoints) {
            $metadata['used_points'] = true;
        }
        $ok = OrderHelpers::addItemToOrder($product->id, $metadata, $quantity);
        if ($ok) {
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo añadir el producto al carrito.']);
        }
        exit;
    });

    $router->post('/remove', function() {
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = isset($input['product_id']) ? intval($input['product_id']) : null;
        $metadata = isset($input['metadata']) ? $input['metadata'] : null;
        if ($productId === null || $metadata === null) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
            exit;
        }
        // Decodificar metadata si es string JSON
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $metadata = $decoded;
            }
        }
        // Si el producto tiene código promocional en los metadatos, quitar el código de la orden
        if (isset($metadata['promo']) && $metadata['promo']) {
            OrderHelpers::removePromoFromOrder($metadata['promo']);
        }
        $ok = OrderHelpers::removeItemFromOrder($productId, $metadata);
        if ($ok) {
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo quitar el producto del carrito.']);
        }
        exit;
    });

    $router->post('/create-stripe', function() {
        
        // Leer el email del body si viene (JSON)
        $input = json_decode(file_get_contents('php://input'), true);
        $email = isset($input['email']) ? trim($input['email']) : null;

        // Configura tu clave secreta de Stripe desde la configuración
        \Stripe\Stripe::setApiKey(CONFIG->STRIPE_SECRET_KEY);

        $order = isset($_SESSION['order']) ? $_SESSION['order'] : null;
        if (!$order || !isset($order['items']) || count($order['items']) === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'No hay pedido activo.']);
            exit;
        }

        $total = 0;
        foreach ($order['items'] as $item) {
            $price = isset($item['product_snapshot']['price']) ? $item['product_snapshot']['price'] : 0;
            $total += $price * $item['quantity'];
        }

        if ($total <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'El total debe ser mayor a cero.']);
            exit;
        }

        try {
            $params = [
                'amount' => intval($total * 100), // Stripe usa centavos
                'currency' => 'eur', // Cambia a tu moneda
                'metadata' => [
                    'order_id' => session_id(),
                    'table' => $order['table'] ?? ''
                ]
            ];
            if ($email) {
                $params['receipt_email'] = $email;
            }
            $paymentIntent = \Stripe\PaymentIntent::create($params);
            echo json_encode(['client_secret' => $paymentIntent->client_secret]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    });

    // Guardar pedido en la base de datos tras pago Stripe
    $router->post('/save', function() {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $orderData = $input['order'] ?? null;
        $email = $input['email'] ?? null;
        $stripe_id = $input['stripe_id'] ?? null;
        $total_points = $input['total_points'] ?? 0;
        $user_id = $input['user_id'] ?? null;

        if (!$orderData || !$email || ($stripe_id === null && $total_points == 0)) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos para guardar el pedido.']);
            exit;
        }

        $table_id = $orderData['table'] ?? null;
        $items = $orderData['items'] ?? [];
        if (!$table_id || count($items) === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Pedido vacío o sin mesa.']);
            exit;
        }

        // --- PAGO CON PUNTOS: Validación y deducción ---
        if (CONFIG->FIDELITY_ENABLED && $user_id && $total_points > 0) {
            $user = User::getById($user_id);
            if ($user) {
                if ($user->points < $total_points) {
                    http_response_code(400);
                    echo json_encode(['error' => 'No tienes suficientes puntos para pagar este pedido.']);
                    exit;
                }
                // Restar puntos usados
                $user->addPoints(-$total_points);
            }
        }

        // Añadir puntos al usuario si se proporciona (solo si no pagó con puntos)
        if(CONFIG->FIDELITY_ENABLED && $user_id && $total_points == 0) {
            $user = User::getById($user_id);
            if ($user) {
                $total = 0;
                foreach ($items as $item) {
                    $price = isset($item['product_snapshot']['price']) ? $item['product_snapshot']['price'] : 0;
                    $points_gained = intval($price * $item['quantity'] * CONFIG->POINTS_PER_UNIT);
                    $total += $points_gained;
                }
                $points = $total;
                $user->addPoints($points);
            }
        }

        // Crear el pedido principal usando el modelo refactorizado
        $order = new Order();
        $order->table_id = $table_id;
        $order->user = isset($input['user_id']) && $input['user_id'] ? $input['user_id'] : null;
        $order->stripe_id = $stripe_id;
        $order->date = (new DateTime(CONFIG->nowInTimezone()))->format('Y-m-d');
        $order->time = CONFIG->nowInTimezone();
        $order->save();

        if (!$order->id) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo crear el pedido.']);
            exit;
        }

        // Guardar los productos del pedido
        foreach ($items as $item) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $price = $item['product_snapshot']['price'] ?? 0;
            $metadata = $item['metadata'] ?? null;
            $order->addProduct($product_id, $price, $quantity, $metadata);
        }

        // --- RESPUESTA SEGÚN TIPO DE PAGO ---
        if (CONFIG->FIDELITY_ENABLED && $user_id && $total_points > 0) {
            // Si pagó con puntos, devolver los puntos restantes
            $user = User::getById($user_id);
            echo json_encode(['status' => 'ok', 'order_id' => $order->id, 'points_left' => $user ? $user->points : 0]);
            return;
        }
        // Si pagó con dinero, devolver puntos ganados (si aplica)
        echo json_encode(['status' => 'ok', 'order_id' => $order->id, 'points_gained' => isset($points) ? $points : 0]);
    });

    $router->get('/user-by-email', function() {
        header('Content-Type: application/json');
        $email = isset($_GET['email']) ? trim($_GET['email']) : '';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['exists' => false]);
            exit;
        }
        $user = User::getByEmail($email);
        if ($user) {
            echo json_encode([
                'exists' => true,
                'points_per_unit' => CONFIG->POINTS_PER_UNIT
            ]);
        } else {
            echo json_encode(['exists' => false]);
        }
        exit;

    });
});