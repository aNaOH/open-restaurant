<?php

class Category {
    public $id;
    public $name;

    public function __construct($id = null, $name = null) {
        $this->id = $id;
        $this->name = $name;
    }

    public function save() {
        $data = ['name' => $this->name];
        if ($this->id) {
            Connection::doUpdate(DBCONN, 'Category', $data, ['id' => $this->id]);
        } else {
            $this->id = Connection::doInsert(DBCONN, 'Category', $data);
        }
    }

    public static function fromRow($row) {
        return new self($row['id'], $row['name']);
    }

    public static function getById($id) {
        $row = Connection::doSelect(DBCONN, 'Category', ['id' => $id]);
        return $row ? self::fromRow($row[0]) : null;
    }

    /**
     * Retrieves all Category instances from the database.
     * @return array An array of Category objects.
     */
    public static function getAll() {
        $rows = Connection::doSelect(DBCONN, 'Category');
        return array_map([self::class, 'fromRow'], $rows);
    }
}