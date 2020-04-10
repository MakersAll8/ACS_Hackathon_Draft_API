<?php

namespace Api;

use function array_key_exists;
use Exception;
use function in_array;
use function sizeof;

require_once 'init.php';

// echo server supports post request
Util::requestType('get');

$auth = Util::verifyAccessToken();
if (!array_key_exists('user_id', $auth)) {
    Util::respond($auth);
}

$isAuthorized = false;
$authorizedRoles = ['admin', 'staff', 'responder'];
foreach($auth['roles'] as $role){
    if(in_array($role['role_name'], $authorizedRoles)){ $isAuthorized = true;}
}

if(!$isAuthorized){
    Util::respond(['error'=>'Log in as admin, staff, or responder to view all tasks']);
}

$limit = empty($_GET['limit']) ? 20 : $_GET['limit'] ;
$offset = empty($_GET['offset']) ? 0 : $_GET['offset'] ;

try {
    $sql = "SELECT * FROM Task ORDER BY createTime DESC LIMIT ? OFFSET ?";
    $pdo = CommunityDB::pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $output = $stmt->fetchAll();

    if($output){
        for($i=0, $l=sizeof($output); $i < $l; $i++){
            $sql = "SELECT * FROM Reply WHERE taskId = ? ORDER BY createTime ASC";
            $pdo = CommunityDB::pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$output[$i]['id']]);
            $output[$i]['reply'] = $stmt->fetchAll();
        }
    } else {
        $output = [];
        $output['error'] = 'task not found';
        Util::respond($output);
    }

    Util::respond($output);


} catch (Exception $e){
    if ($e->getCode()== 23000) {
        $output['error']= 'SQL database error';
        Util::respond($output);
    } else {
        $output['error']= 'unexpected error';
        Util::respond($output);
    }
}