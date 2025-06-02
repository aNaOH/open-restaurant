<?php

$router->mount('/users', function() use ($router) {
    $router->get('/', function() {
        $users = User::getAll();
        ViewController::render('admin/users/index', array_merge(SidebarHelpers::getBaseData(), [
            'users' => $users
        ]));
    });
    $router->get('/add', function() {
        ViewController::render('admin/users/add', SidebarHelpers::getBaseData());
    });
    $router->post('/add', function() {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role = $_POST['role'] ?? 2; // Por defecto cliente
        $points = $_POST['points'] ?? 0;
        if ($name && $email && $password) {
            if ($password !== $password_confirm) {
                ViewController::render('admin/users/add', array_merge(SidebarHelpers::getBaseData(), [
                    'error' => 'Las contraseñas no coinciden.'
                ]));
                return;
            }
            $user = new User(null, $email, $name, password_hash($password, PASSWORD_DEFAULT), $role, $points);
            $user->save();
            header('Location: /admin/users');
            exit;
        } else {
            ViewController::render('admin/users/add', array_merge(SidebarHelpers::getBaseData(), [
                'error' => 'Todos los campos son obligatorios.'
            ]));
        }
    });
    $router->get('/edit/{id}', function($id) {
        $user = User::getById($id);
        if ($user) {
            ViewController::render('admin/users/edit', array_merge(SidebarHelpers::getBaseData(), [
                'user' => $user
            ]));
        } else {
            header('Location: /admin/users');
            exit;
        }
    });
    $router->post('/edit/{id}', function($id) {
        $user = User::getById($id);
        if ($user) {
            $user->name = $_POST['name'] ?? $user->name;
            $user->email = $_POST['email'] ?? $user->email;
            if (!empty($_POST['password'])) {
                $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
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
