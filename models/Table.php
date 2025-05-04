<?php

class Table {
    public $id;
    public $notes;

    public function __construct($id, $notes = null) {
        $this->id = $id;
        $this->notes = $notes;
    }

    public function save() {
        $data = ['notes' => $this->notes];
        if ($this->id) {
            Connection::doUpdate(DBCONN, 'table', $data, ['id' => $this->id]);
        } else {
            Connection::doInsert(DBCONN, 'table', array_merge($data, ['id' => $this->id]));
        }
    }

    public static function fromRow($row) {
        return new self($row['id'], $row['notes']);
    }

    public static function getById($id) {
        $row = Connection::doSelect(DBCONN, 'table', ['id' => $id]);
        return $row ? self::fromRow($row[0]) : null;
    }

    /**
     * Retrieves all Table instances from the database.
     * @return array An array of Table objects.
     */
    public static function getAll() {
        $rows = Connection::doSelect(DBCONN, 'table');
        return array_map([self::class, 'fromRow'], $rows);
    }
}