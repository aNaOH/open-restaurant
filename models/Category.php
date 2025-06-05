<?php
// Clase que representa una categoría de productos
class Category {
    // Propiedades públicas de la categoría
    public ?int $id;
    public string $name;

    /**
     * Constructor de la clase Category
     * @param int|null $id ID de la categoría
     * @param string|null $name Nombre de la categoría
     */
    public function __construct(?int $id = null, string $name = null) {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * Guarda la categoría en la base de datos (insertar o actualizar)
     */
    public function save() {
        $data = ['name' => $this->name];
        if ($this->id) {
            Connection::doUpdate(DBCONN, 'Category', $data, ['id' => $this->id]);
        } else {
            Connection::doInsert(DBCONN, 'Category', $data);
            $this->id = DBCONN->lastInsertId();
        }
    }

    /**
     * Devuelve la ruta de la imagen de la categoría si existe
     * @return string|null Ruta relativa o null si no existe
     */
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

    /**
     * Obtiene los productos de esta categoría
     * @return array Lista de productos
     */
    public function getProducts() {
        if ($this->id) {
            $rows = Connection::doSelect(DBCONN, 'Product', ['category' => $this->id]);
            return array_map([Product::class, 'fromRow'], $rows);
        }
        return [];
    }

    /**
     * Devuelve los productos padres que contienen esta categoría como componente
     * @return array Lista de productos padres
     */
    public function getParentProducts(){
        $productRows = Connection::doSelect(DBCONN, 'ComposedCategory', ['category_id' => $this->id]);
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
     * Elimina la categoría de la base de datos y actualiza productos relacionados
     */
    public function delete() {
        if ($this->id) {
            // Eliminar imagen si existe
            $imagePath = 'assets/uploads/categories/category_' . $this->id;
            $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            foreach ($extensions as $ext) {
                if (file_exists($imagePath . '.' . $ext)) {
                    unlink($imagePath . '.' . $ext);
                }
            }

            // Cambiar la categoría de los productos a "Sin categoría"
            $products = $this->getProducts();

            foreach ($products as $product) {
                if ($product->category instanceof Category) {
                    $product->category = null; // Set to null or a default category
                    $product->save();
                }
            }

            $parentProducts = $this->getParentProducts();
            foreach ($parentProducts as $parentProduct) {
                $parentProduct['product']->removeChild('category', $this->id, $parentProduct['position']); // Eliminar relación de esta categoría en el padre
            }

            Connection::doDelete(DBCONN, 'category', ['id' => $this->id]);
        }
    }

    /**
     * Crea una instancia de Category a partir de un array de datos
     * @param array $row
     * @return Category
     */
    public static function fromRow($row) {
        return new self($row['id'], $row['name']);
    }

    /**
     * Obtiene una categoría por su ID
     * @param int $id
     * @return Category|null
     */
    public static function getById($id) {
        $row = Connection::doSelect(DBCONN, 'Category', ['id' => $id]);
        return $row ? self::fromRow($row[0]) : null;
    }

    /**
     * Obtiene todas las categorías
     * @return array Lista de categorías
     */
    public static function getAll() {
        $rows = Connection::doSelect(DBCONN, 'Category');
        return array_map([self::class, 'fromRow'], $rows);
    }
}