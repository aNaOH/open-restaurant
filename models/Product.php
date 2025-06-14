<?php
// Clase que representa un producto en el sistema
class Product {
    // Propiedades públicas del producto
    public ?int $id;
    public string $name;
    public string $description;
    public float $price;
    public EPRODUCT_TYPE $type;
    public Category|int|null $category;
    public ?string $code;
    public ?int $points;

    /**
     * Constructor de la clase Product
     * @param int|null $id ID del producto
     * @param string $name Nombre del producto
     * @param string $description Descripción
     * @param float $price Precio
     * @param EPRODUCT_TYPE|int|null $type Tipo de producto
     * @param Category|int|null $category Categoría
     * @param string|null $code Código promocional
     * @param int|null $points Puntos de fidelidad
     */
    public function __construct(
        ?int $id = null,
        string $name = null,
        string $description = null,
        float $price = null,
        EPRODUCT_TYPE|int|null $type = null,
        Category|int|null $category = null,
        ?string $code = null,
        ?int $points = null
    ) {
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
        $this->code = $code;
        $this->points = $points;
    }

    /**
     * Guarda el producto en la base de datos (insertar o actualizar)
     */
    public function save() {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'type' => $this->type->value,
            'category' => $this->category instanceof Category ? $this->category->id : $this->category,
            'code' => $this->code,
            'points' => $this->points
        ];
        if ($this->id) {
            Connection::doUpdate(DBCONN, 'Product', $data, ['id' => $this->id]);
        } else {
            Connection::doInsert(DBCONN, 'Product', $data);
            $this->id = DBCONN->lastInsertId();
        }
    }

    /**
     * Devuelve la ruta de la imagen del producto si existe
     * @return string|null Ruta relativa o null si no existe
     */
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

    /**
     * Elimina el producto de la base de datos y sus relaciones
     */
    public function delete() {
        if ($this->id) {
            // Eliminar imagen del servidor
            $imagePath = $this->getImagePath();
            if ($imagePath && file_exists($imagePath)) {
                unlink($imagePath);
            }

            // Eliminar relaciones de hijos
            if($this->type === EPRODUCT_TYPE::COMPOSED || $this->type === EPRODUCT_TYPE::PROMOTION) {
                $this->removeAllChildren();
            }

            // Eliminar producto de aquellos que lo referencian
            $parentProducts = $this->getParentProducts();
            foreach ($parentProducts as $parentProduct) {
                $parentProduct['product']->removeChild('product', $this->id, $parentProduct['position']); // Eliminar relación de este producto en el padre
            }

            // Eliminar producto de la base de datos
            Connection::doDelete(DBCONN, 'Product', ['id' => $this->id]);
        }
    }

    /**
     * Devuelve los productos padres que contienen este producto como componente
     * @return array Lista de productos padres
     */
    public function getParentProducts(){
        $productRows = Connection::doSelect(DBCONN, 'ComposedBy', ['child_id' => $this->id]);
        $parentProducts = [];
        foreach ($productRows as $row) {
            $parentProduct = self::getById($row['product_id']);
            if ($parentProduct) {
                $parentProducts[] = [
                    'product' => $parentProduct,
                    'position' => (int)$row['position']
                ];
            }
        }
        return $parentProducts;
    } 

    /**
     * Devuelve todos los componentes (productos y categorías) de un producto compuesto, ordenados por posición
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
        
        // Ordenar por posición y agrupar en arrays si hay varios elementos en la misma posición
        ksort($byPosition);
        $result = [];
        foreach ($byPosition as $position => $items) {
            if (count($items) === 1) {
                // Si solo hay un elemento, lo devolvemos directamente
                $result[] = $items[0];
            } else {
                // Si hay varios, los agrupamos en un array
                $result[] = [
                    'type' => 'group',
                    'items' => $items,
                    'position' => (int)$position
                ];
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
    public function addCategoryChild($categoryId, $position = 0) {
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

    /**
     * Crea una instancia de Product a partir de un array de datos
     * @param array $row
     * @return Product
     */
    public static function fromRow($row) {
        // Convertir type a EPRODUCT_TYPE y category a Category si es necesario
        $type = EPRODUCT_TYPE::from($row['type']);
        $category = null;
        if (isset($row['category']) && $row['category'] !== null) {
            $category = Category::getById($row['category']);
        }

        return new self($row['id'], $row['name'], $row['description'], $row['price'], $type, $category, $row['code'], $row['points']);
    }

    /**
     * Obtiene un producto por su ID
     * @param int $id
     * @return Product|null
     */
    public static function getById($id) {
        $row = Connection::doSelect(DBCONN, 'Product', ['id' => $id]);
        return $row ? self::fromRow($row[0]) : null;
    }

    /**
     * Obtiene todos los productos
     * @return array Lista de productos
     */
    public static function getAll() {
        $rows = Connection::doSelect(DBCONN, 'Product');
        return array_map([self::class, 'fromRow'], $rows);
    }

    /**
     * Obtiene todos los productos de un tipo dado
     * @param EPRODUCT_TYPE|array $type
     * @return array Lista de productos
     */
    public static function getAllByType(EPRODUCT_TYPE|array $type) {
        if (is_array($type)) {
            $rows = [];
            foreach ($type as $t) {
                if (!$t instanceof EPRODUCT_TYPE) {
                    throw new InvalidArgumentException('All types in the array must be instances of EPRODUCT_TYPE.');
                }
                $typeValue = $t->value;
                $rows = array_merge($rows, Connection::doSelect(DBCONN, 'Product', ['type' => $typeValue]));

            }
        } else {
            $typeValue = $type instanceof EPRODUCT_TYPE ? $type->value : $type;
            $rows = Connection::doSelect(DBCONN, 'Product', ['type' => $typeValue]);
        }
        return array_map([self::class, 'fromRow'], $rows);
    }

    /**
     * Obtiene todos los productos de una categoría específica
     * @param int|Category $categoryId
     * @return array Lista de productos
     */
    public static function getByCategory($categoryId) {
        if ($categoryId instanceof Category) {
            $categoryId = $categoryId->id;
        }
        $rows = Connection::doSelect(DBCONN, 'Product', ['category' => $categoryId]);
        return array_map([self::class, 'fromRow'], $rows);
    }

    /**
     * Busca un producto promocional por código de promoción
     * @param string $code
     * @return Product|null
     */
    public static function getByPromoCode($code) {
        $rows = Connection::doSelect(DBCONN, 'Product', ['code' => $code, 'type' => EPRODUCT_TYPE::PROMOTION->value]);
        return $rows && count($rows) > 0 ? self::fromRow($rows[0]) : null;
    }

    /**
     * Busca un producto por su código promocional exacto
     * @param string $code
     * @return Product|null
     */
    public static function getByCode($code) {
        if (!$code) return null;
        $rows = Connection::doSelect(DBCONN, 'Product', ['code' => $code]);
        return $rows && count($rows) > 0 ? self::fromRow($rows[0]) : null;
    }
}