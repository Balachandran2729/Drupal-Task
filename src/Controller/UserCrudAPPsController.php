<?php

namespace Drupal\user_crud\Controller;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Drupal\user_crud\Service\UserCrudVerifyTokens;
use Drupal\user_crud\Service\UserCrudAPPsService;
use Drupal\user_crud\Service\JwtAuthService;


class UserCrudAPPsController extends ControllerBase {

    private UserCrudAPPsService $userCrudAPPsService;
    private UserCrudVerifyTokens $tokenService;
    private JwtAuthService $jwtAuthService;

    public function __construct(UserCrudAPPsService $userCrudAPPsService, UserCrudVerifyTokens $tokenService, JwtAuthService $jwtAuthService) {
        $this->userCrudAPPsService = $userCrudAPPsService;
        $this->tokenService = $tokenService;
        $this->jwtAuthService = $jwtAuthService;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('user_crud.apps_service'),
            $container->get('user_crud.verify_tokens'),
            $container->get('user_crud.jwt_auth'),
        );
    }

    public function getAppData (Request $request) {

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

        $requestData = json_decode($request->getContent(), TRUE) ?: [];
        $limit = $requestData['limit'] ?? 10;
        $skip = $requestData['skip'] ?? 0;

        $appData = $this->userCrudAPPsService->getAppData($limit, $skip);

        return new JsonResponse($appData);
    }

    public function createCartAppData (Request $request) {

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

        $requestData = json_decode($request->getContent(), TRUE) ?: [];
        $id = $requestData['id'] ?? '';
        $title = $requestData['title'] ?? '';
        $image = $requestData['image'] ?? '';   

        if (empty($id) || empty($title) || empty($image)) {
            return new JsonResponse([
                'error' => 'Missing required fields: id, title, or image.',
            ], 400);
        }

        $cartItem = $this->userCrudAPPsService->createCartAppData($id, $title, $image);

        return new JsonResponse([
            'message' => 'Product added to cart successfully.',
        ], 201);
    }

    public function getCartAppData (Request $request) {
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

        return new JsonResponse([
            'cart' => $this->userCrudAPPsService->getCartAppData(),
        ]);

    }

    public function updateCartAppData($id, Request $request) {
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

        $requestData = json_decode($request->getContent(), TRUE) ?: [];
        $count = $requestData['count'] ?? NULL;

        if (!is_int($count) || $count < 1) {
            return new JsonResponse([
                'error' => 'Count must be a positive integer.',
            ], 400);
        }

        $cartItem = $this->userCrudAPPsService->updateCartAppData($id, $count);

        if (!$cartItem) {
            return new JsonResponse([
                'error' => 'Cart product not found.',
            ], 404);
        }

        return new JsonResponse([
            'message' => 'Cart product updated successfully.',
            'item' => $cartItem,
        ]);
    }

    public function deleteCartAppData($id, Request $request) {
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

        if (!$this->userCrudAPPsService->deleteCartAppData($id)) {
            return new JsonResponse([
                'error' => 'Cart product not found.',
            ], 404);
        }

        return new JsonResponse([
            'message' => 'Cart product deleted successfully.',
        ]);
    }


}