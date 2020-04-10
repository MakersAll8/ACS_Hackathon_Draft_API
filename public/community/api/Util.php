<?php

namespace Api;

use function array_key_exists;
use Carbon\Carbon;
use Exception;
use function strtolower;
use function strtoupper;

require_once 'init.php';

class Util
{
    protected $input_array;
    static protected $timezone = 'Australia/Melbourne';

    public function __construct()
    {
//        $refresh_token = $_SERVER['HTTP_REFRESH_TOKEN'];
//        $access_token = $_SERVER['HTTP_ACCESS_TOKEN'];

        if ($_SERVER['REQUEST_METHOD'] == 'POST'
            && isset($_SERVER['CONTENT_TYPE'])
            && stripos($_SERVER["CONTENT_TYPE"], 'json')
        ) {
            $json_string = file_get_contents('php://input');
            $this->input_array = json_decode($json_string, true);
        }
    }

    public function getInput()
    {
        return $this->input_array;
    }

    public static function respond($message)
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($message,
            JSON_UNESCAPED_UNICODE);
        die;
    }

    public static function requestType($type)
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) != strtoupper($type)) {
            $message = ['error' => 'Please access this api using ' . strtolower($type) . ' method'];
            Util::respond($message);
            die;
        }
    }

    public static function inputIsJSON()
    {
        if (
            isset($_SERVER['CONTENT_TYPE'])
            && stripos($_SERVER["CONTENT_TYPE"], 'json')
        ) {
            return new Util();
        } else {
            $message = ['error' => 'content type received is not JSON'];
            Util::respond($message);
            die;
        }
    }

    public static function verifyAccessToken()
    {
        $access_token = $_SERVER['HTTP_ACCESS_TOKEN'];
        if (empty($access_token)) {
            return ['error' => 'access token is not received'];
        }

        $sql = 'SELECT * FROM User WHERE access_token = ?';
        $pdo = CommunityDB::pdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$access_token]);
        $result = $stmt->fetchAll();
        if (!$result) {
            return ['error' => 'access token invalid'];
        }
        if (sizeof($result) != 1) {
            return ['error' => 'access token not unique'];
        }
        $user = $result[0];

        // access token expired
        $now = Carbon::now(Util::getTimezone());
        $access_token_expire_at = Carbon::parse($user['access_token_expire_at']);
        if ($now->greaterThan($access_token_expire_at)) {
            return ['error' => 'access token expired'];
        }

        $user['roles'] = Util::getUserRole($user['user_id']);

        return $user;

    }

    public static function refreshToken()
    {
        $refresh_token = $_SERVER['HTTP_REFRESH_TOKEN'];
        if (empty($refresh_token)) {
            return ['error' => 'refresh token is not received'];
        }

        $sql = 'SELECT * FROM User WHERE refresh_token = ?';
        $pdo = CommunityDB::pdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$refresh_token]);
        $result = $stmt->fetchAll();
        if (!$result) {
            return ['error' => 'refresh token invalid'];
        }
        if (sizeof($result) != 1) {
            return ['error' => 'refresh token not unique'];
        }
        $user = $result[0];

        // refresh token expired
        $now = Carbon::now(Util::getTimezone());
        $refresh_token_expire_at = Carbon::parse($user['refresh_token_expire_at']);
        if ($now->greaterThan($refresh_token_expire_at)) {
            return ['error' => 'refresh token expired, please log in again'];
        }

        // update access token
        $sql = 'UPDATE User 
                SET access_token = ?, access_token_expire_at = ?,
                refresh_token = ? , refresh_token_expire_at = ?
                WHERE user_id = ?';
        $access_token = Util::createToken();
        $access_token_expire_at = $now->addMinute(15)->toDateTimeString();

        $refresh_token = Util::createToken();
        $refresh_token_expire_at = $now->addHours(2)->toDateTimeString();

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $access_token,
            $access_token_expire_at,
            $refresh_token,
            $refresh_token_expire_at,
            $user['user_id']
        ]);

        return [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'access_token' => $access_token,
            'access_token_expire_at' => $access_token_expire_at,
            'refresh_token' => $refresh_token,
            'refresh_token_expire_at' => $refresh_token_expire_at,
            'timezone' => Util::getTimezone(),
        ];

    }

    public static function getTimezone()
    {
        return Util::$timezone;
    }

    public static function createToken()
    {
        return bin2hex(random_bytes(64));
    }

    public static function insert($table, $input)
    {
        $bd = [];
        $values = '';
        $frontSeparator = '';
        $sql = "INSERT INTO $table (";

        foreach ($input as $k => $v) {
            if (!empty($input[$k])) {
                $sql .= $frontSeparator . " $k ";
                $bd[] = $input[$k];
                $values .= $frontSeparator . '?';
                $frontSeparator = ',';
            }
        }

        $sql .= ') VALUES (';
        $sql .= $values;
        $sql .= ')';
        $pdo = CommunityDB::pdo();
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($bd);

        if ($result) {
            return ['success' => 'inserted successfully',
                'id' => $pdo->lastInsertId()];
        } else {
            throw new Exception('insert failed');
        }
    }

    public static function update($table, $input, $customId = null)
    {
        if($customId === null){
            if(!array_key_exists('id', $input)) throw new Exception('id not specified');
            $updateKeyName = 'id';
        } else {
            if(!array_key_exists('key', $customId)) throw new Exception('custom id not specified');
            $updateKeyName = $customId['key'];
        }

        $bd = [];
        $frontSeparator = '';
        $sql = "UPDATE $table SET ";



        foreach ($input as $k => $v) {
            if (!empty($input[$k]) && $k != $updateKeyName) {
                $sql .= $frontSeparator . " $k = ? ";
                $bd[] = $input[$k];
                $frontSeparator = ',';
            }
        }

        $sql .= ' WHERE ';
        $sql .=  $updateKeyName .' = ? ';

        if($customId === null){
            $bd[] = $input['id'];
        } else {
            $bd[] = $customId['value'];
        }

        $pdo = CommunityDB::pdo();
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($bd);

        if ($result) {
            return ['success' => 'updated successfully'];
        } else {
            throw new Exception('update failed');
        }
    }


    public static function getUserRole($userID)
    {
        $pdo = CommunityDB::pdo();
        // get user role
        $sql = "SELECT ur.role_id, r.role_name FROM UserRole ur LEFT JOIN Role r ON ur.role_id = r.role_id
            WHERE ur.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userID]);
        return $stmt->fetchAll();

    }

    public static function getSalesOrder($orderID){
        $pdo = CommunityDB::pdo();
        $sql = "SELECT * FROM Sales WHERE so_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderID]);
        return $stmt->fetch();
    }

    public static function productById($productID){
        $pdo = CommunityDB::pdo();
        $sql = "SELECT * FROM Product WHERE product_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productID]);
        return $stmt->fetch();
    }

    public static function salesLineById($lineID){
        $pdo = CommunityDB::pdo();
        $sql = "SELECT * FROM SalesLine WHERE line_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lineID]);
        return $stmt->fetch();
    }

    public static function removeSalesLine($lineID){
        $pdo = CommunityDB::pdo();
        $sql = "DELETE FROM SalesLine WHERE line_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$lineID]);
    }
}