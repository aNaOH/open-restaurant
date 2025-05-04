<?php

class ViewController
{
    /**
     * Render a view file with Twig.
     */
    public static function render($view, $data = [])
    {
        $loader = new \Twig\Loader\FilesystemLoader('./views');
        $twig = new \Twig\Environment($loader, [
            'cache' => './cache/twig',
            'debug' => true,
        ]);

        echo $twig->render($view . '.php.twig', $data);
    }
}