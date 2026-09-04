<?php

namespace Drupal\user_crud\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserCrudRestAPIController extends ControllerBase
{
    public function restAPIcreate(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $username = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $phone_number = trim($data['phone_number'] ?? '');

        // Validation.
    if ($username === '' || $email === '' || $password === '') {
        return new JsonResponse([
            'error' => 'Name, email and password are required.',
        ], 400);
    }

        // Create user.
        $user = $this->entityTypeManager()->getStorage('user')->create([
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