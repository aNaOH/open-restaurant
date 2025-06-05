<?php
// Archivo principal de la aplicación
// Incluye las dependencias y rutas principales

// Incluye el autoload de Composer
include_once 'vendor/autoload.php';

// Incluye archivos de configuración y conexión global
include_once 'globals/config.php';
include_once 'globals/connection.php';

// Incluye el controlador de vistas
include_once 'controllers/ViewController.php';

// Define la constante de configuración global
// CONFIG contiene la configuración cargada desde globals/config.php
define('CONFIG', new Config());

// Verifica si la aplicación ya está instalada
$is_installed = CONFIG->isInstalled();
if (!$is_installed) {
    // Si no está instalada, carga el asistente de instalación
    include_once 'routes/wizard.php';
    exit;
}

// Define la constante de conexión a la base de datos
// DBCONN es la conexión PDO activa
define('DBCONN', Connection::connectToDB(CONFIG->DB_HOST, CONFIG->DB_NAME, CONFIG->DB_USER, CONFIG->DB_PASS));

// Inicia la sesión PHP
session_start();

// Incluye las rutas principales de la aplicación
include_once 'routes/index.php';
