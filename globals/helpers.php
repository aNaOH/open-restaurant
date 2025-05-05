<?php

class AuthHelpers {

    public static function isLoggedIn() {
        return isset($_SESSION['admin']);
    }

    public static function isAdmin() {
        if(!self::isLoggedIn()) {
            return false;
        }
    
        $session = $_SESSION['admin'];
        if(!isset($session['username']) || !isset($session['password'])) {
            session_destroy();
            return false;
        }
    
        if($session['username'] != CONFIG->ADMIN_USER || $session['password'] != CONFIG->ADMIN_PASS) {
            session_destroy();
            return false;
        }

        return true;
    }
}

class SidebarHelpers {

    public static function getBaseData(){
        
        if(!AuthHelpers::isAdmin()) {
            return [
                'showUsers' => false,
                'showPromotional' => false,
            ];
        }

        $showUsers = CONFIG->FIDELITY_ENABLED;
        $showPromotional = (CONFIG->FIDELITY_ENABLED || CONFIG->DISCOUNT_ENABLED);

        return [
            'showUsers' => $showUsers,
            'showPromotional' => $showPromotional,
        ];
    }
}