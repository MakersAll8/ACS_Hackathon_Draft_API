<?php

namespace Api;

use function array_key_exists;
use Exception;
use http\Exception\BadQueryStringException;
use function in_array;
use PDOException;

require_once 'init.php';

// echo server supports post request
Util::requestType('post');

// if content type is JSON, an util object is returned
$util = Util::inputIsJSON();

$auth = Util::verifyAccessToken();
if (!array_key_exists('user_id', $auth)) {
    Util::respond($auth);
}

$isAuthorized = false;
$authorizedRoles = ['admin', 'responder'];
foreach($auth['roles'] as $role){
    if(in_array($role['role_name'], $authorizedRoles)){ $isAuthorized = true;}
}

if(!$isAuthorized){
    Util::respond(['error'=>'Log in as admin or responder to reply']);
}

$input = $util->getInput();
$output = [];

if(empty($input['taskId']) || empty($input['message']))
{
    $output['error'] = 'Required parameters not received';
    Util::respond($output);
}

try {
    $input['repliedBy'] = $auth['user_id'];

    $result = Util::insert('Reply', $input);
    if(array_key_exists('success',$result)){
        $output['success'] = 'added reply';
        $output['reply'] = $result['id'];
        Util::respond($output);
    }

    $output['error']= 'Insert failed';
    Util::respond($output);
} catch (Exception $e){
    if($e->errorInfo[1] == 1062){
        $output['error']= 'reply id already exist';
        Util::respond($output);
    } else if ($e->getCode()== 23000) {
        $output['error']= 'SQL database error';
        Util::respond($output);
    } else {
        $output['error']= 'unexpected error';
        Util::respond($output);
    }
}