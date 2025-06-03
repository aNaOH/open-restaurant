<?php

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

$router->mount('/tables', function() use ($router) {
    $router->get('/', function() {
        $tables = Table::getAll();
        ViewController::render('admin/tables/index', $data);
    });

    $router->get('/add', function() {
        ViewController::render('admin/tables/add');
    });

    $router->post('/add', function() {
        if (isset($_POST['id'])) {
            $table = new Table($_POST['id'], $_POST['notes']);
            $table->save();
            header("Location: /admin/tables");
            exit;
        } else {
            ViewController::render('admin/tables/add', $data);
        }
    });

    $router->get('/edit/{id}', function($id) {
        $table = Table::getById($id);
        if ($table) {
            ViewController::render('admin/tables/edit', $data);
        } else {
            header("Location: /admin/tables");
            exit;
        }
    });

    $router->get('/qr/{id}', function($id) {
        $table = Table::getById($id);
        if ($table) {

            // Get base URL from the environment
            $baseUrl = Helpers::getBaseUrl();
            // Create the full URL for the table
            $tableUrl = $baseUrl . '/order/begin/' . $table->id;

            $builder = new Builder(
                writer: new PngWriter(),
                writerOptions: [],
                validateResult: false,
                data: $tableUrl,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                labelText: 'Pide aquí',
                labelFont: new OpenSans(20),
                labelAlignment: LabelAlignment::Center
            );

            $result = $builder->build();
            
            ViewController::render('admin/tables/qr', $data);

            exit;
        } else {
            header("Location: /admin/tables");
            exit;
        }
    });

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
            ViewController::render('admin/tables/edit', $data);
        }
    });

    $router->get('/delete/{id}', function($id) {
        $table = Table::getById($id);
        if ($table) {
            Connection::doDelete(DBCONN, 'table', ['id' => $id]);
            header("Location: /admin/tables");
            exit;
        } else {
            ViewController::render('admin/tables/index', $data);
        }
    });

    $router->post('/delete/{id}', function($id) {
        $table = Table::getById($id);
        if ($table) {
            $table->delete();
        }
        header("Location: /admin/tables");
        exit;
    });
});