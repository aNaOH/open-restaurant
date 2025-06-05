<?php
// Rutas del módulo de usuarios en el panel de administración
$router->mount('/users', function() use ($router) {
    // Listar usuarios
    $router->get('/', function() {
        $users = User::getAll();
        ViewController::render('admin/users/index', [
            'users' => $users
        ]);
    });
    // Formulario para crear usuario
    $router->get('/add', function() {
        ViewController::render('admin/users/add');
    });
    // Procesar creación de usuario
    $router->post('/add', function() {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role = $_POST['role'] ?? 2; // Por defecto cliente
        $points = $_POST['points'] ?? 0;
        if ($name && $email && $password) {
            if ($password !== $password_confirm) {
                ViewController::render('admin/users/add');
                return;
            }
            // Validación de contraseña segura
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
            if (!empty($passwordErrors)) {
                ViewController::render('admin/users/add', $data);
                return;
            }
            $user = new User(null, $email, $name, password_hash($password, PASSWORD_DEFAULT), $role, $points);
            $user->save();
            header('Location: /admin/users');
            exit;
        } else {
            ViewController::render('admin/users/add', $data);
        }
    });
    // Formulario para editar usuario
    $router->get('/edit/{id}', function($id) {
        $user = User::getById($id);
        if ($user) {
            ViewController::render('admin/users/edit', $data);
        } else {
            header('Location: /admin/users');
            exit;
        }
    });
    // Procesar edición de usuario
    $router->post('/edit/{id}', function($id) {
        $user = User::getById($id);
        if ($user) {
            $user->name = $_POST['name'] ?? $user->name;
            $user->email = $_POST['email'] ?? $user->email;
            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                $password_confirm = $_POST['password_confirm'] ?? '';
                if ($password !== $password_confirm) {
                    ViewController::render('admin/users/edit', $data);
                    return;
                }
                // Validación de contraseña segura
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
                if (!empty($passwordErrors)) {
                    ViewController::render('admin/users/edit', $data);
                    return;
                }
                $user->password = password_hash($password, PASSWORD_DEFAULT);
            }
            $user->role = isset($_POST['role']) ? EUSER_ROLE::from(intval($_POST['role'])) : $user->role;
            $user->points = $_POST['points'] ?? $user->points;
            $user->save();
            header('Location: /admin/users');
            exit;
        } else {
            header('Location: /admin/users');
            exit;
        }
    });
    // Eliminar usuario
    $router->post('/delete/{id}', function($id) {
        $user = User::getById($id);
        if ($user) {
            $user->delete();
        }
        header('Location: /admin/users');
        exit;
    });
    // Aquí puedes añadir más rutas (crear, editar, eliminar) si lo necesitas
});
