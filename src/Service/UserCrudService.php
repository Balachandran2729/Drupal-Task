<?php

namespace Drupal\user_crud\Service;

use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserCrudService
{
    public function restAPIcreateUser( $username, $email, $password, $phone_number ) {

        $user = User::create([
            'name' => $username,
            'mail' => $email,
            'field_phone_number' => $phone_number,
            'status' => 1,
        ]);

        $user->setPassword($password);

        $user->save();

        return new JsonResponse([
            'message' => 'User created successfully.',
            'user_id' => $user->id(),
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