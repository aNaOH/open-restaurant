<?php

class HomepageAuthor {
    public string $name;
    public string $email;
    public string $url;

    public function __construct($name, $email, $url) {
        $this->name = $name;
        $this->email = $email;
        $this->url = $url;
    }
}

class Homepage {
    public string $name;
    public string $description;
    public HomepageAuthor $author;
    public string $entry;

    public function __construct($name, $description, $author, $entry) {
        $this->name = $name;
        $this->description = $description;
        // If the author is a HomepageAuthor object, set the author property to the object
        // otherwise set the author property to a new HomepageAuthor object with the author as the name
        $this->author = $author instanceof HomepageAuthor ? $author : new HomepageAuthor($author, '', '');
        $this->entry = $entry;
    }
}

class Homepages {
    public $homepages = [];

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

$homepages = new Homepages();