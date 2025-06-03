<?php

$router->mount('/composed', function() use ($router) {
    $router->get('/add', function() {
        $categories = Category::getAll();
        $products = Product::getAll();
        $data = [
            'categories' => $categories,
            'products' => $products
        ];
        ViewController::render('admin/composed/add', $data);
    });

    $router->post('/add', function() {
        // Decodificar componentes si vienen como string JSON (AJAX)
        $components = isset($_POST['components']) ? $_POST['components'] : [];
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
        // Validar que el total de productos/categorías seleccionados sea al menos dos
        $totalComponents = 0;
        foreach ($components as $component) {
            $data = is_array($component) ? $component : json_decode($component, true);
            if ($data && isset($data['type'], $data['ids']) && is_array($data['ids'])) {
                $totalComponents += count($data['ids']);
            }
        }
        if ($totalComponents < 2) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Debes seleccionar al menos dos productos o categorías en total para los componentes.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        $composed = new Product(
            null,
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            EPRODUCT_TYPE::COMPOSED,
            (isset($_POST['category']) && $_POST['category'] !== 'none') ? $_POST['category'] : null
        );
        $composed->save();
        // Procesar componentes (productos y categorías) con posición
        $totalComponents = 0;
        $position = 0;
        foreach ($components as $component) {
            $data = is_array($component) ? $component : json_decode($component, true);
            if ($data['type'] === 'product') {
                foreach ($data['ids'] as $prodId) {
                    $composed->addChild($prodId, $position);
                    $totalComponents++;
                }
            } elseif ($data['type'] === 'category') {
                foreach ($data['ids'] as $catId) {
                    $composed->addCategoryChild($catId, $position);
                    $totalComponents++;
                }
            }
            // Si hay más de un id, todos comparten la misma posición
            $position++;
        }
        if ($totalComponents < 2) {
            // Eliminar el producto compuesto y sus relaciones si no cumple la validación
            Connection::doDelete(DBCONN, 'Product', ['id' => $composed->id]);
            Connection::doDelete(DBCONN, 'ComposedBy', ['product_id' => $composed->id]);
            Connection::doDelete(DBCONN, 'ComposedCategory', ['product_id' => $composed->id]);
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Debes seleccionar al menos dos productos o categorías en total para los componentes.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
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
            // No guardar ruta de imagen en el producto compuesto, solo subir el archivo
            $jsonResponse = [
                'status' => 'success',
                'message' => 'Producto compuesto creado correctamente.',
                'productId' => $composed->id,
                'imageUrl' => '/' . $uploadFile
            ];
            header('Content-Type: application/json');
            http_response_code(201);
            echo json_encode($jsonResponse);
            exit;
        } else {
            // Si falla la imagen, elimina el producto compuesto y sus relaciones
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

    $router->get('/', function() {
        // Obtener todos los productos compuestos
        $composedProducts = Product::getAllByType(EPRODUCT_TYPE::COMPOSED);
        
        $data = [
            'composedProducts' => $composedProducts
        ];
        ViewController::render('admin/composed/index', $data);
    });

    $router->get('/edit/{id}', function($id) {
        $composed = Product::getById($id);
        if (!$composed || $composed->type !== EPRODUCT_TYPE::COMPOSED) {
            ViewController::renderError('Producto compuesto no encontrado.');
            return;
        }
        $categories = Category::getAll();
        $products = Product::getAll();
        // Procesar componentes y categoría para el formulario
        $components = $composed->getChildren();
        $category = is_object($composed->category) ? $composed->category : ($composed->category ? Category::getById($composed->category) : null);
        $data = [
            'composed' => $composed,
            'components' => $components,
            'categories' => $categories,
            'products' => $products,
            'category' => $category
        ];
        ViewController::render('admin/composed/edit', $data);
    });

    $router->post('/edit/{id}', function($id) {
        $composed = Product::getById($id);
        if (!$composed || $composed->type !== EPRODUCT_TYPE::COMPOSED) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Producto compuesto no encontrado.'
            ];
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode($jsonResponse);
            exit;
        }
        // Decodificar componentes si vienen como string JSON (AJAX)
        $components = isset($_POST['components']) ? $_POST['components'] : [];
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
        // Validar que el total de productos/categorías seleccionados sea al menos dos
        $totalComponents = 0;
        foreach ($components as $component) {
            $data = is_array($component) ? $component : json_decode($component, true);
            if ($data && isset($data['type'], $data['ids']) && is_array($data['ids'])) {
                $totalComponents += count($data['ids']);
            }
        }
        if ($totalComponents < 2) {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Debes seleccionar al menos dos productos o categorías en total para los componentes.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
        // Actualizar datos básicos
        $composed->name = $_POST['name'];
        $composed->description = $_POST['description'];
        $composed->price = $_POST['price'];
        $composed->category = (isset($_POST['category']) && $_POST['category'] !== 'none') ? $_POST['category'] : null;
        $composed->save();
        // Eliminar componentes anteriores
        $composed->removeAllChildren();
        // Guardar nuevos componentes
        $position = 0;
        foreach ($components as $component) {
            $data = is_array($component) ? $component : json_decode($component, true);
            if ($data['type'] === 'product') {
                foreach ($data['ids'] as $prodId) {
                    $composed->addChild($prodId, $position);
                }
            } elseif ($data['type'] === 'category') {
                foreach ($data['ids'] as $catId) {
                    $composed->addCategoryChild($catId, $position);
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
            'message' => 'Producto compuesto actualizado correctamente.'
        ];
        header('Content-Type: application/json');
        echo json_encode($jsonResponse);
        exit;
    });

    $router->post('/delete/{id}', function($id) {
        $composed = Product::getById($id);
        if ($composed && $composed->type === EPRODUCT_TYPE::COMPOSED) {
            $composed->delete();
        }
        header('Location: /admin/composed');
        exit;
    });

});
