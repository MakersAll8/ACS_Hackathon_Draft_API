<?php

namespace Api;

use function array_key_exists;
use Carbon\Carbon;
use Exception;

require_once 'init.php';

// echo server supports post request
Util::requestType('post');

// if content type is JSON, an util object is returned
$util = Util::inputIsJSON();

$auth = Util::verifyAccessToken();
if (!array_key_exists('user_id', $auth)) {
    Util::respond($auth);
}


$input = $util->getInput();
$output = [];

if(empty($input['title']) || empty($input['location'])
    || empty($input['taskDate']) || empty($input['details'])
)
{
    $output['error'] = 'Required parameters not received';
    Util::respond($output);
}

$input['createdBy'] = $auth['user_id'];
$input['taskDate'] = Carbon::parse($input['taskDate'])->toDateTimeString();

try {
    $result = Util::insert('Task', $input);
    if(array_key_exists('success',$result)){
        $output['success'] = 'added new product to list';
        $output['task_id'] = $result['id'];
        Util::respond($output);
    }

    $output['error']= 'Insert failed';
    Util::respond($output);
} catch (Exception $e){
    if($e->errorInfo[1] == 1062){
        $output['error']= 'task id already exist';
        Util::respond($output);
    } else if ($e->getCode()== 23000) {
        $output['error']= 'SQL database error';
        Util::respond($output);
    } else {
        $output['error']= 'unexpected error';
        Util::respond($output);
    }
}