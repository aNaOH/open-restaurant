<?php
// Clase que representa una mesa en el restaurante
class Table {
    // Propiedades públicas de la mesa
    public int $id;
    public ?string $notes;

    /**
     * Constructor de la clase Table
     * @param int $id ID de la mesa
     * @param string|null $notes Notas asociadas a la mesa
     */
    public function __construct(int $id, ?string $notes = null) {
        $this->id = $id;
        $this->notes = $notes;
    }

    /**
     * Guarda la mesa en la base de datos (insertar o actualizar)
     */
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

    /**
     * Elimina la mesa de la base de datos
     */
    public function delete() {
        if ($this->id) {
            Connection::doDelete(DBCONN, 'table', ['id' => $this->id]);
        }
    }

    /**
     * Crea una instancia de Table a partir de un array de datos
     * @param array $row
     * @return Table
     */
    public static function fromRow($row) {
        return new self($row['id'], $row['notes']);
    }

    /**
     * Obtiene una mesa por su ID
     * @param int $id
     * @return Table|null
     */
    public static function getById($id) {
        $row = Connection::doSelect(DBCONN, 'table', ['id' => $id]);
        return $row ? self::fromRow($row[0]) : null;
    }

    /**
     * Obtiene todas las mesas
     * @return array Lista de mesas
     */
    public static function getAll() {
        $rows = Connection::doSelect(DBCONN, 'table');
        return array_map([self::class, 'fromRow'], $rows);
    }
}