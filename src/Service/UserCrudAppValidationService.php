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


    public function validateCreateUserFields($data) {

        if (!is_array($data)) {
            return new JsonResponse([
                'error' => 'Invalid JSON body.',
            ], 400);
        }

        $username = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $phone_number = trim($data['phone_number'] ?? '');

        // Required field validation.
        if ($username === '' || $email === '' || $password === '') {
            return new JsonResponse([
                'error' => 'Name, email and password are required.',
            ], 400);
        }

        // Username validation.
        if (strlen($username) < 3) {
            return new JsonResponse([
                'error' => 'Username must be at least 3 characters long.',
            ], 400);
        }

        // Check whether username already exists.
        if (user_load_by_name($username)) {
            return new JsonResponse([
                'error' => 'This username is already taken.',
            ], 400);
        }

        // Check whether email already exists.
        if (user_load_by_mail($email)) {
            return new JsonResponse([
                'error' => 'This email address is already registered.',
            ], 400);
        }

        // Password validation.
        if (!preg_match(
            '/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}$/',
            $password
        )) {
            return new JsonResponse([
                'error' => 'Password must be at least 8 characters and contain one uppercase letter, one number, and one special character.',
            ], 400);
        }

        // Phone validation.
        if (!preg_match('/^\+65[0-9]{8}$/', $phone_number)) {
            return new JsonResponse([
                'error' => 'Phone number must start with +65 and contain 8 digits.',
            ], 400);
        }

        return NULL;
    }
}
