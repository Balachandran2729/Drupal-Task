<?php

namespace Drupal\user_crud\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserCrudAppValidationService {

    private JwtAuthService $jwtAuthService;

    public function __construct(JwtAuthService $jwtAuthService) {
        $this->jwtAuthService = $jwtAuthService;
    }

    public function getAuthenticatedUserId(Request $request): ?int {
        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return NULL;
        }

        $jwtPayload = $this->jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || !isset($jwtPayload['uid']) || !is_numeric($jwtPayload['uid'])) {
            return NULL;
        }

        return (int) $jwtPayload['uid'];
    }

    public function validateAuthenticatedUser(Request $request, string $operation): array|JsonResponse {
        $uid = $this->getAuthenticatedUserId($request);

        if ($uid === NULL) {
            \Drupal::logger('user_crud')->error($operation . ' failed: User ID missing from valid access token.');
            return new JsonResponse([
                'error' => 'Please Log In to Continue.',
            ], 401);
        }

        return ['uid' => $uid];
    }

   public function validateCartRequestFields(mixed $id,mixed $count = NULL): ?JsonResponse {

        if (empty($id) || !ctype_digit((string) $id)) {
            return new JsonResponse([
                'error' => 'Please provide a valid ID.',
            ], 400);
        }

        if ($count !== NULL && (!is_int($count) || $count < 1)) {
            return new JsonResponse([
                'error' => 'Please provide a valid quantity.',
            ], 400);
        }

        return NULL;
    }
}
