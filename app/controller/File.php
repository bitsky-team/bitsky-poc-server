<?php

namespace Controller;

use \Controller\Auth;
use \Kernel\LogManager;
use \Model\File as FileModel;
use \Model\User as UserModel;

class File extends Controller
{
    public function __construct()
    {
        $this->authService = new Auth();
    }

    function filesizeConvert($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    public function detectEncoding($filepath)
    {
        $output = array();
        exec('file -i ' . $filepath, $output);
        if (isset($output[0])){
            $ex = explode('charset=', $output[0]);
            return isset($ex[1]) ? $ex[1] : null;
        }
        return null;
    }

    public function localGetFolderContent()
    {
        $authorizedForeign = $this->isAuthorizedForeign();
        $check = $this->checkUserToken();

        if(!empty($check) || $authorizedForeign)
        {
            if(!empty($_POST['device']))
            {
                $path = $_SERVER['DOCUMENT_ROOT'] . '/devices/' . $_POST['device'] . '/';
            }else
            {
                $path = $_SERVER['DOCUMENT_ROOT'] . '/devices/bitsky/';
            }

            $items_content = [];
            $timezone = 1;

            $path .= (!empty($_POST['path']) ? $_POST['path'] : null);
            $items = scandir($path);

            foreach ($items as $item)
            {
                $fullPath = $path . '/' . $item;
                $fullPath = preg_replace('#/+#','/', $fullPath);

                clearstatcache();
                if($item == '.' || $item == '..') continue;

                if(file_exists($fullPath))
                {
                    $date_updated = date ('d-m-Y H:i:s.', filemtime($fullPath)  + 3600 * $timezone);
                    $itemsizeConverted = $this->filesizeConvert(filesize($fullPath));
                    $itemsize = filesize($fullPath);
                    $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $ownerUniqId = FileModel::where('path', $fullPath)->first();

                    if($ownerUniqId)
                    {
                        $ownerInformations = null;

                        if(!empty($ownerUniqId->link_id))
                        {
                            $linkController = new Link();
                            $_POST['link_id'] = $ownerUniqId->link_id;
                            $link = json_decode($linkController->getLinkById(), true);
                            $link = $link['link'];

                            $bitsky_ip = json_decode($linkController->getIpOfKey($link['bitsky_key']), true);
                            $bitsky_ip = $bitsky_ip['data'];

                            $owner = json_decode($this->callAPI(
                                'POST',
                                'http://localhost/get_user_by_uniq_id',
                                [
                                    'uniq_id' => $_POST['uniq_id'],
                                    'token' => $_POST['token'],
                                    'user_uniq_id' => $ownerUniqId->owner,
                                    'bitsky_ip' => $bitsky_ip
                                ]
                            ), true);

                            $ownerInformations = $owner['user'];
                        } else
                        {
                            $ownerInformations = UserModel::select('firstname', 'lastname', 'id' , 'uniq_id')->where('uniq_id', $ownerUniqId->owner)->first();
                        }

                        if(is_file($fullPath))
                        {
                            array_push($items_content, ['name' => $item, 'type' => $extension, 'updated_at' => $date_updated, 'converted_size' => $itemsizeConverted, 'size' => $itemsize, 'owner' => $ownerInformations]);
                        }
                        else if(is_dir($fullPath))
                        {
                            array_push($items_content, ['name' => $item, 'type' => 'dossier', 'updated_at' => $date_updated, 'converted_size' => $itemsizeConverted, 'size' => $itemsize, 'owner' => $ownerInformations]);
                        }
                    }
                } else
                {
                    LogManager::store('[POST] Tentative de récupération d\'un fichier inexistant (ID utilisateur: '.$_POST['uniq_id'].', chemin: '.$fullPath.')', 2);
                    return $this->forbidden('fileNotFound');
                }
            }
            return json_encode(['success' => true, 'content' => $items_content, 'path' => $fullPath]);
        } else
        {
            LogManager::store('[POST] Tentative de récupération des fichiers avec un token invalide (ID utilisateur: '.$_POST['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getFolderContent()
    {
        if (empty($_POST['bitsky_ip']))
        {
            return $this->localGetFolderContent();
        } else
        {
            $url = htmlspecialchars($_POST['bitsky_ip']) . '/local_get_files';

            $files = $this->callAPI(
                'POST',
                $url,
                $_POST
            );

            return $files;
        }
    }

    public function localUploadFiles()
    {
        $check = $this->checkUserToken();
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($check) || $authorizedForeign)
        {
            if(!empty($_POST['files']))
            {
                $path = $_POST['path'];
                $files = $_POST['files'];

                if(!empty($_POST['device']))
                {
                    $rootPath = $_SERVER['DOCUMENT_ROOT'] . '/devices/' . $_POST['device'] . (empty($path) ? '/' : '');
                }else
                {
                    $rootPath = $_SERVER['DOCUMENT_ROOT'] . '/devices/bitsky' . (empty($path) ? '/' : '');
                }

                if(!empty($path)) $rootPath .= $path . '/';

                if(is_string($files)) {
                    $files = json_decode($files, true);
                }

                foreach ($files as $file)
                {
                    foreach($file as $key => $value)
                    {
                        list(, $data) = explode(';', $value);
                        list(, $data)      = explode(',', $data);
                        $data = base64_decode($data);
                        file_put_contents($rootPath . $key, $data);

                        if(empty($_POST['device']) || $_POST['device'] == 'bitsky')
                        {
                            chmod($rootPath . $key, 0664);
                        }

                        $existingFile = FileModel::where('path', $rootPath . $key)->first();
                        if($existingFile != null)
                        {
                            $existingFile->delete();
                        }

                        if($authorizedForeign && !empty($_POST['bitsky_ip']))
                        {
                            $bitsky_ip = htmlspecialchars($_POST['bitsky_ip']);
                            $linkController = new Link();
                            $bitskyKey = json_decode($linkController->getKeyOfIp($bitsky_ip), true);
                            $bitskyKey = $bitskyKey['data'];
                            $link = \Model\Link::where('bitsky_key', $bitskyKey)->first();

                            FileModel::create([
                                'path' => $rootPath . $key,
                                'owner' => $_POST['uniq_id'],
                                'link_id' => $link->id
                            ]);
                        } else
                        {
                            FileModel::create([
                                'path' => $rootPath . $key,
                                'owner' => $_POST['uniq_id']
                            ]);
                        }
                    }
                }

                return json_encode(['success' => true]);
            } else
            {
                return $this->forbidden('emptyInput');
            }
        } else
        {
            LogManager::store('[POST] Tentative d\'upload de fichiers avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function uploadFiles()
    {
        if (empty($_POST['bitsky_ip']))
        {
            return $this->localUploadFiles();
        } else
        {
            $url = htmlspecialchars($_POST['bitsky_ip']) . '/local_upload_files';
            $external_ip = exec('curl http://ipecho.net/plain; echo');

            $_POST['files'] = json_encode($_POST['files']);
            $_POST['bitsky_ip'] = $external_ip;

            $file = $this->callAPI(
                'POST',
                $url,
                $_POST
            );

            return $file;
        }
    }

    public function localCreateFolder()
    {
        $authorizedForeign = $this->isAuthorizedForeign();
        $check = $this->checkUserToken();

        if(!empty($check) || $authorizedForeign)
        {
            if(!empty($_POST['name']))
            {
                $name = $_POST['name'];
                $path = $_POST['path'];

                if(!empty($_POST['device']))
                {
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/devices/' . $_POST['device'] . $path . '/';
                }else
                {
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/devices/bitsky' . $path . '/';
                }

                mkdir($fullPath.$name);

                if(empty($_POST['device']) || $_POST['device'] == 'bitsky')
                {
                    chmod($fullPath.$name, 0775);
                }

                if(is_dir($fullPath.$name))
                {
                    $existingFile = FileModel::where('path', $fullPath.$name)->first();
                    if($existingFile != null)
                    {
                        $existingFile->delete();
                    }
                    FileModel::create([
                        'path' => $fullPath.$name,
                        'owner' => $_POST['uniq_id']
                    ]);
                    return json_encode(['success' => true, 'path' => $fullPath.$name]);
                }else
                {
                    return $this->forbidden('cannotCreateFolder');
                }
            }else
            {
                return $this->forbidden('emptyInput');
            }
        } else
        {
            LogManager::store('[POST] Tentative de création de dossier avec un token invalide (ID utilisateur: '.$_POST['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function createFolder()
    {
        if (empty($_POST['bitsky_ip']))
        {
            return $this->localCreateFolder();
        } else
        {
            $url = htmlspecialchars($_POST['bitsky_ip']) . '/local_create_folder';

            $file = $this->callAPI(
                'POST',
                $url,
                $_POST
            );

            return $file;
        }
    }

    public function localDeleteItem()
    {
        $authorizedForeign = $this->isAuthorizedForeign();
        $check = $this->checkUserToken();

        if(!empty($check || $authorizedForeign))
        {
            if(!empty($_POST['name']))
            {
                $path = $_POST['path'];
                $name = $_POST['name'];

                $currentUser = UserModel::where('uniq_id', $check['uniq_id'])->first();

                if(!empty($_POST['device']))
                {
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/devices/' . $_POST['device'] . '/' . $path . '/';
                }else
                {
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/devices/bitsky/' . $path . '/';
                }

                $fullPath = preg_replace('#/+#','/', $fullPath);

                $ownerUniqId = FileModel::where('path', $fullPath . $name)->first();

                if($currentUser['rank'] == 2 || $ownerUniqId->owner == $_POST['uniq_id'])
                {
                    $checkInputs = !strstr($path, '..')
                        && !strstr($path, ';')
                        && !strstr($name, '..')
                        && !strstr($name, ';')
                        && !empty(trim($name))
                        && !strstr($name, '&')
                        && !strstr($name, '&&')
                        && !strstr($name, '|')
                        && !strstr($name, '||')
                        && !strstr($path, '&')
                        && !strstr($path, '&&')
                        && !strstr($path, '|')
                        && !strstr($path, '||');

                    if($checkInputs)
                    {
                        exec('rm -rf ' . $fullPath . $name);

                        $file = FileModel::where('path', $fullPath . $name);

                        if($file)
                        {
                            $file->delete();
                        }

                        if(!file_exists($fullPath . $name))
                        {
                            return json_encode(['success' => true, 'path' => $fullPath . $name]);
                        }else
                        {
                            return $this->forbidden('unableToDeleteItem');
                        }
                    }else
                    {
                        return $this->forbidden('invalidInput');
                    }
                }else
                {
                    return $this->forbidden('tooLowRankOrWrongOwner');
                }
            }else
            {
                return $this->forbidden('emptyInput');
            }
        }else
        {
            LogManager::store('[POST] Tentative de suppression d\'un item avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function deleteItem()
    {
        if (empty($_POST['bitsky_ip']))
        {
            return $this->localDeleteItem();
        } else
        {
            $url = htmlspecialchars($_POST['bitsky_ip']) . '/local_delete_item';

            $file = $this->callAPI(
                'POST',
                $url,
                $_POST
            );

            return $file;
        }
    }

    public function localDownloadItem()
    {
        $authorizedForeign = $this->isAuthorizedForeign();
        $check = $this->checkUserToken();

        if(!empty($check) || $authorizedForeign)
        {
            if(!empty($_POST['name']))
            {
                $path = $_POST['path'];
                $name = $_POST['name'];

                if(!empty($_POST['device']))
                {
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/devices/' . $_POST['device'] . '/' . $path . '/' . $name;
                }else
                {
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/devices/bitsky/' . $path . '/' . $name;
                }

                $fullPath = preg_replace('#/+#','/', $fullPath);

                if(!strstr($fullPath, '..') && !strstr($name, '..'))
                {
                    if (file_exists($fullPath)) {
                        $encoding = $this->detectEncoding($fullPath);
                        header('Content-Type: text/plain; charset=' . $encoding);
                        header("Content-Transfer-Encoding: Binary");
                        header("Content-disposition: attachment; filename=\"" . basename($fullPath) . "\"");
                        readfile($fullPath);
                    }else
                    {
                        return $this->forbidden('itemDoesNotExist');
                    }
                }else
                {
                    $this->forbidden('invalidInput');
                }
            }
        }else
        {
            LogManager::store('[POST] Tentative de téléchargement d\'un item avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function downloadItem()
    {
        if (empty($_POST['bitsky_ip']))
        {
            return $this->localDownloadItem();
        } else
        {
            $url = htmlspecialchars($_POST['bitsky_ip']) . '/local_download_item';

            $file = $this->callAPI(
                'POST',
                $url,
                $_POST
            );

            return $file;
        }
    }

    public function localGetStorageDevices()
    {
        $authorizedForeign = $this->isAuthorizedForeign();
        $check = $this->checkUserToken();

        if(!empty($check) || $authorizedForeign)
        {
            $devices = [];
            $letters = ['a', 'b', 'c', 'd'];
            $path = '/dev/sd';
            $numberOfPorts = 4;

            foreach ($letters as $letter)
            {
                $noMatch = true;

                for($i = 1; $i <= $numberOfPorts; $i++)
                {
                    if(file_exists($path . $letter . $i))
                    {
                        $noMatch = false;
                        array_push($devices, $path . $letter . $i);
                    }
                }

                if($noMatch)
                {
                    if(file_exists($path . $letter))
                    {
                        array_push($devices, $path . $letter);
                    }
                }
            }

            return json_encode(['success' => true, 'devices' => $devices]);
        }else
        {
            LogManager::store('[POST] Tentative de récupération d\'un appareil de stockage avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getStorageDevices()
    {
        if (empty($_POST['bitsky_ip']))
        {
            return $this->localGetStorageDevices();
        } else
        {
            $url = htmlspecialchars($_POST['bitsky_ip']) . '/local_get_devices';

            $file = $this->callAPI(
                'POST',
                $url,
                $_POST
            );

            return $file;
        }
    }
}
