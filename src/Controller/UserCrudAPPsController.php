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

    private function getAuthenticatedUserId(Request $request): ?int {
        $authorization = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return NULL;
        }

        $jwtPayload = $this->jwtAuthService->decodeToken($matches[1]);

        if (!$jwtPayload || !isset($jwtPayload['uid']) || !is_numeric($jwtPayload['uid'])) {
            return NULL;
        }

        return (int) $jwtPayload['uid'];
    }

    public function getAppData (Request $request) {

        \Drupal::logger('user_crud')->info('getAppData called.');

        $validationResponse = $this->tokenService->validateAccessToken($request, $this->jwtAuthService, 'getAppData');
        if ($validationResponse instanceof JsonResponse) {
            return $validationResponse;
        }

        $requestData = json_decode($request->getContent(), TRUE) ?: [];
        $limit = $requestData['limit'] ?? 10;
        $skip = $requestData['skip'] ?? 0;

        \Drupal::logger('user_crud')->info('getAppData: Request data received with limit ' . $limit . ' and skip ' . $skip . '.');

        $appData = $this->userCrudAPPsService->getAppData($limit, $skip);

        \Drupal::logger('user_crud')->info('getAppData completed successfully.');

        return new JsonResponse($appData);
    }

    //Cart Section

    public function createCartAppData (Request $request) {

        \Drupal::logger('user_crud')->info('createCartAppData called.');

        $validationResponse = $this->tokenService->validateAccessToken($request, $this->jwtAuthService, 'createCartAppData');
        if ($validationResponse instanceof JsonResponse) {
            return $validationResponse;
        }

        $uid = $this->getAuthenticatedUserId($request);
        if ($uid === NULL) {
            \Drupal::logger('user_crud')->error('createCartAppData failed: User ID missing from valid access token.');
            return new JsonResponse([
                'error' => 'Please log out and log in again.',
            ], 401);
        }

        $requestData = json_decode($request->getContent(), TRUE) ?: [];
        $id = $requestData['id'] ?? '';
        $title= $requestData['title'] ?? '';
        $image = $requestData['image'] ?? '';

        $requestDataJson = json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        \Drupal::logger('user_crud')->info('createCartAppData: Request data received: ' . ($requestDataJson ?: '[]'));

        if (empty($id) || !is_int($id) ) {
            \Drupal::logger('user_crud')->error('createCartAppData failed: Missing required fields.');
            return new JsonResponse([
                'error' => 'Oops! something went wrong, please try after some times.',
            ], 400);
        }

        try {
            \Drupal::logger('user_crud')->info('createCartAppData: Calling service for user @uid.',['@uid' => $uid]);

        $this->userCrudAPPsService->createCartAppData($uid,$id,$title,$image);

        \Drupal::logger('user_crud')->info('createCartAppData completed successfully.');

        return new JsonResponse(['message' => 'Product added to cart successfully.',], 201);

    } catch (\Throwable $e) {

        \Drupal::logger('user_crud')->error('createCartAppData failed: @message',['@message' => $e->getMessage(),]);

        return new JsonResponse(['error' => 'Something went wrong while adding the product to cart.',], 500);
    }
}

    public function getCartAppData (Request $request) {
        \Drupal::logger('user_crud')->info('getCartAppData called.');

        $validationResponse = $this->tokenService->validateAccessToken($request, $this->jwtAuthService, 'getCartAppData');
        if ($validationResponse instanceof JsonResponse) {
            return $validationResponse;
        }

        $uid = $this->getAuthenticatedUserId($request);
        if ($uid === NULL) {
            \Drupal::logger('user_crud')->error('getCartAppData failed: User ID missing from valid access token.');
            return new JsonResponse([
                'error' => 'Please log out and log in again.',
            ], 401);
        }

        try {

            $cartData = $this->userCrudAPPsService->getCartAppData($uid);

            \Drupal::logger('user_crud')->info('getCartAppData completed successfully.' );

            return new JsonResponse(['cart' => $cartData,], 200);

        } catch (\Throwable $e) {

            \Drupal::logger('user_crud')->error(
                'getCartAppData failed for user @uid: @message',['@uid' => $uid,'@message' => $e->getMessage(), ]  );

            return new JsonResponse(['error' => 'Something went wrong while Geting cart data.',], 500);
        }
    }

    public function updateCartAppData($id, Request $request) {
        \Drupal::logger('user_crud')->info('updateCartAppData called for id ' . $id . '.');

        $validationResponse = $this->tokenService->validateAccessToken($request, $this->jwtAuthService, 'updateCartAppData');
        if ($validationResponse instanceof JsonResponse) {
            return $validationResponse;
        }

        $uid = $this->getAuthenticatedUserId($request);
        if ($uid === NULL) {
            \Drupal::logger('user_crud')->error('updateCartAppData failed: User ID missing from valid access token.');
            return new JsonResponse([
                'error' => 'Please log out and log in again.',
            ], 401);
        }

        $requestData = json_decode($request->getContent(), TRUE) ?: [];
        $count = $requestData['count'] ?? NULL;

        $requestDataJson = json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        \Drupal::logger('user_crud')->info('updateCartAppData: Request data received: ');
        
        if (!is_int($count) || $count < 1 || empty($id) || !ctype_digit((string) $id) ) {
            \Drupal::logger('user_crud')->error('updateCartAppData failed: Count value or ID is missing or invalid.');
            return new JsonResponse([
                'error' => 'Please provide a valid quantity or Please Try After Some time.',
            ], 400);
        }

        \Drupal::logger('user_crud')->info('updateCartAppData: Updating cart item with count @count for user @uid.', [
            '@count' => $count,
            '@uid' => $uid,
        ]);

        try {
            $cartItem = $this->userCrudAPPsService->updateCartAppData( $uid,$id,$count );

            \Drupal::logger('user_crud')->info( 'updateCartAppData completed successfully for id @id.',['@id' => $id]);

            return new JsonResponse(['message' => 'Cart product updated successfully.','item' => $cartItem,], 200);

        } catch (\Throwable $e) {

            \Drupal::logger('user_crud')->error(
                'updateCartAppData failed for id @id, user @uid: @message',
                [
                    '@id' => $id,
                    '@uid' => $uid,
                    '@message' => $e->getMessage(),
                ]
            );

            return new JsonResponse(['error' => 'Something went wrong while updating the cart product.',], 500);
        }
    }

    public function deleteCartAppData($id, Request $request) {
        \Drupal::logger('user_crud')->info('deleteCartAppData called for id ' . $id . '.');

        $validationResponse = $this->tokenService->validateAccessToken($request, $this->jwtAuthService, 'deleteCartAppData');
        if ($validationResponse instanceof JsonResponse) {
            return $validationResponse;
        }

        $uid = $this->getAuthenticatedUserId($request);
        
        if ($uid === NULL) {
            \Drupal::logger('user_crud')->error('deleteCartAppData failed: User ID missing from valid access token.');
            return new JsonResponse([
                'error' => 'Please log out and log in again.',
            ], 401);
        }

        if (empty($id) || !ctype_digit((string) $id)) {
            \Drupal::logger('user_crud')->error('DeleteCartAppData failed: Missing required fields.');
            return new JsonResponse([
                'error' => 'Oops! something went wrong, please try after some times.',
            ], 400);
        }

        try {

            $deleted = $this->userCrudAPPsService->deleteCartAppData($uid, $id);

            if (!$deleted) {
                \Drupal::logger('user_crud')->error('deleteCartAppData failed: Cart product not found for id @id.',['@id' => $id]);

                return new JsonResponse(['error' => 'Cart product not found.',], 404);
            }

            \Drupal::logger('user_crud')->info( 'deleteCartAppData completed successfully for id @id.',['@id' => $id]);

            return new JsonResponse(['message' => 'Cart product deleted successfully.',], 200);
    
        } catch (\Throwable $e) {

            \Drupal::logger('user_crud')->error(
                'deleteCartAppData failed for id @id, user @uid: @message',
                [
                    '@id' => $id,
                    '@uid' => $uid,
                    '@message' => $e->getMessage(),
                ]
            );

            return new JsonResponse(['error' => 'Something went wrong while deleting the cart product.',], 500);
        }
    }


    //Notification Token Section


    public function registerToken(Request $request) {
        \Drupal::logger('user_crud')->info('registerToken called.');

        $validationResponse = $this->tokenService->validateAccessToken($request, $this->jwtAuthService, 'registerToken');
        if ($validationResponse instanceof JsonResponse) {
            return $validationResponse;
        }

        $requestData = json_decode($request->getContent(), TRUE);

        if (!is_array($requestData)) {
            \Drupal::logger('user_crud')->warning('registerToken failed: Invalid JSON body.');
            return new JsonResponse([
                'error' => 'Please send a valid JSON request body.',
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
                'error' => 'Please provide your name, id, device, and token.',
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


}