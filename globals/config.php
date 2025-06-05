<?php
// Clase de configuración global de la aplicación
class Config {
    // Propiedades de conexión a la base de datos
    public $DB_HOST = '';
    public $DB_NAME = '';
    public $DB_USER = '';
    public $DB_PASS = '';

    // Propiedades de información del restaurante
    public $RESTAURANT_NAME = '';
    public $RESTAURANT_ADDRESS = '';
    public $RESTAURANT_PHONE = '';
    public $RESTAURANT_EMAIL = '';

    // Propiedades de características
    public $DISCOUNT_ENABLED;
    public $FIDELITY_ENABLED;
    public $POINTS_PER_UNIT;

    // Estado de instalación
    public $INSTALL_FINISHED = false;

    // Claves de Stripe
    public $STRIPE_PUBLIC_KEY = '';
    public $STRIPE_SECRET_KEY = '';

    // Zona horaria
    public $TIMEZONE = 'Europe/Madrid';

    /**
     * Verifica si la aplicación está instalada correctamente
     * @return bool
     */
    public function isInstalled() {
        return ( !empty($this->DB_NAME) && !empty($this->DB_USER) ) && $this->INSTALL_FINISHED == true;
    }

    /**
     * Constructor: carga la configuración desde config.json si existe
     */
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

            if(isset($config['restaurant'])) {
                $this->RESTAURANT_NAME = isset($config['restaurant']['name']) ? $config['restaurant']['name'] : 'Restaurant Name';
                $this->RESTAURANT_ADDRESS = isset($config['restaurant']['address']) ? $config['restaurant']['address'] : 'Restaurant Address';
                $this->RESTAURANT_PHONE = isset($config['restaurant']['phone']) ? $config['restaurant']['phone'] : 'Restaurant Phone';
                $this->RESTAURANT_EMAIL = isset($config['restaurant']['email']) ? $config['restaurant']['email'] : 'Restaurant Email';
            } 

            if(isset($config['features'])) {
                $this->DISCOUNT_ENABLED = isset($config['features']['discountEnabled']) ? $config['features']['discountEnabled'] : false;
                $this->FIDELITY_ENABLED = isset($config['features']['fidelityEnabled']) ? $config['features']['fidelityEnabled'] : false;
                $this->POINTS_PER_UNIT = isset($config['features']['pointsPerUnit']) ? intval($config['features']['pointsPerUnit']) : 100; // Valor por defecto
                if ($this->POINTS_PER_UNIT <= 0) {
                    $this->POINTS_PER_UNIT = 100; // Valor por defecto si es inválido
                }
            }

            if(isset($config['stripe'])) {
                $this->STRIPE_PUBLIC_KEY = isset($config['stripe']['publicKey']) ? $config['stripe']['publicKey'] : '';
                $this->STRIPE_SECRET_KEY = isset($config['stripe']['secretKey']) ? $config['stripe']['secretKey'] : '';
            }

            $this->TIMEZONE = isset($config['timezone']) ? $config['timezone'] : 'Europe/Madrid';

            $this->INSTALL_FINISHED = isset($config['installFinished']) ? $config['installFinished'] : false;
        }
    }

    /**
     * Guarda la configuración actual en config.json
     */
    public function save() {
        $config = [
            'database' => [
                'host' => $this->DB_HOST,
                'name' => $this->DB_NAME,
                'user' => $this->DB_USER,
                'pass' => $this->DB_PASS
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
            'stripe' => [
                'publicKey' => $this->STRIPE_PUBLIC_KEY,
                'secretKey' => $this->STRIPE_SECRET_KEY
            ],
            'timezone' => $this->TIMEZONE,
            'installFinished' => $this->INSTALL_FINISHED
        ];

        $content = json_encode($config);
        file_put_contents('./config.json', $content);
    }

    /**
     * Devuelve la hora actual en la zona horaria configurada (formato Y-m-d H:i:s)
     * @return string
     */
    public function nowInTimezone() {
        $tz = new \DateTimeZone($this->TIMEZONE);
        $now = new \DateTime('now', $tz);
        return $now->format('Y-m-d H:i:s');
    }
}

$config = new Config();