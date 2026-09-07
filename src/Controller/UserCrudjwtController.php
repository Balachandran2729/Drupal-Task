<?php

namespace Drupal\user_crud\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Drupal\user_crud\Service\JwtAuthService;

class UserCrudjwtController extends ControllerBase {

    private JwtAuthService $jwtAuthService;

    public function __construct(JwtAuthService $jwtAuthService) {
        $this->jwtAuthService = $jwtAuthService;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('user_crud.jwt_auth')
        );
    }

    public function refreshToken(Request $request): JsonResponse {
        
        $data = json_decode($request->getContent(), TRUE);
        $refreshToken = $data['refresh_token'] ?? '';

        $payload = $this->jwtAuthService->decodeToken($refreshToken);

        if (!$payload || ($payload['type'] ?? '') !== 'refresh') {
        return new JsonResponse(['error' => 'Invalid or expired refresh token'], 401);
        }

        return new JsonResponse([
        'access_token' => $this->jwtAuthService->generateAccessToken((int)$payload['uid']),
        ]);
    }

}