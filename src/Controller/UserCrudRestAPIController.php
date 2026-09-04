<?php

namespace Drupal\user_crud\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;


class UserCrudRestAPIController extends ControllerBase { 

    public function restAPIdelete($user)
        {
            $user = User::load($user);

            if ($user) {
                $user->delete();

                return new JsonResponse([
                    'message' => 'User deleted successfully.',
                ], 200);
            }

            return new JsonResponse([
                'message' => 'User not found.',
            ], 404);
        }

}