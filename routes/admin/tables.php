<?php

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

// Rutas del módulo de mesas en el panel de administración
$router->mount('/tables', function() use ($router) {
    // Listar mesas
    $router->get('/', function() {
        $tables = Table::getAll();
        ViewController::render('admin/tables/index', [
            'tables' => $tables // Pasa las mesas a la vista
        ]);
    });

    // Formulario para crear mesa
    $router->get('/add', function() {
        ViewController::render('admin/tables/add');
    });

    // Procesar creación de mesa
    $router->post('/add', function() {
        if (isset($_POST['id'])) {
            $table = new Table($_POST['id'], $_POST['notes']);
            $table->save();
            header("Location: /admin/tables");
            exit;
        } else {
            ViewController::render('admin/tables/add');
        }
    });

    // Formulario para editar mesa
    $router->get('/edit/{id}', function($id) {
        $table = Table::getById($id);
        if ($table) {
            ViewController::render('admin/tables/edit', [
                'table' => $table // Pasa la mesa a la vista para editar
            ]);
        } else {
            header("Location: /admin/tables");
            exit;
        }
    });

    // Mostrar QR de la mesa
    $router->get('/qr/{id}', function($id) {
        $table = Table::getById($id); // Busca la mesa por su ID
        if ($table) {

            // Obtiene la URL base del entorno (por ejemplo, http://localhost)
            $baseUrl = Helpers::getBaseUrl();
            // Crea la URL completa para la mesa, que usará el QR
            $tableUrl = $baseUrl . '/order/begin/' . $table->id;

            // Construye el código QR con la librería Endroid QR Code
            $builder = new Builder(
                writer: new PngWriter(), // El QR será una imagen PNG
                writerOptions: [],
                validateResult: false,
                data: $tableUrl, // La URL que contendrá el QR
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High, // Nivel de corrección de errores
                size: 300, // Tamaño del QR
                margin: 10, // Margen alrededor del QR
                roundBlockSizeMode: RoundBlockSizeMode::Margin, // Redondeo de bloques
                labelText: 'Pide aquí', // Texto debajo del QR
                labelFont: new OpenSans(20), // Fuente del texto
                labelAlignment: LabelAlignment::Center // Centrado
            );

            $result = $builder->build(); // Genera el QR
            
            // Renderiza la vista que muestra el QR
            ViewController::render('admin/tables/qr', [
                'qrCode' => $result->getDataUri(), // Pasa la imagen del QR a la vista
                'table' => $table // Pasa el ID de la mesa a la vista
            ]);

            exit;
        } else {
            // Si no existe la mesa, redirige al listado de mesas
            header("Location: /admin/tables");
            exit;
        }
    });

    // Procesar edición de mesa
    $router->post('/edit/{id}', function($id) {
        if (isset($_POST['notes'])) {
            $table = Table::getById($id);
            if (!$table) {
                header("Location: /admin/tables");
                exit;
            }
            $table->notes = $_POST['notes'];
            $table->save();
            header("Location: /admin/tables");
            exit;
        } else {
            ViewController::render('admin/tables/edit');
        }
    });

    // Eliminar mesa (POST)
    $router->post('/delete/{id}', function($id) {
        $table = Table::getById($id);
        if ($table) {
            $table->delete();
        }
        header("Location: /admin/tables");
        exit;
    });
});