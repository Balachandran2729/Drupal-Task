<?php

namespace Drupal\user_crud\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Drupal\user_crud\Service\UserCrudVerifyTokens;
use Drupal\user_crud\Service\UserCrudService;
use Drupal\user_crud\Service\JwtAuthService;


class UserCrudRestAPIController extends ControllerBase {

    private UserCrudService $userCrudService;
     private UserCrudVerifyTokens $tokenService;
    private JwtAuthService $jwtAuthService;

    public function __construct(UserCrudService $userCrudService, UserCrudVerifyTokens $tokenService, JwtAuthService $jwtAuthService) {
        $this->userCrudService = $userCrudService;
        $this->tokenService = $tokenService;
        $this->jwtAuthService = $jwtAuthService;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('user_crud.service'),
            $container->get('user_crud.verify_tokens'),
            $container->get('user_crud.jwt_auth'),
        );
    }

    public function userLogin(Request $request) {

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse([
                'error' => 'Invalid JSON body.',
            ], 400);
        }

        $token = $this->tokenService->generateToken();
        
        $token_verify = $this->tokenService->verifyToken($token);

        if (!$token_verify) {
            return new JsonResponse([
                'error' => 'Invalid token , Check The Token.',
            ], 401);
        }



        $username = trim($data['name'] ?? '');
        $password = $data['password'] ?? '';

        if ($username === '' || $password === '') {
            return new JsonResponse([
                'error' => 'Name and password are required.',
            ], 400);
        }

        return $this->userCrudService->userLogin($username, $password);
    }

    public function restApiRead(Request $request) 
    {      
        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return new JsonResponse([
                'error' => 'Authorization header must use Bearer token format.',
            ], 401);
        }

        $jwtPayload = $this->jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || ($jwtPayload['type'] ?? '') !== 'access') {
            return new JsonResponse([
                'error' => 'Invalid or expired access token.',
            ], 401);
        }

        $token = $this->tokenService->generateToken();
        
        $token_verify = $this->tokenService->verifyToken($token);

        if (!$token_verify) {
            return new JsonResponse([
                'error' => 'Invalid token , Check The Token.',
            ], 401);
        }

        $storage = $this->entityTypeManager()->getStorage('user');

        $users = $storage->loadMultiple();

        $data=[];

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

        $token = $this->tokenService->generateToken();

        $token_verify = $this->tokenService->verifyToken($token);
        if (!$token_verify) {
            return new JsonResponse(['error' => 'Invalid token.',], 401);
        }

        return $this->userCrudService->restAPIupdateUserPatch($user, $data);
        
    }


    public function restAPIdelete($user, Request $request) {

        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return new JsonResponse([
                'error' => 'Authorization header must use Bearer token format.',
            ], 401);
        }

        $jwtPayload = $this->jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || ($jwtPayload['type'] ?? '') !== 'access') {
            return new JsonResponse([
                'error' => 'Invalid or expired access token.',
            ], 401);
        }

        $user = User::load($user);

        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found.',
            ], 404);
        }

        $token = $this->tokenService->generateToken();

        $token_verify = $this->tokenService->verifyToken($token);

        if (!$token_verify) {
            return new JsonResponse([
                'error' => 'Invalid token.',
            ], 401);
        }

        return $this->userCrudService->restAPIdeleteUser($user);

    }

    
    public function restAPIeditPut($user,Request $request) {

        $user = User::load($user);

        $token = $this->tokenService->generateToken();


        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found',
            ], 404);
        }

        $token_verify = $this->tokenService->verifyToken($token);

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

        return $this->userCrudService->restAPIupdateUserPut($user, $data);
       
    }

    

    public function restAPIcreate(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $token = $this->tokenService->generateToken();

        $token_verify = $this->tokenService->verifyToken($token);

        if (!$token_verify) {
            return new JsonResponse([
                'error' => 'Invalid token.',
            ], 401);
        }

        if (!is_array($data)) {
            return new JsonResponse([
                'error' => 'Invalid JSON body.',
            ], 400);
        }

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

        return $this->userCrudService->restAPIcreateUser( $username, $email, $password,$phone_number );

    }
    
}