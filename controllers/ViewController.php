<?php
// Controlador de vistas para renderizar plantillas Twig
class ViewController
{
    /**
     * Renderiza una vista usando Twig.
     * @param string $view Nombre de la vista (sin extensión)
     * @param array $data Datos a pasar a la vista
     */
    public static function render($view, $data = [])
    {
        // Carga las plantillas desde la carpeta ./views
        $loader = new \Twig\Loader\FilesystemLoader('./views');
        // Inicializa el entorno Twig con caché y modo debug
        $twig = new \Twig\Environment($loader, [
            'cache' => './cache/twig',
            'debug' => true,
        ]);
        // Renderiza la vista y muestra el resultado
        echo $twig->render($view . '.php.twig', array_merge(ViewHelpers::getBaseData(), $data));
    }
}