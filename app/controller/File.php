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

    function filesizeConvert($bytes, $decimals = 2) {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    public function getFolderContent()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/files';
            $items_content = [];
            $timezone = 1;

            if(!empty($_POST['path']))
            {
                $path .= $_POST['path'];
            }

            $items = scandir($path);

            foreach ($items as $item)
            {
                $fullPath = $path . DIRECTORY_SEPARATOR . $item;
                clearstatcache();
                if($item == '.' || $item == '..') continue;
                if(file_exists($fullPath))
                {
                    $date_updated = date ('d-m-Y H:i:s.', filemtime($fullPath)  + 3600 * $timezone);
                    $itemsize = $this->filesizeConvert(filesize($fullPath));
                    $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $ownerUniqId = FileModel::where('path', $fullPath)->first();

                    if($ownerUniqId)
                    {
                        $ownerInformations = UserModel::select('firstname', 'lastname','id')->where('uniq_id', $ownerUniqId->owner)->first();

                        if(is_file($fullPath))
                        {
                            array_push($items_content, ['name' => $item, 'type' => $extension, 'updated_at' => $date_updated, 'size' => $itemsize, 'owner' => $ownerInformations]);
                        }
                        else if(is_dir($fullPath))
                        {
                            array_push($items_content, ['name' => $item, 'type' => 'dossier', 'updated_at' => $date_updated, 'size' => $itemsize, 'owner' => $ownerInformations]);
                        }
                    }
                } else
                {
                    LogManager::store('[POST] Tentative de récupération d\'un fichier inexistant (ID utilisateur: '.$check['uniq_id'].', chemin: '.$fullPath.')', 2);
                    return $this->forbidden('fileNotFound');
                }
            }
            return json_encode(['success' => true, 'content' => $items_content]);
        } else
        {
            LogManager::store('[POST] Tentative de récupération des fichiers avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function detectEncoding($filepath) {
        $output = array();
        exec('file -i ' . $filepath, $output);
        if (isset($output[0])){
            $ex = explode('charset=', $output[0]);
            return isset($ex[1]) ? $ex[1] : null;
        }
        return null;
    }

    public function uploadFiles()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if(!empty($_POST['files']))
            {
                $path = $_POST['path'];
                $files = $_POST['files'];
                $rootPath = $_SERVER['DOCUMENT_ROOT'] . '/files/';

                if(!empty($path)) $rootPath .= $path . '/';

                foreach ($files as $file)
                {
                    foreach($file as $key => $value)
                    {
                        list(, $data) = explode(';', $value);
                        list(, $data)      = explode(',', $data);
                        $data = base64_decode($data);
                        file_put_contents($rootPath . $key, $data);
                        chmod($rootPath . $key, 0664);

                        $existingFile = FileModel::where('path', $rootPath . $key)->first();
                        if($existingFile != null)
                        {
                            $existingFile->delete();
                        }
                        FileModel::create([
                            'path' => $rootPath . $key,
                            'owner' => $check['uniq_id']
                        ]);
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

    public function createFolder()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if(!empty($_POST['name']))
            {
                $name = $_POST['name'];
                $path = $_POST['path'];

                $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/files' . $path;

                mkdir($fullPath.'/'.$name);
                chmod($fullPath.'/'.$name, 0775);

                if(is_dir($fullPath.'/'.$name))
                {
                    $existingFile = FileModel::where('path', $fullPath.'/'.$name)->first();
                    if($existingFile != null)
                    {
                        $existingFile->delete();
                    }
                    FileModel::create([
                        'path' => $fullPath.'/'.$name,
                        'owner' => $check['uniq_id']
                    ]);
                    return json_encode(['success' => true, 'path' => $fullPath.'/'.$name]);
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
            LogManager::store('[POST] Tentative de création de dossier avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function deleteItem()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if(!empty($_POST['name']))
            {
                $path = $_POST['path'];
                $name = $_POST['name'];

                if(!strstr($path, '..') && !strstr($path, ';') && !strstr($name, '..') && !strstr($name, ';') && !empty(trim($name)))
                {
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/files' . $path . '/';

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
                    $this->forbidden('invalidInput');
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

    public function downloadItem()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if(!empty($_POST['name']))
            {
                $path = $_POST['path'];
                $name = $_POST['name'];

                $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/files' . $path . '/' . $name;

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
}