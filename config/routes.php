<?php
    /**
     * routes.php
     *
     * Routes importing
     *
     * @author Jason Van Malder <jasonvanmalder@gmail.com>
     */

    $routes = array();
    $dir = scandir('/var/www/html/routes');
    $files = array_diff($dir, array('..', '.'));
    
    foreach($files as $file) {
        $array = require_once('/var/www/html/routes/' . $file);
        $routes = array_merge($routes, $array);
    }

    return $routes;