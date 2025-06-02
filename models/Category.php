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
            Connection::doInsert(DBCONN, 'Category', $data);
            $this->id = DBCONN->lastInsertId();
        }
    }

    public function getImagePath() {
        if ($this->id) {
            $uploadDir = 'assets/uploads/categories/';
            $imagePath = $uploadDir . 'category_' . $this->id;
            $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            foreach ($extensions as $ext) {
                if (file_exists($imagePath . '.' . $ext)) {
                    return $imagePath . '.' . $ext; // Return the URL relative to the root
                }
            }

        }
        return null;
    }

    public function getProducts() {
        if ($this->id) {
            $rows = Connection::doSelect(DBCONN, 'Product', ['category' => $this->id]);
            return array_map([Product::class, 'fromRow'], $rows);
        }
        return [];
    }

    public function delete() {
        if ($this->id) {
            Connection::doDelete(DBCONN, 'Category', ['id' => $this->id]);
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