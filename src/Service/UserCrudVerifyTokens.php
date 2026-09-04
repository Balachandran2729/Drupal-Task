<?php

namespace Drupal\user_crud\Service;

class UserCrudVerifyTokens {

    public function generateToken() {

        $static_key = 'jv3784gfd454b5rrhyufhrbr874';

        $payload = time();

        $signature = hash_hmac('sha256', $payload, $static_key);

        $token = base64_encode($payload . '.' . $signature);

        return $token;

    }

    public function verifyToken($token) {

        $static_key = 'jv3784gfd454b5rrhyufhrbr874';
        
        $decoded_token = base64_decode($token);

        list($payload, $signature) = explode('.', $decoded_token);

        $expected_signature = hash_hmac('sha256', $payload, $static_key);

        if ($signature === $expected_signature) {
            return true;
        } else {
            return false;
        }
    }


}