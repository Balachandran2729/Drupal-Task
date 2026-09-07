<?php

namespace Drupal\user_crud\Service;

use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user_crud\Service\JwtAuthService;

class UserCrudService
{
    private JwtAuthService $jwtAuthService;

    public function __construct(JwtAuthService $jwtAuthService)
    {
        $this->jwtAuthService = $jwtAuthService;
    }

    public function userLogin($username, $password) {
        
        $user = user_load_by_name($username);

        if (!$user || !$user->isActive() || !\Drupal::service('password')->check($password, $user->getPassword())) {
            return new JsonResponse([
                'error' => 'Invalid username or password.',
            ], 401);
        }

        $access_token = $this->jwtAuthService->generateAccessToken($user->id(), $user->getAccountName());
        $refresh_token = $this->jwtAuthService->generateRefreshToken($user->id(), $user->getAccountName());

        return new JsonResponse([
            'message' => 'Login successful.',
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
        ], 200);

    }

    public function restAPIcreateUser( $username, $email, $password, $phone_number ) {

        $user = User::create([
            'name' => $username,
            'mail' => $email,
            'field_phone_number' => $phone_number,
            'status' => 1,
        ]);

        $user->setPassword($password);

        $user->save();

        $access_token = $this->jwtAuthService->generateAccessToken($user->id(), $user->getAccountName());
        
        $refresh_token = $this->jwtAuthService->generateRefreshToken($user->id(), $user->getAccountName());

        return new JsonResponse([
            'message' => 'User created successfully.',
            'user_id' => $user->id(),
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
        ], 201);
    }

    public function restAPIdeleteUser($user) {
        if ($user) {
            $user->delete();

            return new JsonResponse([
                'message' => 'User deleted successfully.',
            ], 200);
        }
    }

    public function restAPIupdateUserPatch($user, $data) {

        if (isset($data['name'])) {
            $user->setUsername($data['name']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            $user->setPassword($data['password']);
        }

        if (isset($data['status'])) {
            $user->set('status', $data['status']);
        }

        if (isset($data['phone'])) {
            $user->set('field_phone_number', $data['phone']);
        }

        $user->save();

        return new JsonResponse(['message' => 'User updated successfully'], 200);
    }

    public function restAPIupdateUserPut($user, $data)
    {
        $user->setUsername($data['name']);
        $user->setEmail($data['email']);
        $user->set('field_phone_number', $data['phone']);
        $user->set('status', $data['status'] ?? 1);

        $user->save();

        return new JsonResponse([
            'message' => 'User updated successfully'
        ], 200);
    }


}