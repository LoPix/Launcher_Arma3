<?php
/**
 * Created by PhpStorm.
 * User: hubert_i
 * Date: 17/06/16
 * Time: 17:19
 */


header('Content-type: application/json');

$result = array("status" => 500, "message" => "Internal error");

if (isset($_POST['token']) && isset($_POST['id']))
{
    $id = $_POST['id'];
    $token = $_POST['token'];

    $checkUser = $database->prepare('SELECT user_id FROM sessions WHERE token = :token');
    $checkUser->execute(array('token' => $token));
    $res = $checkUser->fetch();
    if ($checkUser->rowCount() != 0 && $userLevel = $database->prepare('SELECT `level`,`banned` FROM users WHERE id = :id')) {
        $userLevel->execute(array('id' => $res['user_id']));
        $myID = $res['user_id'];
        $res = $userLevel->fetch();
        if ($userLevel->rowCount() != 0 && (int)$res['level'] >= 7 && (int)$res['banned'] != 1) {
            $getSettings = $database->prepare('SELECT * FROM `servers` WHERE id = :id');
            $getSettings->execute(array('id' => $id));
            if ($getSettings->rowCount()) {
                $res = $getSettings->fetch();

                $addons_directory = $res['local_path'] . '/modpack/addons';
                $cpps_directory = $res['local_path'] . '/modpack';
                $userconfigs_directory = $res['local_path'];
                if ($handle_addons = opendir($addons_directory)) {
                    $Addons = array();
                    $Cpps = array();
                    $Userconfigs = array();
                    $i = 0;
                    $addons = array();
                    $cpps = array();
                    $userconfigs = array();
                    $handle_cpps = opendir($cpps_directory);
                    $handle_userconfigs = opendir($userconfigs_directory);
                    /* Listing des mods */
                    while (false !== ($entry = readdir($handle_addons))) {
                        if ($entry != '.' && $entry != '..' && is_file($addons_directory . '/' . $entry)) {
                            $Addons['mod' . $i]['md5'] = md5_file($addons_directory . '/' . $entry);
                            $Addons['mod' . $i]['name'] = $entry;
                            $Addons['mod' . $i]['size'] = filesize($addons_directory . '/' . $entry);
                            $i++;
                        }
                    }
                    while (false !== ($entry = readdir($handle_cpps))) {
                        if ($entry != '.' && $entry != '..' && is_file($cpps_directory . '/' . $entry)) {
                            $Cpps['cpp' . $i]['md5'] = md5_file($cpps_directory . '/' . $entry);
                            $Cpps['cpp' . $i]['name'] = $entry;
                            $Cpps['cpp' . $i]['size'] = filesize($cpps_directory . '/' . $entry);
                            $i++;
                        }
                    }
                    while (false !== ($entry = readdir($handle_userconfigs))) {
                        if ($entry != '.' && $entry != '..' && is_file($userconfigs_directory . '/' . $entry)) {
                            $Userconfigs['userconfig' . $i]['md5'] = md5_file($userconfigs_directory . '/' . $entry);
                            $Userconfigs['userconfig' . $i]['name'] = $entry;
                            $Userconfigs['userconfig' . $i]['size'] = filesize($userconfigs_directory . '/' . $entry);
                            $i++;
                        }
                    }
                    closedir($handle_addons);
                    closedir($handle_userconfigs);
                    closedir($handle_cpps);
                    foreach ($Addons as $Addon) {
                        array_push($addons, $Addon);
                    }
                    foreach ($Cpps as $Cpp) {
                        array_push($cpps, $Cpp);
                    }
                    foreach ($Userconfigs as $Userconfig) {
                        array_push($userconfigs, $Userconfig);
                    }

                    $result['status'] = 42;
                    $result['message'] = "Update created";
                    $result['total_addons'] = count($addons);
                    $result['total_cpps'] = count($cpps);
                    $result['total_userconfigs'] = count($userconfigs);
                    $result['addons'] = $addons;
                    $result['cpps'] = $cpps;
                    $result['userconfigs'] = $userconfigs;

                    if (!$fp = fopen($userconfigs_directory . '/'. $id .'.json', 'w+'))
                    {
                        $result['status'] = 505;
                        $result['message'] = "Can't open " .  $userconfigs_directory . '/list.json';
                    }
                    if (!fwrite($fp, json_encode($result)))
                    {
                        $result['status'] = 504;
                        $result['message'] = "Can't write " .  $userconfigs_directory . '/list.json';
                    }
                    
                    if ($result['status'] == 42)
                    {
                        $insertToken = $database->prepare('UPDATE servers SET vmod=:vmod WHERE id = :id');
                        $vmod = (int)$res['vmod'] += 1;
                        $insertToken->execute(array('vmod' => $vmod, 'id' => $id));
                    }
                    fclose($fp);
                }
            }
            else
            {
                $result["status"] = 44;
                $result["message"] = "Server not found";
            }

        }
        else
        {
            $result['status'] = 44;
            $result['message'] = "You don't have right to create this request !";
        }
    }
    else
    {
        $result['status'] = 41;
        $result['message'] = "Token invalid";
    }
}
else
{
    $result["status"] = 404;
    $result["message"] = "Arguments missing.";
}


echo json_encode($result);