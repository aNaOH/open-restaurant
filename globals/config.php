<?php

class Config {
    public $DB_HOST = 'localhost';
    public $DB_NAME = '';
    public $DB_USER = '';
    public $DB_PASS = '';

    // Check if the application is installed, check if the database name and user are set
    public function isInstalled() {
        return !empty($this->DB_NAME) && !empty($this->DB_USER);
    }

    public function __construct() {
        $content = file_get_contents('config.json');
        $config = json_decode($content, true);
        
        if ($config) {
            $this->DB_HOST = $config['database']['host'];
            $this->DB_NAME = $config['database']['name'];
            $this->DB_USER = $config['database']['user'];
            $this->DB_PASS = $config['database']['pass'];
        }
    }

    public function save() {
        $config = [
            'database' => [
                'host' => $this->DB_HOST,
                'name' => $this->DB_NAME,
                'user' => $this->DB_USER,
                'pass' => $this->DB_PASS
            ]
        ];

        $content = json_encode($config);
        file_put_contents('config.json', $content);
    }
}

$config = new Config();