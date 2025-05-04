<?php

$router = new \Bramus\Router\Router();

$router->get("/", function() {
    include_once 'views/index.php';
});

$router->run();
