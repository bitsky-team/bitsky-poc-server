<?php

    namespace Controller;

    use \Controller\Auth;
    use \Model\Module as ModuleModel;

    class Module extends Controller
    {
        public function __construct()
        {
            $this->authService = new Auth();
        }

        public function getRegistrationState()
        {
            $module = ModuleModel::where('name', 'registration')->first();

            if($module != null) {
                return json_encode(['success' => true, 'state' => $module->state]);
            }

            return $this->forbidden('emptyModule');
        }

        public function toggleRegistrationState()
        {
            if (!empty($_POST['token']) && !empty($_POST['uniq_id'])) {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if ($verify->success) {
                    $module = ModuleModel::where('name', 'registration')->first();
                    $module->state = !$module->state;
                    $module->save();

                    return json_encode(['success' => true, 'state' => $module->state]);
                } else {
                    return $this->forbidden('invalidToken');
                }
            } else {
                return $this->forbidden('noInfo');
            }
        }
    }