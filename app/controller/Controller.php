<?php

    namespace Controller;

    use Kernel\RemoteAddress;

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

        public function isAuthorizedForeign()
        {
            $remoteAddress = new RemoteAddress();

            $links = $this->callAPI(
                'POST',
                'https://bitsky.be/getActiveLinks',
                [
                    'bitsky_key' => getenv('LINKING_KEY')
                ]
            );

            $links = json_decode($links, true);
            $linksIP = [];

            if($links['success']) {
                $links = $links['data'];

                foreach($links as $link) {
                    array_push($linksIP, $link['foreign_ip']);
                }
            }

            return in_array($remoteAddress->getIpAddress(), $linksIP);
        }

        public function checkUserToken()
        {
            $authService = new Auth();

            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($authService->verify($token, $uniq_id));

                if ($verify->success) {
                    return [
                        'uniq_id' => $uniq_id,
                        'token' => $token
                    ];
                }

                return null;
            }

            return null;
        }
    }