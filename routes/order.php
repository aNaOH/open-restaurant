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

    $router->get('/', function() {
        // Get the current order
        $order = OrderHelpers::getOrder();
        if ($order === null) {
            // Redirect to home if no order exists
            header('Location: /');
            exit;
        }

        $categories = Category::getAll();
        // Remove from categories those that have no products
        $categories = array_filter($categories, function($category) {
            return count(Product::getByCategory($category->id)) > 0;
        });

        // Render the order page with the current order
        ViewController::render('order/index', [
            'order' => $order,
            'restaurantName' => CONFIG->RESTAURANT_NAME
        ]);
    });

    $router->get("/stop", function() {
        // Stop the current order
        OrderHelpers::clearOrder();
        // Redirect to home
        header('Location: /');
        exit;
    });
});