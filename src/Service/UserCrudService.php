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
}