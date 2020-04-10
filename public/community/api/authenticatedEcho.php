<?php

namespace Api;

use function array_key_exists;

require_once 'init.php';

// echo server supports post request
Util::requestType('post');

// if content type is JSON, an util object is returned
$util = Util::inputIsJSON();

$auth = Util::verifyAccessToken();
if (!array_key_exists('user_id', $auth)) {
    Util::respond($auth);
}

$output = $util->getInput();
$output['username'] = $auth['username'];
$output['user_id'] = $auth['user_id'];
Util::respond($output);