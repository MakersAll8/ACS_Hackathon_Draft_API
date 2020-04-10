<?php
namespace Api;

require_once 'init.php';

// echo server supports post request
Util::requestType('get');

Util::respond(Util::refreshToken());