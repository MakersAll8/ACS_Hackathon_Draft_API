<?php
namespace Api;
require_once 'init.php';

// echo server supports post request
Util::requestType('post');

// if content type is JSON, an util object is returned
$util = Util::inputIsJSON();

Util::respond($util->getInput());

