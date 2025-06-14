<?php
// Rutas principales del frontend (inicio, login, registro, logout)
// Incluye lógica de autenticación y renderizado de vistas principales
include_once 'models/Table.php';
include_once 'models/Category.php';
include_once 'models/Product.php';
include_once 'models/User.php';
include_once 'models/Order.php';

$router = new \Bramus\Router\Router();

// Página de inicio
$router->get("/", function() {
    $categories = Category::getAll();
    // Eliminar de las categorías aquellas que no tienen productos
    $categories = array_filter($categories, function($category) {
        return count(Product::getByCategory($category->id)) > 0;
    });
    $data = [
        'categories' => $categories,
        'order' => OrderHelpers::getOrder(),
        'isHomePage' => true // Pasar esta variable para la página de inicio
    ];
    ViewController::render('index', $data);
});

// Página de login
$router->get("/login", function() {
    if(AuthHelpers::isLoggedIn()) {
        header("Location: /");
        exit;
    }
    ViewController::render('auth/login');
});

// Página de registro
$router->get("/register", function() {
    if(AuthHelpers::isLoggedIn()) {
        header("Location: /");
        exit;
    }
    ViewController::render('auth/register');
});

// Cerrar sesión
$router->get("/logout", function() {
    if(AuthHelpers::isLoggedIn()) {
        session_destroy();
    }
    header("Location: /");
    exit;
});

// Procesar login
$router->post("/login", function() {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $data = [
                'error' => 'Por favor, completa todos los campos.'
            ];
            ViewController::render('auth/login', $data);
            return;
        }

        $user = User::getByEmail($email);
        if ($user && password_verify($password, $user->password)) {
            $_SESSION['user'] = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'points' => $user->points,
                'role' => $user->role
            ];
            header("Location: /");
            exit;
        } else {
            $data = [
                'error' => 'Usuario o contraseña incorrectos.'
            ];
            ViewController::render('auth/login', $data);
        }
    } else {
        $data = [
            'error' => 'Por favor, completa todos los campos.'
        ];
        ViewController::render('auth/login', $data);
    }
});

// Ruta para procesar el registro de usuario
$router->post("/register", function() {
    // Obtiene los datos enviados por el formulario
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Verifica que todos los campos estén completos
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $data = [
            'error' => 'Por favor, completa todos los campos.'
        ];
        ViewController::render('auth/register', $data);
        return;
    }
    // Valida el formato del correo electrónico
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $data = [
            'error' => 'El correo electrónico no es válido.'
        ];
        ViewController::render('auth/register', $data);
        return;
    }
    // Verifica que las contraseñas coincidan
    if ($password !== $confirm_password) {
        $data = [
            'error' => 'Las contraseñas no coinciden.'
        ];
        ViewController::render('auth/register', $data);
        return;
    }
    // Validación de contraseña segura (longitud, mayúscula, minúscula, número, especial)
    $passwordErrors = [];
    if (strlen($password) < 8) {
        $passwordErrors[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos una letra mayúscula.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos una letra minúscula.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos un número.';
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $passwordErrors[] = 'La contraseña debe contener al menos un carácter especial.';
    }
    // Si hay errores de contraseña, los muestra
    if (!empty($passwordErrors)) {
        $data = [
            'error' => implode(' ', $passwordErrors)
        ];
        ViewController::render('auth/register', $data);
        return;
    }
    // Verifica si el correo ya está registrado
    if (User::getByEmail($email)) {
        $data = [
            'error' => 'El correo electrónico ya está registrado.'
        ];
        ViewController::render('auth/register', $data);
        return;
    }
    // Crea el usuario y lo guarda en la base de datos
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $user = new User(null, $email, $name, $hashedPassword, EUSER_ROLE::USER);
    if ($user) {
        $user->save();
        header("Location: /"); // Redirige al inicio si todo sale bien
        exit;
    } else {
        $data = [
            'error' => 'No se pudo crear la cuenta. Intenta de nuevo.'
        ];
        ViewController::render('auth/register', $data);
    }
});

$router->get('/logo', function() {
        //Buscar el logo en la carpeta de contenido, puede ser un archivo .png o .jpg
        //si se encuentra, devolverlo con el MIME correcto, si no, devolver 404
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

include_once 'routes/admin/index.php';
include_once 'routes/order.php';
include_once 'routes/employee.php';

$router->run();
