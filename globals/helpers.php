<?php
// Clase de utilidades generales
class Helpers {
    /**
     * Obtiene la URL base de la aplicación
     * @return string
     */
    public static function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $path = dirname($script);

        // Reemplaza las barras invertidas por barras normales y elimina la barra final
        $baseUrl = $protocol . $host . str_replace('\\', '/', $path);
        return rtrim($baseUrl, '/');
    }
}

// Clase de utilidades para autenticación y roles
class AuthHelpers {
    /**
     * Verifica si el usuario está logueado
     * @return bool
     */
    public static function isLoggedIn() {

        if(!isset($_SESSION['user'])) {
            return false;
        }

        $session = $_SESSION['user'];
        if(!isset($session['email']) || !isset($session['id']) || !isset($session['name'])) {
            session_destroy();
            return false;
        }
    
        //Check if user exists in the database
        $user = User::getById($session['id']);
        if(!$user) {
            session_destroy();
            return false;
        }

        return true;
    }

    /**
     * Verifica si el usuario es administrador
     * @return bool
     */
    public static function isAdmin() {
        if(!self::isLoggedIn()) {
            return false;
        }

        $session = $_SESSION['user'];
        $user = User::getById($session['id']);
    
        //Check if user is admin
        return ($user->role == EUSER_ROLE::ADMIN);
    }

    /**
     * Verifica si el usuario es empleado o administrador
     * @return bool
     */
    public static function isEmployee() {
        if(!self::isLoggedIn()) {
            return false;
        }

        $session = $_SESSION['user'];
        $user = User::getById($session['id']);

        return ($user->role == EUSER_ROLE::EMPLOYEE || $user->role == EUSER_ROLE::ADMIN);
    }
}

// Clase de utilidades para vistas (datos base para Twig)
class ViewHelpers {
    /**
     * Devuelve los datos base para las vistas Twig
     * @return array
     */
    public static function getBaseData(){
        $fidelityEnabled = CONFIG->FIDELITY_ENABLED;
        $promosEnabled = (CONFIG->FIDELITY_ENABLED || CONFIG->DISCOUNT_ENABLED);

        if(AuthHelpers::isLoggedIn()) {
            $user = User::getById($_SESSION['user']['id']);
        }

        // Calculate isHomePage based on the current request URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $isHomePage = ($requestUri === '/' || preg_match('/^\/\?(.*)$/', $requestUri));

        return [
            'fidelityEnabled' => $fidelityEnabled,
            'promosEnabled' => $promosEnabled,
            'user' => isset($user) ? $user : null,
            'restaurantName' => CONFIG->RESTAURANT_NAME,
            'isHomePage' => $isHomePage
        ];
    }
}

// Clase de utilidades para pedidos
class OrderHelpers {
    /**
     * Inicia un pedido en la sesión
     * @param string $table
     */
    public static function beginOrder($table){
        // Create a new order with the given table, stored in session, expires in 30 minutes
        $_SESSION['order'] = [
            'table' => $table,
            'items' => [],
            'status' => 'open',
            'created_at' => time()
        ];
    }

    /**
     * Obtiene el pedido actual de la sesión
     * @return array|null
     */
    public static function getOrder() {
        if(!isset($_SESSION['order'])) {
            return null;
        }

        $order = $_SESSION['order'];
        if(!$order || !isset($order['table']) || !isset($order['items']) || !isset($order['status']) || !isset($order['created_at'])) {
            return null;
        }

        // Check if the order is older than 30 minutes
        if(time() - $order['created_at'] > (30 * 60)) {
            unset($_SESSION['order']);
            return null;
        }

        // Asegura que 'promos' siempre esté presente y sea un array asociativo
        if (!isset($order['promos']) || !is_array($order['promos'])) {
            $order['promos'] = [];
        }

        // Actualiza la sesión si fue necesario
        $_SESSION['order'] = $order;

        return $order;
    }

    public static function addItemToOrder($productId, $metadata, $quantity = 1){
        if(!isset($_SESSION['order'])) {
            return false;
        }
        $order = $_SESSION['order'];
        if(!$order || !isset($order['items'])) {
            return false;
        }
        // Check if the product already exists in the order
        foreach($order['items'] as &$item) {
            if($item['product_id'] == $productId && $item['metadata'] == $metadata) {
                $item['quantity'] += $quantity;
                $_SESSION['order'] = $order;
                return true;
            }
        }
        // If not found, add new item
        $product = Product::getById($productId);
        $order['items'][] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'metadata' => $metadata,
            'product_snapshot' => $product ? [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'points' => (isset($metadata['used_points']) && $metadata['used_points']) ? $product->points : null,
                'image' => $product->getImagePath(),
                'code' => $product->code,
                'type' => $product->type->value,
            ] : null
        ];
        $_SESSION['order'] = $order;
        return true;
    }

    public static function removeItemFromOrder($productId, $metadata, $quantity = 1){
        if(!isset($_SESSION['order'])) {
            return false;
        }
        $order = $_SESSION['order'];
        if(!$order || !isset($order['items'])) {
            return false;
        }
        // Check if the product exists in the order
        foreach($order['items'] as $key => &$item) {
            if($item['product_id'] == $productId && $item['metadata'] == $metadata) {
                if($item['quantity'] > $quantity) {
                    $item['quantity'] -= $quantity;
                } else {
                    unset($order['items'][$key]);
                }
                $_SESSION['order'] = $order;
                return true;
            }
        }
        return false;
    }

    public static function clearOrder() {
        if(self::getOrder() !== null) {
            unset($_SESSION['order']);
        }
    }

    public static function addPromoToOrder($promoCode) {
        if(!isset($_SESSION['order'])) {
            return false;
        }
        $order = $_SESSION['order'];
        if(!$order || !isset($order['promos']) || !is_array($order['promos'])) {
            $order['promos'] = [];
        }
        // Check if the promo already exists in the order (clave asociativa)
        if(array_key_exists($promoCode, $order['promos'])) {
            return false;
        }
        // Check if the promo code is valid
        $product = Product::getByCode($promoCode);
        if(!$product || $product->type != EPRODUCT_TYPE::PROMOTION) {
            return false; // Invalid promo code
        }
        // Guardar snapshot del producto en vez de solo el código
        $order['promos'][$promoCode] = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'points' => $product->points,
            'image' => $product->getImagePath(),
            'code' => $product->code,
            'type' => $product->type->value,
        ];
        $_SESSION['order'] = $order;
        return true;
    }

    public static function removePromoFromOrder($promoCode) {
        if(!isset($_SESSION['order'])) {
            return false;
        }
        $order = $_SESSION['order'];
        if(!$order || !isset($order['promos'])) {
            return false;
        }
        // Check if the promo exists in the order (clave asociativa)
        if(array_key_exists($promoCode, $order['promos'])) {
            unset($order['promos'][$promoCode]);
            $_SESSION['order'] = $order;
            return true;
        }
        return false;
    }

    public static function hasProductInOrder($productId) {
        if (!isset($_SESSION['order']) || !isset($_SESSION['order']['items'])) {
            return false;
        }
        foreach ($_SESSION['order']['items'] as $item) {
            if ($item['product_id'] == $productId) {
                return true;
            }
        }
        return false;
    }

    public static function hasPromoInOrder($promoCode) {
        if (!isset($_SESSION['order']) || !isset($_SESSION['order']['promos'])) {
            return false;
        }
        return array_key_exists($promoCode, $_SESSION['order']['promos']);
    }
}