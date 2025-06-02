<?php

$router->mount('/products', function() use ($router) {
    $router->get('/', function() {
        $products = Product::getAllByType(EPRODUCT_TYPE::STANDARD);
        ViewController::render('admin/products/index', array_merge(SidebarHelpers::getBaseData(), [
            'products' => $products
        ]));
    });

    $router->get('/add', function() {
        $categories = Category::getAll();
        ViewController::render('admin/products/add', array_merge(SidebarHelpers::getBaseData(), [
            'categories' => $categories
        ]));
    });

    $router->post('/add', function() {
        if (!isset($_POST['name'], $_POST['category'], $_POST['description'], $_POST['price'])) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Faltan datos obligatorios.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }

        $product = new Product(
            null,
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            EPRODUCT_TYPE::STANDARD,
            $_POST['category'] !== 'none' ? $_POST['category'] : null
        );
        $product->save();

        // Manejo de imagen
        if (!(isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK)) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'No se ha subido ninguna imagen o ha ocurrido un error al subirla.'
            ];
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
        $uploadFile = $uploadDir . "product_" . $product->id . '.' . $extension;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $jsonResponse = [
                'status' => 'success',
                'message' => 'Producto creado correctamente.',
                'productId' => $product->id,
                'imageUrl' => '/' . $uploadFile
            ];
            header('Content-Type: application/json');
            http_response_code(201);
            echo json_encode($jsonResponse);
            exit;
        } else {
            $product->delete();
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

    $router->get('/edit/{id}', function($id) {
        $product = Product::getById($id);
        $categories = Category::getAll();
        if ($product) {
            ViewController::render('admin/products/edit', array_merge(SidebarHelpers::getBaseData(), [
                'product' => $product,
                'categories' => $categories
            ]));
        } else {
            header('Location: /admin/products');
            exit;
        }
    });

    $router->post('/edit/{id}', function($id) {
        $product = Product::getById($id);
        if (!$product) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Producto no encontrado.'
            ];
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode($jsonResponse);
            exit;
        }
        if (!isset($_POST['name'], $_POST['category'], $_POST['description'], $_POST['price'])) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Faltan datos obligatorios.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        $product->name = $_POST['name'];
        $product->description = $_POST['description'];
        $product->price = $_POST['price'];
        if($_POST['category'] !== 'none') {
            $product->category = Category::getById($_POST['category']);
            if (!$product->category) {
                $jsonResponse = [
                    'status' => 'error',
                    'message' => 'Categoría no válida.'
                ];
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode($jsonResponse);
                exit;
            }
        } else {
            $product->category = null;
        }
        $product->type = EPRODUCT_TYPE::STANDARD;

        // Manejo de imagen
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'assets/uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $uploadFile = $uploadDir . "product_" . $product->id . '.' . $extension;
            $pastFilePath = $product->getImagePath();
            if ($pastFilePath && file_exists($pastFilePath)) {
                unlink($pastFilePath); // Elimina la imagen anterior
            }
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $jsonResponse = [
                    'status' => 'error',
                    'message' => 'Error al subir la imagen.'
                ];
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode($jsonResponse);
                exit;
            } 
        }

        $product->save();
        $jsonResponse = [
            'status' => 'success',
            'message' => 'Producto actualizado correctamente.',
            'productId' => $product->id,
            'imageUrl' => isset($product->image) ? '/' . $product->image : null
        ];
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode($jsonResponse);
        exit;
    });

    $router->post('/delete/{id}', function($id) {
        $product = Product::getById($id);
        if ($product) {
            $product->delete();
            header('Location: /admin/products');
            exit;
        } else {
            ViewController::render('admin/products/index', array_merge(SidebarHelpers::getBaseData(), ['error' => 'Producto no encontrado.']));
        }
    });
});
