<?php

class AuthHelpers {

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

    public static function isAdmin() {
        if(!self::isLoggedIn()) {
            return false;
        }

        $session = $_SESSION['user'];
        $user = User::getById($session['id']);
    
        //Check if user is admin
        if($user->role != EUSER_ROLE::ADMIN) {
            return false;
        }

        return true;
    }
}

class SidebarHelpers {

    public static function getBaseData(){
        
        $fidelityEnabled = CONFIG->FIDELITY_ENABLED;
        $promosEnabled = (CONFIG->FIDELITY_ENABLED || CONFIG->DISCOUNT_ENABLED);

        if(AuthHelpers::isLoggedIn()) {
            $user = User::getById($_SESSION['user']['id']);
        }

        return [
            'fidelityEnabled' => $fidelityEnabled,
            'promosEnabled' => $promosEnabled,
            'user' => isset($user) ? $user : null,
            'restaurantName' => CONFIG->RESTAURANT_NAME
        ];
    }
}

class OrderHelpers {
    public static function beginOrder($table){
        // Create a new order with the given table, stored in session, expires in 30 minutes
        $_SESSION['order'] = [
            'table' => $table,
            'items' => [],
            'status' => 'open',
            'created_at' => time()
        ];
    }

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
        $order['items'][] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'metadata' => $metadata
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
}