<?php

    namespace Kernel;

    /**
     * RestPHP's Router
     * 
     * Call the controller method 
     * associated with the requested route
     * 
     * @category Routing
     * @author Jason Van Malder <jasonvanmalder@gmail.com>
     */
    class Router 
    {
        /**
         * @var Router
         * @access private
         * @static
         */
        private static $_instance = null;
        
        /**
         * Method who creates the class instance
         * if it doesn't exists yet then return it
         *
         * @param void
         * @return Router
         */
        public static function getInstance() : Router
        {
            if(is_null(self::$_instance))
            {
                self::$_instance = new Router();  
            }
        
            return self::$_instance;
        }

        /**
         * Method who read the routes and
         * call the controller method associated with
         * 
         * @param void
         * @return void
         * 
         */
        public static function launch()
        {
            $routes = require_once(__DIR__ . '/../config/routes.php');
            $request = (isset($_GET['route']) && !empty($_GET['route'])) ? htmlspecialchars($_GET['route']) : 'home';

            if(substr($request, -1) == '/') {
                $request = substr($request, 0, -1);
            }

            $request = explode('/', $request);
            foreach($routes as $route)
            {
                $parts = explode('/', $route[1]);
                
                if($request[0] == $parts[0] && $_SERVER['REQUEST_METHOD'] == $route[0])
                {                    
                    if(count($request) == count($parts))
                    {
                        $request = array_values(array_filter($request));
                        $i = 0;
                        foreach($parts as $part)
                        {
                            $part = str_replace('{', '', $part);
                            $part = str_replace('}', '', $part);
                            if($part != $request[$i]) $_GET[$part] = $request[$i];
                            $i++;
                        }
                        
                        $request = $route;
                        break;
                    }
                }
            }
            
            $request_methods = ['GET', 'POST', 'PUT'];

            if(!in_array($request[0], $request_methods)) $request = ['GET', '404', 'controller', 'notFound'];
            
            if ($_SERVER['REQUEST_METHOD'] == $request[0]) 
            {
                $controller = 'Controller\\' . ucfirst($request[2]);
                if(class_exists($controller))
                {
                    $controller = new $controller();
                    
                    if(method_exists($controller, $request[3]))
                    {
                        echo $controller->{$request[3]}();
                    }else
                    {
                        die(json_encode(['success' => false, 'error' => 'controllerMethodNotFound', 'name' => $request[3]]));                                            
                    }
                }else
                {
                    die(json_encode(['success' => false, 'error' => 'controllerNotFound', 'name' => ucfirst($request[2])]));                    
                }
            }else
            {
                die(json_encode(['success' => false, 'error' => '403']));
            }
        }
    }