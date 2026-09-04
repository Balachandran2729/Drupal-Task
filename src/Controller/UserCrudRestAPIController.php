<?php

namespace Drupal\user_crud\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Drupal\user_crud\Service\UserCrudVerifyTokens;
use Drupal\user_crud\Service\UserCrudService;


class UserCrudRestAPIController extends ControllerBase {

    public function restApiRead() 
    {
        $token_service = new UserCrudVerifyTokens();

        $token_verify = $token_service->verifyToken($token);

        if (!$token_verify) {
            return new JsonResponse([
                'error' => 'Invalid token.',
            ], 401);
        }

        $storage = $this->entityTypeManager()->getStorage('user');

        $users = $storage->loadMultiple();

        $data=[];

        $token = $token_service->generateToken();

        foreach($users as $user) {
            if($user->id() ==0) {
                continue;
            }
            $data[] = [
                'id' => $user->id(),
                'username' => $user->getAccountName(),
                'email' => $user->getEmail(),
                'phone_number' => $user->get('field_phone_number')->value,
                'status' => $user->isActive() ? 'Active' : 'Blocked',
            ];

        }

        return new JsonResponse($data);

    }

    public function restAPIedit($user,Request $request) {

        $user = User::load($user);
        $data = json_decode($request->getContent(), true);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $token_service = new UserCrudVerifyTokens();
        $user_service = new UserCrudService();

        $token = $token_service->generateToken();

        $token_verify = $token_service->verifyToken($token);
        if (!$token_verify) {
            return new JsonResponse(['error' => 'Invalid token.',], 401);
        }

        return $user_service->restAPIupdateUserPatch($user, $data);
        
    }


    public function restAPIdelete($user) {

        $user = User::load($user);

        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found.',
            ], 404);
        }

        $token_service = new UserCrudVerifyTokens();
        $user_service = new UserCrudService();

        $token = $token_service->generateToken();

        $token_verify = $token_service->verifyToken($token);

        if (!$token_verify) {
            return new JsonResponse([
                'error' => 'Invalid token.',
            ], 401);
        }

        return $user_service->restAPIdeleteUser($user);

    }

    
    public function restAPIeditPut($user,Request $request) {

        $user = User::load($user);

        $token_service = new UserCrudVerifyTokens();
        $user_service = new UserCrudService();

        $token = $token_service->generateToken();


        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found',
            ], 404);
        }

        $token_verify = $token_service->verifyToken($token);

        if (!$token_verify) {
            return new JsonResponse([
                'error' => 'Invalid token.',
            ], 401);
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

        return $user_service->restAPIupdateUserPut($user, $data);
       
    }

    

    public function restAPIcreate(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $token_service = new UserCrudVerifyTokens();
        $user_service = new UserCrudService();

        $token = $token_service->generateToken();

        $username = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $phone_number = trim($data['phone_number'] ?? '');

        $token_verify = $token_service->verifyToken($token);

        if (!$token_verify) {
            return new JsonResponse([
                'error' => 'Invalid token.',
            ], 401);
        }

        // Validation.
        if ($username === '' || $email === '' || $password === '') {
            return new JsonResponse([
                'error' => 'Name, email and password are required.',
            ], 400);
        }

        return $user_service->restAPIcreateUser( $username, $email, $password,$phone_number );

    }
    
}