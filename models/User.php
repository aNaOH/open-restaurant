<?php

class User {
    public $id;
    public $email;
    public $name;
    public $password;
    public $points;

    public function __construct($id = null, $email = null, $name = null, $password = null, $points = 0) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->password = $password;
        $this->points = $points;
    }

    public function save() {
        $data = [
            'email' => $this->email,
            'name' => $this->name,
            'password' => $this->password,
            'points' => $this->points
        ];
        if ($this->id) {
            Connection::doUpdate(DBCONN, 'User', $data, ['id' => $this->id]);
        } else {
            $this->id = Connection::doInsert(DBCONN, 'User', $data);
        }
    }

    public static function fromRow($row) {
        return new self($row['id'], $row['email'], $row['name'], $row['password'], $row['points']);
    }

    public static function getById($id) {
        $row = Connection::doSelect(DBCONN, 'User', ['id' => $id]);
        return $row ? self::fromRow($row[0]) : null;
    }

    /**
     * Retrieves all User instances from the database.
     * @return array An array of User objects.
     */
    public static function getAll() {
        $rows = Connection::doSelect(DBCONN, 'User');
        return array_map([self::class, 'fromRow'], $rows);
    }
}