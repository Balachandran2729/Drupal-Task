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

        \Drupal::logger('user_crud')->info('getAppData called.');

        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            \Drupal::logger('user_crud')->warning('getAppData failed: Authorization header is missing or not in Bearer format.');
            return new JsonResponse([
                'error' => 'Authorization header must use Bearer token format.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('getAppData: Authorization header found.');

        $jwtPayload = $this->jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || ($jwtPayload['type'] ?? '') !== 'access') {
            \Drupal::logger('user_crud')->warning('getAppData failed: Invalid or expired access token.');
            return new JsonResponse([
                'error' => 'Invalid or expired access token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('getAppData: Access token is valid.');

        $token = $this->tokenService->generateToken();
        $token_verify = $this->tokenService->verifyToken($token);

        if (!$token_verify) {
            \Drupal::logger('user_crud')->warning('getAppData failed: Token verification failed.');
            return new JsonResponse([
                'error' => 'Invalid token , Check The Token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('getAppData: Token verification successful.');

        $requestData = json_decode($request->getContent(), TRUE) ?: [];
        $limit = $requestData['limit'] ?? 10;
        $skip = $requestData['skip'] ?? 0;

        \Drupal::logger('user_crud')->info('getAppData: Request data received with limit ' . $limit . ' and skip ' . $skip . '.');

        $appData = $this->userCrudAPPsService->getAppData($limit, $skip);

        \Drupal::logger('user_crud')->info('getAppData completed successfully.');

        return new JsonResponse($appData);
    }

    public function createCartAppData (Request $request) {

        \Drupal::logger('user_crud')->info('createCartAppData called.');

        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            \Drupal::logger('user_crud')->warning('createCartAppData failed: Authorization header is missing or not in Bearer format.');
            return new JsonResponse([
                'error' => 'Authorization header must use Bearer token format.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('createCartAppData: Authorization header found.');

        $jwtPayload = $this->jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || ($jwtPayload['type'] ?? '') !== 'access') {
            \Drupal::logger('user_crud')->warning('createCartAppData failed: Invalid or expired access token.');
            return new JsonResponse([
                'error' => 'Invalid or expired access token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('createCartAppData: Access token is valid.');

        $token = $this->tokenService->generateToken();
        $token_verify = $this->tokenService->verifyToken($token);

        if (!$token_verify) {
            \Drupal::logger('user_crud')->warning('createCartAppData failed: Token verification failed.');
            return new JsonResponse([
                'error' => 'Invalid token , Check The Token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('createCartAppData: Token verification successful.');

        $requestData = json_decode($request->getContent(), TRUE) ?: [];
        $id = $requestData['id'] ?? '';
        $title = $requestData['title'] ?? '';
        $image = $requestData['image'] ?? '';   

        $requestDataJson = json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        \Drupal::logger('user_crud')->info('createCartAppData: Request data received: ' . ($requestDataJson ?: '[]'));

        if (empty($id) || empty($title) || empty($image)) {
            \Drupal::logger('user_crud')->warning('createCartAppData failed: Missing required fields.');
            return new JsonResponse([
                'error' => 'Missing required fields: id, title, or image.',
            ], 400);
        }

        \Drupal::logger('user_crud')->info('createCartAppData: Product details are valid. Calling service to add cart item.');

        $cartItem = $this->userCrudAPPsService->createCartAppData($id, $title, $image);

        \Drupal::logger('user_crud')->info('createCartAppData completed successfully.');

        return new JsonResponse([
            'message' => 'Product added to cart successfully.',
        ], 201);
    }

    public function registerToken(Request $request) {
        \Drupal::logger('user_crud')->info('registerToken called.');

        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            \Drupal::logger('user_crud')->warning('registerToken failed: Authorization header is missing or not in Bearer format.');
            return new JsonResponse([
                'error' => 'Authorization header must use Bearer token format.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('registerToken: Authorization header found.');

        $jwtPayload = $this->jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || ($jwtPayload['type'] ?? '') !== 'access') {
            \Drupal::logger('user_crud')->warning('registerToken failed: Invalid or expired access token.');
            return new JsonResponse([
                'error' => 'Invalid or expired access token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('registerToken: Access token is valid.');

        $token = $this->tokenService->generateToken();
        $token_verify = $this->tokenService->verifyToken($token);

        if (!$token_verify) {
            \Drupal::logger('user_crud')->warning('registerToken failed: Token verification failed.');
            return new JsonResponse([
                'error' => 'Invalid token , Check The Token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('registerToken: Token verification successful.');

        $requestData = json_decode($request->getContent(), TRUE);

        if (!is_array($requestData)) {
            \Drupal::logger('user_crud')->warning('registerToken failed: Invalid JSON body.');
            return new JsonResponse([
                'error' => 'Invalid JSON body.',
            ], 400);
        }

        $name = trim((string) ($requestData['name'] ?? ''));
        $id = $requestData['id'] ?? '';
        $device = trim((string) ($requestData['device'] ?? ''));
        $tokenValue = trim((string) ($requestData['token'] ?? ''));

        \Drupal::logger('user_crud')->info('registerToken: Request data received: @request', [
            '@request' => json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        if ($name === '' || $id === '' || $device === '' || $tokenValue === '') {
            \Drupal::logger('user_crud')->warning('registerToken failed: Missing required fields.');
            return new JsonResponse([
                'error' => 'Name, id, device, and token are required.',
            ], 400);
        }

        $registeredToken = $this->userCrudAPPsService->registerDeviceToken($name, $id, $device, $tokenValue);

        \Drupal::logger('user_crud')->info('registerToken completed successfully.');

        return new JsonResponse([
            'message' => 'Device token registered successfully.',
            'data' => $registeredToken,
        ], 201);
    }

    public function getRegisteredTokens() {
        \Drupal::logger('user_crud')->info('getRegisteredTokens called.');

        $registeredTokens = $this->userCrudAPPsService->getRegisteredTokens();

        \Drupal::logger('user_crud')->info('getRegisteredTokens completed successfully.');

        return new JsonResponse([
            'data' => $registeredTokens,
            'count' => count($registeredTokens),
        ]);
    }

    public function getCartAppData (Request $request) {
        \Drupal::logger('user_crud')->info('getCartAppData called.');

        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            \Drupal::logger('user_crud')->warning('getCartAppData failed: Authorization header is missing or not in Bearer format.');
            return new JsonResponse([
                'error' => 'Authorization header must use Bearer token format.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('getCartAppData: Authorization header found.');

        $jwtPayload = $this->jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || ($jwtPayload['type'] ?? '') !== 'access') {
            \Drupal::logger('user_crud')->warning('getCartAppData failed: Invalid or expired access token.');
            return new JsonResponse([
                'error' => 'Invalid or expired access token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('getCartAppData: Access token is valid.');

        $cartData = $this->userCrudAPPsService->getCartAppData();

        \Drupal::logger('user_crud')->info('getCartAppData completed successfully.');

        return new JsonResponse([
            'cart' => $cartData,
        ]);

    }

    public function updateCartAppData($id, Request $request) {
        \Drupal::logger('user_crud')->info('updateCartAppData called for id ' . $id . '.');

        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            \Drupal::logger('user_crud')->warning('updateCartAppData failed: Authorization header is missing or not in Bearer format.');
            return new JsonResponse([
                'error' => 'Authorization header must use Bearer token format.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('updateCartAppData: Authorization header found.');

        $jwtPayload = $this->jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || ($jwtPayload['type'] ?? '') !== 'access') {
            \Drupal::logger('user_crud')->warning('updateCartAppData failed: Invalid or expired access token.');
            return new JsonResponse([
                'error' => 'Invalid or expired access token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('updateCartAppData: Access token is valid.');

        $requestData = json_decode($request->getContent(), TRUE) ?: [];
        $count = $requestData['count'] ?? NULL;

        $requestDataJson = json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        \Drupal::logger('user_crud')->info('updateCartAppData: Request data received: ' . ($requestDataJson ?: '[]'));

        if (!is_int($count) || $count < 1) {
            \Drupal::logger('user_crud')->warning('updateCartAppData failed: Count value is missing or invalid.');
            return new JsonResponse([
                'error' => 'Count must be a positive integer.',
            ], 400);
        }

        \Drupal::logger('user_crud')->info('updateCartAppData: Updating cart item with count @count.');

        $cartItem = $this->userCrudAPPsService->updateCartAppData($id, $count);

        if (!$cartItem) {
            \Drupal::logger('user_crud')->warning('updateCartAppData failed: Cart product not found for id @id.');
            return new JsonResponse([
                'error' => 'Cart product not found.',
            ], 404);
        }

        \Drupal::logger('user_crud')->info('updateCartAppData completed successfully for id ' . $id . '.');

        return new JsonResponse([
            'message' => 'Cart product updated successfully.',
            'item' => $cartItem,
        ]);
    }

    public function deleteCartAppData($id, Request $request) {
        \Drupal::logger('user_crud')->info('deleteCartAppData called for id ' . $id . '.');

        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            \Drupal::logger('user_crud')->warning('deleteCartAppData failed: Authorization header is missing or not in Bearer format.');
            return new JsonResponse([
                'error' => 'Authorization header must use Bearer token format.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('deleteCartAppData: Authorization header found.');

        $jwtPayload = $this->jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || ($jwtPayload['type'] ?? '') !== 'access') {
            \Drupal::logger('user_crud')->warning('deleteCartAppData failed: Invalid or expired access token.');
            return new JsonResponse([
                'error' => 'Invalid or expired access token.',
            ], 401);
        }

        \Drupal::logger('user_crud')->info('deleteCartAppData: Access token is valid.');

        if (!$this->userCrudAPPsService->deleteCartAppData($id)) {
            \Drupal::logger('user_crud')->warning('deleteCartAppData failed: Cart product not found for id ' . $id . '.');
            return new JsonResponse([
                'error' => 'Cart product not found.',
            ], 404);
        }

        \Drupal::logger('user_crud')->info('deleteCartAppData completed successfully for id ' . $id . '.');

        return new JsonResponse([
            'message' => 'Cart product deleted successfully.',
        ]);
    }


}