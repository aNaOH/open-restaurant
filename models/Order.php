<?php
// Clase que representa un pedido en el sistema
class Order {
    // Propiedades públicas del pedido
    public ?int $id = null;
    public ?string $table_id = null;
    public ?int $user = null;
    public ?string $stripe_id = null;
    public ?string $date = null;
    public ?string $time = null;

    /**
     * Constructor de la clase Order
     * @param int|null $id ID del pedido
     * @param string|null $table_id ID de la mesa
     * @param int|null $user ID del usuario
     * @param string|null $stripe_id ID de Stripe
     * @param string|null $date Fecha del pedido
     * @param string|null $time Hora del pedido
     */
    public function __construct(?int $id = null, ?string $table_id = null, ?int $user = null, ?string $stripe_id = null, ?string $date = null, ?string $time = null) {
        $this->id = $id;
        $this->table_id = $table_id;
        $this->user = $user;
        $this->stripe_id = $stripe_id;
        $this->date = $date;
        $this->time = $time;
    }

    /**
     * Guarda el pedido en la base de datos (insertar o actualizar)
     */
    public function save() {
        $data = [
            'table_id' => $this->table_id,
            'user' => $this->user,
            'stripe_id' => $this->stripe_id,
            'date' => $this->date ?? date('Y-m-d'),
            'time' => $this->time ?? date('Y-m-d H:i:s')
        ];
        // Elimina claves con valor NULL para evitar errores SQL
        $data = array_filter($data, function($v) { return $v !== null; });
        if ($this->id) {
            Connection::doUpdate(DBCONN, 'Orders', $data, ['id' => $this->id]);
        } else {
            Connection::doInsert(DBCONN, 'Orders', $data);
            $this->id = DBCONN->lastInsertId();
        }
    }

    /**
     * Crea una instancia de Order a partir de un array de datos
     * @param array $row
     * @return Order
     */
    public static function fromRow($row) {
        return new self(
            $row['id'] ?? null,
            $row['table_id'] ?? null,
            $row['user'] ?? null,
            $row['stripe_id'] ?? null,
            $row['date'] ?? null,
            $row['time'] ?? null
        );
    }

    /**
     * Obtiene un pedido por su ID
     * @param int $id
     * @return Order|null
     */
    public static function getById($id) {
        $rows = Connection::doSelect(DBCONN, 'Orders', ['id' => $id]);
        return $rows ? self::fromRow($rows[0]) : null;
    }

    /**
     * Obtiene todos los pedidos ordenados del más reciente al más antiguo
     * @return array Lista de pedidos
     */
    public static function getAll() {
        $rows = Connection::doSelect(DBCONN, 'Orders', [], ['order' => 'date DESC, time DESC']);
        return array_map([self::class, 'fromRow'], $rows);
    }

    /**
     * Obtiene los pedidos por fecha
     * @param string $date
     * @return array Lista de pedidos
     */
    public static function getByDate($date) {
        $rows = Connection::doSelect(DBCONN, 'Orders', ['date' => $date], ['order' => 'time DESC']);
        return array_map([self::class, 'fromRow'], $rows);
    }

    /**
     * Obtiene los pedidos del día actual
     * @return array Lista de pedidos
     */
    public static function getToday() {
        $today = date('Y-m-d');
        return self::getByDate($today);
    }

    /**
     * Añade un producto al pedido
     * @param int $product_id
     * @param float $price
     * @param int $quantity
     * @param array|null $metadata
     */
    public function addProduct($product_id, $price, $quantity, $metadata = null) {

        $index = count($this->getProducts());

        $data = [
            'order_id' => $this->id, // Escapa el nombre de la columna reservada
            'index' => $index, // Añade el índice para mantener el orden
            'product' => $product_id,
            'price' => $price,
            'quantity' => $quantity,
            'metadata' => $metadata ? json_encode($metadata) : null
        ];
        Connection::doInsert(DBCONN, 'OrderContains', $data);
    }

    /**
     * Obtiene los productos del pedido
     * @return array Lista de productos
     */
    public function getProducts() {
        $rows = Connection::doSelect(DBCONN, 'OrderContains', ['order_id' => $this->id]);
        $products = [];
        foreach ($rows as $row) {
            $product = Product::getById($row['product']);
            $products[] = [
                'product_id' => $row['product'],
                'quantity' => $row['quantity'],
                'price' => $row['price'],
                'metadata' => $row['metadata'] ? json_decode($row['metadata'], true) : null,
                'done' => (bool)$row['done'],
                'product_snapshot' => $product ? [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'points' => $product->points,
                    'image' => $product->getImagePath(),
                    'code' => $product->code,
                    'type' => $product->type->value,
                ] : null
            ];
        }
        return $products;
    }

    /**
     * Calcula el total del pedido
     * @return float Total
     */
    public function getTotal() {
        $rows = Connection::doSelect(DBCONN, 'OrderContains', ['order_id' => $this->id]);
        $total = 0;
        foreach ($rows as $row) {
            $total += floatval($row['price']) * intval($row['quantity']);
        }
        return $total;
    }

    /**
     * Devuelve el offset horario configurado
     * @return float Horas de diferencia
     */
    public static function getTimezoneOffsetHours() {
        global $config;
        $tz = new \DateTimeZone($config->TIMEZONE);
        $now = new \DateTime('now', $tz);
        return $tz->getOffset($now) / 3600;
    }

    /**
     * Actualiza el estado de un producto en el pedido
     * @param int $product_id
     * @param bool $status
     * @return bool
     */
    public function updateProductStatus($product_id, $status) {
        // Only update if the product exists in this order
        $rows = Connection::doSelect(DBCONN, 'OrderContains', [
            'order_id' => $this->id,
            'product' => $product_id
        ]);
        if ($rows && count($rows) > 0) {
            Connection::doUpdate(DBCONN, 'OrderContains', [
                'done' => $status ? 1 : 0 // Forzar 1 o 0 explícitamente
            ], [
                'order_id' => $this->id,
                'product' => $product_id
            ]);
            return true;
        }
        return false;
    }

    /**
     * Obtiene la cantidad de productos completados
     * @return int
     */
    public function getCompletedProducts() {
        $rows = Connection::doSelect(DBCONN, 'OrderContains', [
            'order_id' => $this->id,
            'done' => true
        ]);
        return count($rows);
    }

    /**
     * Obtiene la cantidad de productos pendientes
     * @return int
     */
    public function getPendingProducts() {
        $rows = Connection::doSelect(DBCONN, 'OrderContains', [
            'order_id' => $this->id,
            'done' => false
        ]);
        return count($rows);
    }

    /**
     * Devuelve el estado general del pedido
     * @return string Estado ('empty', 'completed', 'pending', 'not_started')
     */
    public function getStatus() {
        $completed = $this->getCompletedProducts();
        $pending = $this->getPendingProducts();
        $total = count($this->getProducts());
        if ($total === 0) {
            return 'empty';
        } elseif ($completed > 0 && $pending === 0) {
            return 'completed';
        } elseif ($pending > 0 && $completed > 0) {
            return 'pending';
        } elseif ($pending === $total) {
            return 'not_started';
        }
        return 'empty';
    }
}
