<?php
// Clase que representa al autor de una homepage
class HomepageAuthor {
    public string $name; // Nombre del autor
    public string $email; // Email del autor
    public string $url; // URL del autor

    /**
     * Constructor de HomepageAuthor
     * @param string $name
     * @param string $email
     * @param string $url
     */
    public function __construct($name, $email, $url) {
        $this->name = $name;
        $this->email = $email;
        $this->url = $url;
    }
}

// Clase que representa una homepage personalizada
class Homepage {
    public string $name; // Nombre de la homepage
    public string $description; // Descripción
    public HomepageAuthor $author; // Autor
    public string $entry; // Carpeta de entrada

    /**
     * Constructor de Homepage
     * @param string $name
     * @param string $description
     * @param HomepageAuthor|string $author
     * @param string $entry
     */
    public function __construct($name, $description, $author, $entry) {
        $this->name = $name;
        $this->description = $description;
        // If the author is a HomepageAuthor object, set the author property to the object
        // otherwise set the author property to a new HomepageAuthor object with the author as the name
        $this->author = $author instanceof HomepageAuthor ? $author : new HomepageAuthor($author, '', '');
        $this->entry = $entry;
    }
}

// Clase que gestiona todas las homepages disponibles
class Homepages {
    public $homepages = []; // Array de homepages

    /**
     * Constructor: carga todas las homepages desde la carpeta content/homepages
     */
    public function __construct() {
        $homepagesLocation = 'content/homepages';
        // Get all folders in the homepages directory
        $folders = array_diff(scandir($homepagesLocation), ['.', '..']);
        // Loop through each folder searching for a meta.json file
        foreach ($folders as $folder) {
            $metaFile = $homepagesLocation . '/' . $folder . '/meta.json';
            if (file_exists($metaFile)) {
                $meta = json_decode(file_get_contents($metaFile), true);
                $this->homepages[] = new Homepage($meta['name'], $meta['description'], $meta['author'], $folder);
            }
        }
    }
}

// Instancia global de homepages
$homepages = new Homepages();