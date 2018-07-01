<?php
    /**
     * routes.php
     *
     * Routes importing
     *
     * @author Jason Van Malder <jasonvanmalder@gmail.com>
     */

    $routes = array();
    $dir = scandir('../routes');
    $files = array_diff($dir, array('..', '.'));
    
    foreach($files as $file) {
        $array = require_once('../routes/' . $file);
        $routes = array_merge($routes, $array);
    }

    return $routes;