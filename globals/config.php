<?php

class Config {
    public $DB_HOST = '';
    public $DB_NAME = '';
    public $DB_USER = '';
    public $DB_PASS = '';

    public $ADMIN_USER = '';
    public $ADMIN_PASS = '';
    public $ADMIN_EMAIL = '';

    public $RESTAURANT_NAME = '';
    public $RESTAURANT_ADDRESS = '';
    public $RESTAURANT_PHONE = '';
    public $RESTAURANT_EMAIL = '';
    //public $RESTAURANT_CURRENCY = '';
    //public $RESTAURANT_CURRENCY_SYMBOL = '';
    //public $RESTAURANT_CURRENCY_POSITION = 'left'; // left or right
    //public $RESTAURANT_CURRENCY_DECIMALS = 2;
    //public $RESTAURANT_CURRENCY_DECIMAL_SEPARATOR = '.'; // . or ,
    //public $RESTAURANT_CURRENCY_THOUSAND_SEPARATOR = ','; // . or ,

    public $DISCOUNT_ENABLED;
    public $FIDELITY_ENABLED;
    public $POINTS_PER_UNIT;

    public $INSTALL_FINISHED = false;

    // Check if the application is installed, check if the database name and user are set
    public function isInstalled() {
        return ( !empty($this->DB_NAME) && !empty($this->DB_USER) ) && $this->INSTALL_FINISHED == true;
    }

    public function __construct() {
        if(!file_exists('./config.json')) {
            return;
        }
        $content = file_get_contents('./config.json');
        $config = json_decode($content, true);
        
        if ($config) {
            $this->DB_HOST = $config['database']['host'];
            $this->DB_NAME = $config['database']['name'];
            $this->DB_USER = $config['database']['user'];
            $this->DB_PASS = $config['database']['pass'];

            if(isset($config['admin'])) {
                $this->ADMIN_USER = isset($config['admin']['user']) ? $config['admin']['user'] : 'admin';
                $this->ADMIN_PASS = isset($config['admin']['pass']) ? $config['admin']['pass'] : 'admin';
            }

            if(isset($config['restaurant'])) {
                $this->RESTAURANT_NAME = isset($config['restaurant']['name']) ? $config['restaurant']['name'] : 'Restaurant Name';
                $this->RESTAURANT_ADDRESS = isset($config['restaurant']['address']) ? $config['restaurant']['address'] : 'Restaurant Address';
                $this->RESTAURANT_PHONE = isset($config['restaurant']['phone']) ? $config['restaurant']['phone'] : 'Restaurant Phone';
                $this->RESTAURANT_EMAIL = isset($config['restaurant']['email']) ? $config['restaurant']['email'] : 'Restaurant Email';
            } 

            if(isset($config['features'])) {
                $this->DISCOUNT_ENABLED = isset($config['features']['discountEnabled']) ? $config['features']['discountEnabled'] : false;
                $this->FIDELITY_ENABLED = isset($config['features']['fidelityEnabled']) ? $config['features']['fidelityEnabled'] : false;
            }

            $this->INSTALL_FINISHED = isset($config['installFinished']) ? $config['installFinished'] : false;
        }
    }

    public function save() {
        $config = [
            'database' => [
                'host' => $this->DB_HOST,
                'name' => $this->DB_NAME,
                'user' => $this->DB_USER,
                'pass' => $this->DB_PASS
            ],
            'admin' => [
                'user' => $this->ADMIN_USER,
                'pass' => $this->ADMIN_PASS,
                'email' => $this->ADMIN_EMAIL
            ],
            'restaurant' => [
                'name' => $this->RESTAURANT_NAME,
                'address' => $this->RESTAURANT_ADDRESS,
                'phone' => $this->RESTAURANT_PHONE,
                'email' => $this->RESTAURANT_EMAIL,
            ],
            'features' => [
                'discountEnabled' => $this->DISCOUNT_ENABLED,
                'fidelityEnabled' => $this->FIDELITY_ENABLED,
                'pointsPerUnit' => $this->POINTS_PER_UNIT
            ],
            'installFinished' => $this->INSTALL_FINISHED
        ];

        $content = json_encode($config);
        file_put_contents('./config.json', $content);
    }
}

$config = new Config();