<?php

class User {
    public ?int $id;
    public string $email;
    public string $name;
    public string $password;
    public EUSER_ROLE $role;
    public int $points;

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

    public static function getById($id) {
        $row = Connection::doSelect(DBCONN, 'User', ['id' => $id]);
        return $row ? self::fromRow($row[0]) : null;
    }

    /**
     * Retrieves a User instance by email.
     * @param string $email The email of the user.
     * @return User|null The User object if found, null otherwise.
     */
    public static function getByEmail($email) {
        $rows = Connection::doSelect(DBCONN, 'User', ['email' => $email]);
        return $rows ? self::fromRow($rows[0]) : null;
    }

    /**
     * Retrieves all User instances from the database.
     * @return array An array of User objects.
     */
    public static function getAll() {
        $rows = Connection::doSelect(DBCONN, 'User');
        return array_map([self::class, 'fromRow'], $rows);
    }

    public function delete() {
        if ($this->id) {
            Connection::doDelete(DBCONN, 'User', ['id' => $this->id]);
        }
    }
}