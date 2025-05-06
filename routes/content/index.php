<?php

$router->mount('/content', function() use ($router) {
    $router->get('/logo', function() {
        //Search for the logo in the content folder, it can be a .png or .jpg file
        //if found, return it with the correct MIME, if not, return 404
        $logo = glob('content/logo.{jpg,png}', GLOB_BRACE);
        if (count($logo) > 0) {
            $logo = $logo[0];
            $ext = pathinfo($logo, PATHINFO_EXTENSION);
            header('Content-Type: image/' . $ext);
            readfile($logo);
        } else {
            header("HTTP/1.0 404 Not Found");
            exit;
        }
    });
});