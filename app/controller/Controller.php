<?php

    namespace Controller;

    /**
     * RestPHP's abstract controller
     * 
     * @category Controller
     * @author Jason Van Malder <jasonvanmalder@gmail.com>
     */
    class Controller
    {
        /**
         * Method returning a 404 error
         * 
         * @return string
         */
        public function notFound()
        {
            return json_encode(['success' => false, 'error' => 404]);
        }

        /**
         * Method returning a 403 error
         * 
         * @return string
         */
        public function forbidden($message = null)
        {
            return json_encode(['success' => false, 'error' => 403, 'message' => $message]);
        }
    }