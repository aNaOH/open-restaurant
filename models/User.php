<?php
// Clase que representa a un usuario del sistema
class User {
    // Propiedades públicas del usuario
    public ?int $id;
    public string $email;
    public string $name;
    public string $password;
    public EUSER_ROLE $role;
    public int $points;

    /**
     * Constructor de la clase User
     * @param int|null $id ID del usuario
     * @param string|null $email Correo electrónico
     * @param string|null $name Nombre
     * @param string|null $password Contraseña
     * @param EUSER_ROLE|int|null $role Rol del usuario
     * @param int $points Puntos de fidelidad
     */
    public function __construct(
        ?int $id = null,
        ?string $email = null,
        ?string $name = null,
        ?string $password = null,
        EUSER_ROLE|int|null $role = null,
        int $points = 0
    ) {
        $this->id = $id;
        $this->email = $email ?? '';
        $this->name = $name ?? '';
        $this->password = $password ?? '';
        $this->role = $role instanceof EUSER_ROLE ? $role : ($role !== null ? EUSER_ROLE::from($role) : null);
        $this->points = $points;
    }

    /**
     * Guarda el usuario en la base de datos (insertar o actualizar)
     */
    public function save() {
        $data = [
            'email' => $this->email,
            'name' => $this->name,
            'password' => $this->password,
            'role' => $this->role instanceof EUSER_ROLE ? $this->role->value : $this->role,
            'points' => $this->points
        ];
        if ($this->id) {
            Connection::doUpdate(DBCONN, 'User', $data, ['id' => $this->id]);
        } else {
            $this->id = Connection::doInsert(DBCONN, 'User', $data);
        }
    }

    /**
     * Crea una instancia de User a partir de un array de datos
     * @param array $row Datos del usuario
     * @return User
     */
    public static function fromRow($row) {
        $role = isset($row['role']) ? (is_int($row['role']) ? EUSER_ROLE::from($row['role']) : $row['role']) : null;
        return new self(
            $row['id'],
            $row['email'],
            $row['name'],
            $row['password'],
            $role,
            isset($row['points']) ? $row['points'] : 0
        );
    }

    /**
     * Obtiene un usuario por su ID
     * @param int $id
     * @return User|null
     */
    public static function getById($id) {
        $row = Connection::doSelect(DBCONN, 'User', ['id' => $id]);
        return $row ? self::fromRow($row[0]) : null;
    }

    /**
     * Obtiene un usuario por su email
     * @param string $email
     * @return User|null
     */
    public static function getByEmail($email) {
        $rows = Connection::doSelect(DBCONN, 'User', ['email' => $email]);
        return $rows ? self::fromRow($rows[0]) : null;
    }

    /**
     * Obtiene todos los usuarios
     * @return array Lista de usuarios
     */
    public static function getAll() {
        $rows = Connection::doSelect(DBCONN, 'User');
        return array_map([self::class, 'fromRow'], $rows);
    }

    /**
     * Elimina el usuario de la base de datos
     */
    public function delete() {
        if ($this->id) {
            Connection::doDelete(DBCONN, 'User', ['id' => $this->id]);
        }
    }

    /**
     * Añade puntos de fidelidad al usuario y guarda el cambio en la base de datos.
     * @param int $points Cantidad de puntos a añadir (puede ser negativa para restar).
     */
    public function addPoints(int $points): void {
        $this->points += $points;
        if ($this->points < 0) {
            $this->points = 0;
        }
        $this->save();
    }
}