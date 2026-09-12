<?php

namespace Drupal\user_crud\Service;

use Drupal\user_crud\Service\JwtAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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

    public function validateAccessToken(Request $request, JwtAuthService $jwtAuthService, string $context): ?JsonResponse {

        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            \Drupal::logger('user_crud')->error($context . ' failed: Authorization header is missing or not in Bearer format.');
            return new JsonResponse([
                'error' => 'Authorization header must use Bearer token format.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info($context . ': Authorization header found.');

        $jwtPayload = $jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || ($jwtPayload['type'] ?? '') !== 'access') {
            \Drupal::logger('user_crud')->error($context . ' failed: Invalid or expired access token.');
            return new JsonResponse([
                'error' => 'Invalid or expired access token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info($context . ': Access token is valid.');

        $token = $this->generateToken();
        $token_verify = $this->verifyToken($token);

        if (!$token_verify) {
            \Drupal::logger('user_crud')->error($context . ' failed: Token verification failed.');
            return new JsonResponse([
                'error' => 'Invalid token , Check The Token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info($context . ': Token verification successful.');

        return null;
    }


}