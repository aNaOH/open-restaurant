<?php

class Product {
    public $id;
    public $name;
    public $description;
    public $price;
    public $type;
    public $category;

    public function __construct($id = null, $name = null, $description = null, $price = null, $type = null, $category = null) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->type = $type;
        $this->category = $category;
    }

    public function save() {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'type' => $this->type,
            'category' => $this->category
        ];
        if ($this->id) {
            Connection::doUpdate(DBCONN, 'Product', $data, ['id' => $this->id]);
        } else {
            $this->id = Connection::doInsert(DBCONN, 'Product', $data);
        }
    }

    public static function fromRow($row) {
        return new self($row['id'], $row['name'], $row['description'], $row['price'], $row['type'], $row['category']);
    }

    public static function getById($id) {
        $row = Connection::doSelect(DBCONN, 'Product', ['id' => $id]);
        return $row ? self::fromRow($row[0]) : null;
    }

    /**
     * Retrieves all Product instances from the database.
     * @return array An array of Product objects.
     */
    public static function getAll() {
        $rows = Connection::doSelect(DBCONN, 'Product');
        return array_map([self::class, 'fromRow'], $rows);
    }
}