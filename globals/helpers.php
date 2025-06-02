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