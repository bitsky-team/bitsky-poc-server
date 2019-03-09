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

        protected function callAPI($method, $url, $data = false)
        {
            $curl = curl_init();

            switch ($method)
            {
                case "POST":
                    curl_setopt($curl, CURLOPT_POST, 1);

                    if ($data)
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case "PUT":
                    curl_setopt($curl, CURLOPT_PUT, 1);
                    break;
                default:
                    if ($data)
                        $url = sprintf("%s?%s", $url, http_build_query($data));
            }

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);

            curl_close($curl);

            return $result;
        }
    }