<?php

namespace Api;

use function array_key_exists;
use Exception;
use function in_array;

require_once 'init.php';

// echo server supports post request
Util::requestType('get');

$auth = Util::verifyAccessToken();
if (!array_key_exists('user_id', $auth)) {
    Util::respond($auth);
}


$taskId = $_GET['taskId'];

if(empty($taskId)){
    $output['error'] = 'taskId not received';
    Util::respond($output);
}

try {
    $sql = "SELECT * FROM Task WHERE id = ?";
    $pdo = CommunityDB::pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taskId]);
    $output['task'] = $stmt->fetchAll();

    $sql = "SELECT * FROM Reply WHERE taskId = ?";
    $pdo = CommunityDB::pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taskId]);
    $output['reply'] = $stmt->fetchAll();

//    if($output){
        Util::respond($output);
//    } else {
//        $output = [];
//        $output['error'] = 'task id not found';
//        Util::respond($output);
//    }

} catch (Exception $e){
    if ($e->getCode()== 23000) {
        $output['error']= 'SQL database error';
        Util::respond($output);
    } else {
        $output['error']= 'unexpected error';
        Util::respond($output);
    }
}