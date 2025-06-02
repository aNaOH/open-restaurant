<?php

class Table {
    public int $id;
    public ?string $notes;

    public function __construct(int $id, ?string $notes = null) {
        $this->id = $id;
        $this->notes = $notes;
    }

    public function save() {
        $data = ['notes' => $this->notes];

        // Verificar si el registro ya existe en la base de datos
        $existingRecord = Connection::doSelect(DBCONN, 'table', ['id' => $this->id]);

        if ($existingRecord) {
            // Si el registro existe, realizar un UPDATE
            Connection::doUpdate(DBCONN, 'table', $data, ['id' => $this->id]);
        } else {
            // Si el registro no existe, realizar un INSERT
            $data['id'] = $this->id; // Agregar el ID al conjunto de datos
            Connection::doInsert(DBCONN, 'table', $data);
        }
    }

    public function delete() {
        if ($this->id) {
            Connection::doDelete(DBCONN, 'table', ['id' => $this->id]);
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