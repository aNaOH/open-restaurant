<?php

$router->mount('/categories', function() use ($router) {
    $router->get('/', function() {
        $categories = Category::getAll();
        ViewController::render('admin/categories/index', ['categories' => $categories]);
    });

    $router->get('/add', function() {
        ViewController::render('admin/categories/add');
    });

    $router->post('/add', function() {

        if(!(isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK)) {
            // send error message if no file is uploaded or if there is an upload error via json response
            $jsonResponse = [
                'status' => 'error',
                'message' => 'No se ha subido ninguna imagen o ha ocurrido un error al subirla.'
            ];

            header('Content-Type: application/json');
            // Set the response code to 400 Bad Request
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }

        if (isset($_POST['name'])) {
            $category = new Category(null, $_POST['name']);
            $category->save();
            $uploadDir = 'assets/uploads/categories/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            // Assuming the category ID is set after saving, add extension to the filename based on the uploaded file
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $uploadFile = $uploadDir . "category_".$category->id . '.' . $extension;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $jsonResponse = [
                    'status' => 'success',
                    'message' => 'Categoría creada correctamente.',
                    'categoryId' => $category->id,
                    'imageUrl' => '/' . $uploadFile // Assuming the URL is relative to the root
                ];
                header('Content-Type: application/json');
                // Set the response code to 201 Created
                http_response_code(201);
                echo json_encode($jsonResponse);
                exit;
            } else {
                $category->delete();
                $jsonResponse = [
                    'status' => 'error',
                    'message' => 'No se ha subido ninguna imagen o ha ocurrido un error al subirla.'
                ];

                header('Content-Type: application/json');
                // Set the response code to 400 Bad Request
                http_response_code(400);
                echo json_encode($jsonResponse);
                exit;
            }
        } else {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Introduce el nombre de la categoría.'
            ];

            header('Content-Type: application/json');
            // Set the response code to 400 Bad Request
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
    });

    $router->get('/edit/{id}', function($id) {
        $category = Category::getById($id);
        if ($category) {
            ViewController::render('admin/categories/edit', ['category' => $category]);
        } else {
            header("Location: /admin/categories");
            exit;
        }
    });

    $router->post('/edit/{id}', function($id) {
        if (isset($_POST['name'])) {
            $category = Category::getById($id);
            if (!$category) {
                $jsonResponse = [
                    'status' => 'error',
                    'message' => 'Categoría no encontrada.'
                ];
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode($jsonResponse);
                exit;
            }
            $category->name = $_POST['name'];

            // Handle image upload if a new image is provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = 'assets/uploads/categories/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $uploadFile = $uploadDir . "category_" . $category->id . '.' . $extension;
                $pastFilePath = $category->getImagePath();
                if ($pastFilePath && file_exists($pastFilePath)) {
                    unlink($pastFilePath); // Delete the old image file
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

            $category->save();
            $jsonResponse = [
                'status' => 'success',
                'message' => 'Categoría actualizada correctamente.',
                'categoryId' => $category->id,
                'imageUrl' => isset($category->image) ? '/' . $category->image : null
            ];
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode($jsonResponse);
            exit;
        } else {
            $jsonResponse = [
                'status' => 'error',
                'message' => 'Introduce el nombre de la categoría.'
            ];
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode($jsonResponse);
            exit;
        }
    });

    $router->get('/delete/{id}', function($id) {
        $category = Category::getById($id);
        if ($category) {
            Connection::doDelete(DBCONN, 'category', ['id' => $id]);
            header("Location: /admin/categories");
            exit;
        } else {
            ViewController::render('admin/categories/index', ['error' => 'Categoría no encontrada.']);
        }
    });

    $router->post('/delete/{id}', function($id) {
        $category = Category::getById($id);
        if ($category) {
            $category->delete();
        }
        header("Location: /admin/categories");
        exit;
    });
});