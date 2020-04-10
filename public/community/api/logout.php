<?php

namespace Api;

use function array_key_exists;

require_once 'init.php';

// echo server supports post request
Util::requestType('get');

$auth = Util::verifyAccessToken();
if (!array_key_exists('user_id', $auth)) {
    Util::respond($auth);
}

$sql = "UPDATE User SET access_token = NULL, access_token_expire_at = NULL, 
refresh_token = NULL, refresh_token_expire_at = NULL, failed_attempts = 0, 
last_failed_time = NULL WHERE user_id = ?";

$pdo = CommunityDB::pdo();
$stmt = $pdo->prepare($sql);

$result = $stmt->execute([$auth['user_id']]);

if ($result) {
    $message = ['success' => 'logout successful'];
} else {
    $message = ['error' => 'logout failed'];
}

Util::respond($message);


