<?php
namespace Api;
use Carbon\Carbon;
use Exception;
require_once 'init.php';


Util::requestType('post');
$util = Util::inputIsJSON();

$input = $util->getInput();

$username = $input['username'];
$password = $input['password'];

if(empty($username) || empty($password)){
    $message = ['error'=>'username or password cannot be empty'];
    Util::respond($message);
}

$pdo = CommunityDB::pdo();

$sql = "SELECT * FROM `User` WHERE username = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);

$user = $stmt->fetch();
if(!$user){ // user does not exist
    $message = ['error'=>'login failed'];
    Util::respond($message);
}

// if failed attempts >= 5 or locked -> account is locked
if($user['failed_attempts'] > 4 || $user['locked']){
    $message = ['error'=>'account is locked'];
    Util::respond($message);
}

$exists =  password_verify($password, $user['password']);
$now = Carbon::now(Util::getTimezone());

if(!$exists) {
    // if last_failed_time within 24 hrs, failed attempts + 1 else failed
    // attempts = 1

    $attempts = 1;
    if(!empty($user['last_failed_time'])){
        $last_failed_time = Carbon::parse($user['last_failed_time']);
        // last failed time within 24 hours
        if($last_failed_time->addHours(24)->greaterThan($now)){
            $attempts = $user['failed_attempts'] + 1;
        }
    }

    $sql = 'UPDATE User SET failed_attempts = ?, last_failed_time = ? WHERE user_id = ?';
//    $pdo = CommunityDB::pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$attempts, $now->toDateTimeString(), $user['user_id']]);


    $remaining = 5-$attempts;
    $message = ['error' => 'Login has failed. Attempts remaining '. $remaining];
    Util::respond($message);
}

$access_token = Util::createToken();
$refresh_token = Util::createToken();

$access_token_expire_at = Carbon::now(Util::getTimezone())->addMinutes(20)
    ->toDateTimeString();
$refresh_token_expire_at = Carbon::now(Util::getTimezone())->addHours(2)
    ->toDateTimeString();

try {
    // failed attempts reset to 0
    $sql = 'UPDATE User SET failed_attempts = 0, access_token = ?, 
        access_token_expire_at = ?, refresh_token = ?, 
        refresh_token_expire_at = ?, last_login = ?
        WHERE user_id = ?';
//$pdo = CommunityDB::pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$access_token, $access_token_expire_at, $refresh_token,
        $refresh_token_expire_at, $now->toDateTimeString(),
        $user['user_id']]);

    // get user role
    $roles = Util::getUserRole($user['user_id']);


    $user_info = [
        'user_id'=> $user['user_id'],
        'username'=> $user['username'],
        'access_token'=> $access_token,
        'access_token_expire_at'=>$access_token_expire_at,
        'refresh_token'=>$refresh_token,
        'refresh_token_expire_at'=>$refresh_token_expire_at,
        'timezone'=>Util::getTimezone(),
        'roles'=> $roles
    ];
    Util::respond($user_info);
} catch (Exception $e){
    $message = ['error' => 'unexpected failure, please try again'];
    Util::respond($message);
}
