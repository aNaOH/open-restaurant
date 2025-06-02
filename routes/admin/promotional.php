<?php

$router->mount('/promotional', function() use ($router) {
    $router->get('/', function() {
        // Listar productos promocionales (compuestos con code o points)
        $promotionalProducts = Product::getAllByType(EPRODUCT_TYPE::PROMOTION); // true,true: solo con code o points
        ViewController::render('admin/promotional/index', array_merge(SidebarHelpers::getBaseData(), [
            'promotionalProducts' => $promotionalProducts,
            'discountEnabled' => CONFIG->DISCOUNT_ENABLED,
            'fidelityEnabled' => CONFIG->FIDELITY_ENABLED
        ]));
    });

    $router->get('/add', function() {
        $categories = Category::getAll();
        $products = Product::getAll();
        ViewController::render('admin/promotional/add', array_merge(SidebarHelpers::getBaseData(), [
            'categories' => $categories,
            'products' => $products,
            'discountEnabled' => CONFIG->DISCOUNT_ENABLED,
            'fidelityEnabled' => CONFIG->FIDELITY_ENABLED
        ]));
    });

    $router->post('/add', function() {
        // Decodificar componentes si vienen como string JSON (AJAX)
        $components = $_POST['components'];
        if (is_string($components)) {
            $components = json_decode($components, true);
        }
        // Validación de campos obligatorios
        if (!isset($_POST['name']) || trim($_POST['name']) === '' ||
            !isset($_POST['description']) || trim($_POST['description']) === '' ||
            !isset($_POST['price']) || $_POST['price'] === '' ||
            !is_array($components) || count($components) < 1
        ) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Faltan datos obligatorios.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        // Validar que el total de productos/categorías seleccionados sea al menos uno
        $totalComponents = 0;
        foreach ($components as $component) {
            $data = is_array($component) ? $component : json_decode($component, true);
            if ($data && isset($data['type'], $data['ids']) && is_array($data['ids'])) {
                $totalComponents += count($data['ids']);
            }
        }
        if ($totalComponents < 1) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Debes seleccionar al menos un producto o categoría como componente.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        // Validar unicidad de código promocional si se introduce
        $promoCode = isset($_POST['promo_code']) ? trim($_POST['promo_code']) : null;
        if ($promoCode) {
            $exists = Connection::doSelect(DBCONN, 'Product', ['code' => $promoCode]);
            if ($exists) {
                $jsonResponse = [
                    'status' => 'error',
                    'message' => 'El código promocional ya existe.'
                ];
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode($jsonResponse);
                exit;
            }
        }
        $points = isset($_POST['loyalty_points']) && $_POST['loyalty_points'] !== '' ? intval($_POST['loyalty_points']) : null;
        $composed = new Product(
            null,
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            EPRODUCT_TYPE::PROMOTION,
            (isset($_POST['category']) && $_POST['category'] !== 'none') ? $_POST['category'] : null,
            $promoCode,
            $points
        );
        $composed->save();
        // Procesar componentes (productos y categorías) con posición
        $position = 0;
        foreach ($components as $component) {
            $data = is_array($component) ? $component : json_decode($component, true);
            if ($data['type'] === 'product') {
                foreach ($data['ids'] as $prodId) {
                    $composed->addChild($prodId, $position);
                }
            } elseif ($data['type'] === 'category') {
                foreach ($data['ids'] as $catId) {
                    $composed->addChildCategory($catId, $position);
                }
            }
            $position++;
        }
        // Manejo de imagen (igual que en products)
        if (!(isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK)) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'No se ha subido ninguna imagen o ha ocurrido un error al subirla.'
            ];
            // Limpieza de relaciones y producto
            Connection::doDelete(DBCONN, 'Product', ['id' => $composed->id]);
            Connection::doDelete(DBCONN, 'ComposedBy', ['product_id' => $composed->id]);
            Connection::doDelete(DBCONN, 'ComposedCategory', ['product_id' => $composed->id]);
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        $uploadDir = 'assets/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $uploadFile = $uploadDir . "product_" . $composed->id . '.' . $extension;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $jsonResponse = [
                'status' => 'success',
                'message' => 'Producto promocional creado correctamente.',
                'productId' => $composed->id,
                'imageUrl' => '/' . $uploadFile
            ];
            header('Content-Type: application/json');
            http_response_code(201);
            echo json_encode($jsonResponse);
            exit;
        } else {
            Connection::doDelete(DBCONN, 'Product', ['id' => $composed->id]);
            Connection::doDelete(DBCONN, 'ComposedBy', ['product_id' => $composed->id]);
            Connection::doDelete(DBCONN, 'ComposedCategory', ['product_id' => $composed->id]);
            $jsonResponse = [
                'status' => 'error',
                'message' => 'No se ha subido ninguna imagen o ha ocurrido un error al subirla.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
    });

    // Endpoint para validar unicidad de código promocional (AJAX)
    $router->get('/check-code', function() {
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $exclude = isset($_GET['exclude']) ? intval($_GET['exclude']) : null;
        $exists = false;
        if ($code) {
            $where = ['code' => $code];
            $rows = Connection::doSelect(DBCONN, 'Product', $where);
            if ($rows) {
                if ($exclude) {
                    // Si hay coincidencia pero es el mismo id, no cuenta como existente
                    $exists = false;
                    foreach ($rows as $row) {
                        if ((int)$row['id'] !== $exclude) {
                            $exists = true;
                            break;
                        }
                    }
                } else {
                    $exists = true;
                }
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['exists' => $exists]);
        exit;
    });

    $router->get('/edit/{id}', function($id) {
        $composed = Product::getById($id);
        if (!$composed || $composed->type !== EPRODUCT_TYPE::PROMOTION) {
            header('HTTP/1.0 404 Not Found');
            echo 'Producto promocional no encontrado.';
            exit;
        }
        $categories = Category::getAll();
        $products = Product::getAll();
        // Procesar componentes y categoría para el formulario
        $components = $composed->getChildren();
        $category = is_object($composed->category) ? $composed->category : ($composed->category ? Category::getById($composed->category) : null);
        ViewController::render('admin/promotional/edit', array_merge(SidebarHelpers::getBaseData(), [
            'composed' => $composed,
            'components' => $components,
            'categories' => $categories,
            'products' => $products,
            'category' => $category,
            'discountEnabled' => CONFIG->DISCOUNT_ENABLED,
            'fidelityEnabled' => CONFIG->FIDELITY_ENABLED
        ]));
    });

    $router->post('/edit/{id}', function($id) {
        $composed = Product::getById($id);
        if (!$composed || $composed->type !== EPRODUCT_TYPE::PROMOTION) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Producto promocional no encontrado.'
            ];
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode($jsonResponse);
            exit;
        }
        $components = $_POST['components'];
        if (is_string($components)) {
            $components = json_decode($components, true);
        }
        if (!isset($_POST['name']) || trim($_POST['name']) === '' ||
            !isset($_POST['description']) || trim($_POST['description']) === '' ||
            !isset($_POST['price']) || $_POST['price'] === '' ||
            !is_array($components) || count($components) < 1
        ) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Faltan datos obligatorios.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        $totalComponents = 0;
        foreach ($components as $component) {
            $data = is_array($component) ? $component : json_decode($component, true);
            if ($data && isset($data['type'], $data['ids']) && is_array($data['ids'])) {
                $totalComponents += count($data['ids']);
            }
        }
        if ($totalComponents < 1) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Debes seleccionar al menos un producto o categoría como componente.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        // Validación de campos condicionales
        $discountEnabled = CONFIG->DISCOUNT_ENABLED;
        $fidelityEnabled = CONFIG->FIDELITY_ENABLED;
        $promoCode = isset($_POST['promo_code']) ? trim($_POST['promo_code']) : null;
        $points = isset($_POST['loyalty_points']) && $_POST['loyalty_points'] !== '' ? intval($_POST['loyalty_points']) : null;
        if ($discountEnabled && !$fidelityEnabled && !$promoCode) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'El código promocional es obligatorio.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        if ($fidelityEnabled && !$discountEnabled && !$points) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Los puntos de fidelización son obligatorios.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        if ($discountEnabled && $fidelityEnabled && !$promoCode && !$points) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Debes rellenar al menos el código promocional o los puntos de fidelización.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        // Validar unicidad de código promocional si se introduce y ha cambiado
        if ($promoCode && $promoCode !== $composed->code) {
            $exists = Connection::doSelect(DBCONN, 'Product', ['code' => $promoCode]);
            if ($exists) {
                $jsonResponse = [
                    'status' => 'error',
                    'message' => 'El código promocional ya existe.'
                ];
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode($jsonResponse);
                exit;
            }
        }
        // Actualizar datos básicos
        $composed->name = $_POST['name'];
        $composed->description = $_POST['description'];
        $composed->price = $_POST['price'];
        $composed->category = (isset($_POST['category']) && $_POST['category'] !== 'none') ? $_POST['category'] : null;
        $composed->code = $promoCode;
        $composed->points = $points;
        $composed->save();
        $composed->removeAllChildren();
        $position = 0;
        foreach ($components as $component) {
            $data = is_array($component) ? $component : json_decode($component, true);
            if ($data['type'] === 'product') {
                foreach ($data['ids'] as $prodId) {
                    $composed->addChild($prodId, $position);
                }
            } elseif ($data['type'] === 'category') {
                foreach ($data['ids'] as $catId) {
                    $composed->addChildCategory($catId, $position);
                }
            }
            $position++;
        }
        // Manejo de imagen (opcional, solo si se sube una nueva)
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'assets/uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $uploadFile = $uploadDir . "product_" . $composed->id . '.' . $extension;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile);
        }
        $jsonResponse = [
            'status' => 'success',
            'message' => 'Producto promocional actualizado correctamente.'
        ];
        header('Content-Type: application/json');
        echo json_encode($jsonResponse);
        exit;
    });

    $router->post('/delete/{id}', function($id) {
        $promo = Product::getById($id);
        if ($promo && $promo->type === EPRODUCT_TYPE::PROMOTION) {
            $promo->delete();
        }
        header('Location: /admin/promotional');
        exit;
    });

});
