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

    /**
     * Retrieves all Product instances of a given type from the database.
     * @param EPRODUCT_TYPE $type
     * @return array An array of Product objects.
     */
    public static function getAllByType(EPRODUCT_TYPE $type) {
        $rows = Connection::doSelect(DBCONN, 'Product', ['type' => $type->value]);
        return array_map([self::class, 'fromRow'], $rows);
    }

    /**
     * Devuelve todos los componentes (productos y categorías) de un producto compuesto, ordenados por posición.
     * Si hay varios productos/categorías en la misma posición (por selección múltiple), agrupa en arrays de opciones.
     * Cada elemento es:
     *   - ['type' => 'product'|'category', 'object' => Product|Category, 'position' => int] (si solo hay uno en esa posición)
     *   - o un array de esos elementos (si hay varios en la misma posición)
     * @return array Lista de componentes ordenados y agrupados por posición
     */
    public function getChildren() {
        $productRows = Connection::doSelect(DBCONN, 'ComposedBy', ['product_id' => $this->id]);
        $categoryRows = Connection::doSelect(DBCONN, 'ComposedCategory', ['product_id' => $this->id]);
        $byPosition = [];
        foreach ($productRows as $row) {
            $prod = self::getById($row['child_id']);
            if ($prod) {
                $byPosition[$row['position']][] = [
                    'type' => 'product',
                    'object' => $prod,
                    'position' => (int)$row['position']
                ];
            }
        }
        foreach ($categoryRows as $row) {
            $cat = Category::getById($row['category_id']);
            if ($cat) {
                $byPosition[$row['position']][] = [
                    'type' => 'category',
                    'object' => $cat,
                    'position' => (int)$row['position']
                ];
            }
        }
        ksort($byPosition, SORT_NUMERIC);
        $result = [];
        foreach ($byPosition as $group) {
            if (count($group) === 1) {
                $result[] = $group[0];
            } else {
                $result[] = $group;
            }
        }
        return $result;
    }

    /**
     * Elimina todas las relaciones de hijos para este producto compuesto.
     */
    public function removeAllChildren() {
        Connection::doDelete(DBCONN, 'ComposedBy', ['product_id' => $this->id]);
        Connection::doDelete(DBCONN, 'ComposedCategory', ['product_id' => $this->id]);
    }

    /**
     * Añade una relación de hijo producto a este producto compuesto, con posición.
     * @param int $childId
     * @param int $position
     */
    public function addChild($childId, $position = 0) {
        Connection::doInsert(DBCONN, 'ComposedBy', [
            'product_id' => $this->id,
            'child_id' => $childId,
            'position' => $position
        ]);
    }

    /**
     * Añade una relación de hijo categoría a este producto compuesto, con posición.
     * @param int $categoryId
     * @param int $position
     */
    public function addChildCategory($categoryId, $position = 0) {
        Connection::doInsert(DBCONN, 'ComposedCategory', [
            'product_id' => $this->id,
            'category_id' => $categoryId,
            'position' => $position
        ]);
    }

    /**
     * Elimina un componente hijo (producto o categoría) de este producto compuesto en una posición concreta.
     * @param string $type 'product' o 'category'
     * @param int $id ID del producto o categoría
     * @param int $position Posición del componente
     */
    public function removeChild($type, $id, $position) {
        if ($type === 'product') {
            Connection::doDelete(DBCONN, 'ComposedBy', [
                'product_id' => $this->id,
                'child_id' => $id,
                'position' => $position
            ]);
        } elseif ($type === 'category') {
            Connection::doDelete(DBCONN, 'ComposedCategory', [
                'product_id' => $this->id,
                'category_id' => $id,
                'position' => $position
            ]);
        }
    }
}