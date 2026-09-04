<?php

namespace Drupal\user_crud\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class UserCrudRestAPIController extends ControllerBase {

    public function restAPIedit($user,Request $request) {

        $user = User::load($user);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

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

        return new JsonResponse(['message' => 'User updated successfully'], 201);

    }



    public function restAPIeditPut($user,Request $request) {

        $user = User::load($user);

        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found',
            ], 404);
        }

        $data = json_decode($request->getContent(), TRUE);

        if (!is_array($data)) {
            return new JsonResponse([
                'error' => 'Invalid JSON body',
            ], 400);
        }

        if (
            !isset($data['name']) ||
            !isset($data['email']) ||
            !isset($data['phone'])
        ) {
            return new JsonResponse([
                'error' => 'Name, email and phone are required',
            ], 400);
        }

        $user->setUsername($data['name']);
        $user->setEmail($data['email']);
        $user->set('field_phone_number', $data['phone']);
        $user->set('status', isset($data['status']) ? $data['status'] : 1);
        $user->save();

        return new JsonResponse(['message' => 'User updated successfully'], 201);

    }

}