<?php

namespace Drupal\user_crud\Service;

class UserCrudAPPsService {

    private $httpClient;

    private function getCartStorageKey(int $uid): string {
        return 'user_crud.cart.' . (string) $uid;
    }

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

    private function getValidatedProductData($id) {
        try {
            $response = $this->httpClient->get('https://dummyjson.com/products/' . $id);
            $productData = json_decode($response->getBody()->getContents(), TRUE);

            if (!is_array($productData) || empty($productData) || !isset($productData['id'])) {
                throw new \RuntimeException('Product not found in database.');
            }

            return $productData;
        }
        catch (\Throwable $exception) {
            $message = $exception->getMessage();

            if (stripos($message, 'not found') !== FALSE || stripos($message, '404') !== FALSE) {
                throw new \RuntimeException('Product not found in database.', 0, $exception);
            }

            \Drupal::logger('user_crud')->error('getValidatedProductData failed for product @id: @message', [
                '@id' => $id,
                '@message' => $message,
            ]);

            throw new \RuntimeException('Unable to validate product in database.', 0, $exception);
        }
    }

    public function createCartAppData($uid, $id, $title, $image ,$count) {

        $productData = $this->getValidatedProductData($id);

            $stock = $productData['stock'] ?? NULL;

            if (!is_numeric($stock)) {
                throw new \RuntimeException('Product stock information is unavailable in database.');
            }

            if ((int) $count > (int) $stock) {
                throw new \RuntimeException('Requested quantity exceeds available stock.');
            }

        try {
            $cartStorageKey = $this->getCartStorageKey((int) $uid);
            $cart = \Drupal::state()->get($cartStorageKey, []);

            $cartItem = [
                'id' => $id,
                'title' => $title,
                'image' => $image,
                'count' => $count,
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

            \Drupal::state()->set($cartStorageKey, array_values($cart));

            return $cartItem;
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('createCartAppData failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Unable to add cart item.', 0, $exception);
        }
    }

    public function getCartAppData($uid) {
        try {
            $cartStorageKey = $this->getCartStorageKey((int) $uid);
            $cart = \Drupal::state()->get($cartStorageKey, []);

            foreach ($cart as $index => $item) {
                $cart[$index]['count'] = $item['count'] ?? 1;
            }

            \Drupal::state()->set($cartStorageKey, array_values($cart));

            return array_values($cart);
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('getCartAppData failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Unable to load cart data from storage.', 0, $exception);
        }
    }

    public function updateCartAppData($uid, $id, $count) {
        try {
            $productData = $this->getValidatedProductData($id);

            $stock = $productData['stock'] ?? NULL;

            if (!is_numeric($stock)) {
                throw new \RuntimeException('Product stock information is unavailable in database.');
            }

            if ((int) $count > (int) $stock) {
                throw new \RuntimeException('Requested quantity exceeds available stock.');
            }

            $cartStorageKey = $this->getCartStorageKey((int) $uid);
            $cart = \Drupal::state()->get($cartStorageKey, []);

            foreach ($cart as $index => $item) {
                if ((string) ($item['id'] ?? '') === (string) $id) {
                    $cart[$index]['count'] = $count;
                    \Drupal::state()->set($cartStorageKey, array_values($cart));

                    return $cart[$index];
                }
            }

            return NULL;
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('updateCartAppData failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException($exception->getMessage(), 0, $exception);
        }
    }

    public function deleteCartAppData($uid, $id) {
        try {
            $cartStorageKey = $this->getCartStorageKey((int) $uid);
            $cart = \Drupal::state()->get($cartStorageKey, []);

            foreach ($cart as $index => $item) {
                if ((string) ($item['id'] ?? '') === (string) $id) {
                    unset($cart[$index]);
                    \Drupal::state()->set($cartStorageKey, array_values($cart));

                    return TRUE;
                }
            }

            return FALSE;
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('deleteCartAppData failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Unable to delete cart data from storage.', 0, $exception);
        }
    }


    public function registerDeviceToken($name, $id, $device, $token) {
        try {
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
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('registerDeviceToken failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Unable to save device token data.', 0, $exception);
        }
    }

    public function getRegisteredTokens() {
        try {
            $tokens = \Drupal::state()->get('user_crud.notification_tokens', []);

            foreach ($tokens as $index => $item) {
                $tokens[$index]['name'] = trim((string) ($item['name'] ?? ''));
                $tokens[$index]['id'] = (string) ($item['id'] ?? '');
                $tokens[$index]['device'] = trim((string) ($item['device'] ?? ''));
                $tokens[$index]['token'] = trim((string) ($item['token'] ?? ''));
            }

            return array_values($tokens);
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('getRegisteredTokens failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Unable to load registered tokens from storage.', 0, $exception);
        }
    }


}