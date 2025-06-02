<?php

class Product {
    public $id;
    public $name;
    public $description;
    public $price;
    public EPRODUCT_TYPE $type;
    public $category;

    public function __construct($id = null, $name = null, $description = null, $price = null, $type = null, $category = null) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        // Ensure type is an instance of EPRODUCT_TYPE enum, if it's an int, convert it to the enum type
        if ($type === null) {
            $type = EPRODUCT_TYPE::STANDARD; // Default type if none provided
        }
        if (is_int($type)) {
            $type = EPRODUCT_TYPE::from($type);
        } else if (!$type instanceof EPRODUCT_TYPE) {
            throw new InvalidArgumentException('Invalid type provided for Product.');
        }

        $this->type = $type;
        $this->category = $category;
    }

    public function save() {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'type' => $this->type->value,
            'category' => $this->category instanceof Category ? $this->category->id : $this->category
        ];
        if ($this->id) {
            Connection::doUpdate(DBCONN, 'Product', $data, ['id' => $this->id]);
        } else {
            Connection::doInsert(DBCONN, 'Product', $data);
            $this->id = DBCONN->lastInsertId();
        }
    }

    public function getImagePath() {
        if ($this->id) {
            $uploadDir = 'assets/uploads/products/';
            $imagePath = $uploadDir . 'product_' . $this->id;
            $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            foreach ($extensions as $ext) {
                if (file_exists($imagePath . '.' . $ext)) {
                    return $imagePath . '.' . $ext; // Return the URL relative to the root
                }
            }

        }
        return null;
    }


    public static function fromRow($row) {
        // Convertir type a EPRODUCT_TYPE y category a Category si es necesario
        $type = EPRODUCT_TYPE::from($row['type']);
        $category = null;
        if (isset($row['category']) && $row['category'] !== null) {
            $category = Category::getById($row['category']);
        }

        return new self($row['id'], $row['name'], $row['description'], $row['price'], $type, $category);
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