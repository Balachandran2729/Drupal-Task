<?php

namespace Drupal\user_crud\Service;

use Drupal\Core\Site\Settings;

class JwtAuthService {

    private string $secretKey;

    public function __construct() {

        $this->secretKey = Settings::get('jwt_secret_key','jv3784gfd454b5rrhyufhrbr874');

    }

    public function base64UrlEncode (string $data): string {

        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));

    }

    public function base64UrlDecode (string $data): string {

        $remainder = strlen($data) % 4;

        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    public function generateToken(array $payload): string {

        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);

        $base64Header = $this->base64UrlEncode($header);

        $base64Payload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secretKey, true);

        $base64Signature = $this->base64UrlEncode($signature);

        $token = $base64Header . "." . $base64Payload . "." . $base64Signature;

        return $token;

    } 

    public function generateAccessToken(int $uid , string $username): string {

        return $this->generateToken([
        'iat' => time(),
        'exp' => time() + (15 * 60), 
        'uid' => $uid,
        'username' => $username,
        'type' => 'access',
        ]);

    }

  public function generateRefreshToken(int $uid , string $username): string {

        return $this->generateToken([
        'iat' => time(),
        'exp' => time() + (7 * 24 * 60 * 60),
        'uid' => $uid,
        'username' => $username,
        'type' => 'refresh',
        ]);

    }

    public function decodeToken(string $token): ?array {

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
        return NULL; 
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        $expectedSignature = $this->base64UrlEncode(
        hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secretKey, true)
        );

        if (!hash_equals($expectedSignature, $base64Signature)) {
        return NULL; 
        }

        $payload = json_decode($this->base64UrlDecode($base64Payload), TRUE);

        if (isset($payload['exp']) && $payload['exp'] < time()) {
        return NULL;
        }

        return $payload;
    }

}