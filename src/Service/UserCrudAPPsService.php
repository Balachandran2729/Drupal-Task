<?php

namespace Drupal\user_crud\Service;

class UserCrudAPPsService {

    private $httpClient;

    public function __construct($httpClient) {
        $this->httpClient = $httpClient;
    }

    public function getAppData($limit, $skip) {
        try {
            $response = $this->httpClient->get('https://dummyjson.com/products', [
                'query' => [
                    'limit' => $limit,
                    'skip' => $skip,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), TRUE);
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('getAppData failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    public function createCartAppData($id, $title, $image) {

        try {
            $response = $this->httpClient->get('https://dummyjson.com/products/' . $id );

            $productData = json_decode($response->getBody()->getContents(), TRUE);

            if (!is_array($productData) || empty($productData) || !isset($productData['id'])) {
                throw new \RuntimeException('Product not found in database.');
            }

            $cart = \Drupal::state()->get('user_crud.cart', []);

            $cartItem = [
                'id' => $id,
                'title' => $title,
                'image' => $image,
                'count' => 1,
            ];

            $updated = FALSE;

            foreach ($cart as $index => $item) {
                if ((string) ($item['id'] ?? '') === (string) $id) {
                    $cart[$index] = $cartItem;
                    $updated = TRUE;
                    break;
                }
            }

            if (!$updated) {
                $cart[] = $cartItem;
            }

            \Drupal::state()->set('user_crud.cart', array_values($cart) );

            return $cartItem;
        }
        catch (\Throwable $exception) {

            \Drupal::logger('user_crud')->error(
                'createCartAppData failed: @message',
                [
                    '@message' => $exception->getMessage(),
                ]
            );

            $message = $exception->getMessage();

            if (stripos($message, 'not found') !== FALSE || stripos($message, '404') !== FALSE) {
                throw new \RuntimeException('Product not found in database.');
            }

            throw new \RuntimeException('Unable to validate product in database.');
        }
    }

    public function getCartAppData() {
        $cart = \Drupal::state()->get('user_crud.cart', []);

        foreach ($cart as $index => $item) {
            $cart[$index]['count'] = $item['count'] ?? 1;
        }

        \Drupal::state()->set('user_crud.cart', array_values($cart));

        return array_values($cart);
    }

    public function updateCartAppData($id, $count) {
        $cart = \Drupal::state()->get('user_crud.cart', []);

        foreach ($cart as $index => $item) {
            if ((string) ($item['id'] ?? '') === (string) $id) {
                $cart[$index]['count'] = $count;
                \Drupal::state()->set('user_crud.cart', array_values($cart));

                return $cart[$index];
            }
        }

        return NULL;
    }

    public function deleteCartAppData($id) {
        $cart = \Drupal::state()->get('user_crud.cart', []);

        foreach ($cart as $index => $item) {
            if ((string) ($item['id'] ?? '') === (string) $id) {
                unset($cart[$index]);
                \Drupal::state()->set('user_crud.cart', array_values($cart));

                return TRUE;
            }
        }

        return FALSE;
    }


    public function registerDeviceToken($name, $id, $device, $token) {
        $storedTokens = \Drupal::state()->get('user_crud.notification_tokens', []);

        $registeredToken = [
            'name' => trim((string) $name),
            'id' => (string) $id,
            'device' => trim((string) $device),
            'token' => trim((string) $token),
            'registered_at' => time(),
        ];

        $updated = FALSE;
        foreach ($storedTokens as $index => $item) {
            if ((string) ($item['token'] ?? '') === (string) $registeredToken['token']) {
                $storedTokens[$index] = $registeredToken;
                $updated = TRUE;
                break;
            }
        }

        if (!$updated) {
            $storedTokens[] = $registeredToken;
        }

        \Drupal::state()->set('user_crud.notification_tokens', array_values($storedTokens));

        return $registeredToken;
    }

    public function getRegisteredTokens() {
        $tokens = \Drupal::state()->get('user_crud.notification_tokens', []);

        foreach ($tokens as $index => $item) {
            $tokens[$index]['name'] = trim((string) ($item['name'] ?? ''));
            $tokens[$index]['id'] = (string) ($item['id'] ?? '');
            $tokens[$index]['device'] = trim((string) ($item['device'] ?? ''));
            $tokens[$index]['token'] = trim((string) ($item['token'] ?? ''));
        }

        return array_values($tokens);
    }


}