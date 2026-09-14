<?php

namespace Drupal\user_crud\Service;
use Drupal\Core\Database\Connection;

class UserCrudAPPsService {

    protected $database;

    public function __construct(Connection $database) {
        $this->database = $database;
    }


    // Get the Data from  Database
   public function getAppData() {
    try {
        $products = $this->database
            ->select('user_crud_products', 'c')
            ->fields('c')
            ->orderBy('id', 'DESC')
            ->execute()
            ->fetchAll();

        $totalProducts = count($products);

        return [
            'products' => $products,
            'total_products' => $totalProducts,
        ];
    }
    catch (\Throwable $exception) {
        \Drupal::logger('user_crud')->error('getAppData failed: @message', [
            '@message' => $exception->getMessage(),
        ]);

        return [
            'products' => [],
            'total_products' => 0,
        ];
    }
}

    // Validate a Prodect for create and update cart function like prodect exites or not , stock like that.
    private function getValidatedProductData($id) {
        try {
            $productData = $this->database
                ->select('user_crud_products', 'c')
                ->fields('c')
                ->condition('id', $id)
                ->execute()
                ->fetchAssoc();

            if (!$productData) {
                throw new \RuntimeException('Product not found in database.');
            }

            return $productData;
        }
        catch (\Throwable $exception) {
            $message = $exception->getMessage();

            \Drupal::logger('user_crud')->error( 'getValidatedProductData failed for product @id: @message',
                [
                    '@id' => $id,
                    '@message' => $message,
                ]
            );

            throw new \RuntimeException( 'Unable to validate product in database.',0, $exception);
        }
    }


    // create a cart 
    public function createCartAppData($uid, $id, $title, $image ,$count) {

        $productData = $this->getValidatedProductData($id);

        $available = (int) ($productData['available'] ?? 0);
        $sales = (int) ($productData['sales'] ?? 0);
        $itemCount = (int) $count;

        if (!is_numeric($available)) {
            throw new \RuntimeException('Product stock information is unavailable in database.');
        }

        if ($itemCount > $available) {
            throw new \RuntimeException('Requested quantity exceeds available stock.');
        }

        try {
                $cartItem = [
                    'id' => (int) $id,
                    'title' => $title,
                    'image' => $image,
                    'count' => (int) $count,
                ];

                $db = \Drupal::database();
                $existingItem = $db->select('user_crud_cart', 'c')
                    ->fields('c', ['id'])
                    ->condition('c.uid', (int) $uid)
                    ->condition('c.product_id', (int) $id)
                    ->range(0, 1)
                    ->execute()
                    ->fetchAssoc();

                $now = time();

            if ($existingItem) {
                throw new \RuntimeException('Product is already in the cart.');
            }
            else {
                $db->insert('user_crud_cart')
                    ->fields([
                        'uid' => (int) $uid,
                        'product_id' => (int) $id,
                        'title' => $title,
                        'image' => $image,
                        'count' => (int) $count,
                        'created' => $now,
                        'changed' => $now,
                    ])
                    ->execute();

                $db->update('user_crud_products')
                    ->fields([
                        'available' => $available - $itemCount,
                        'sales' => $sales + $itemCount,
                        'updated_at' => $now,
                    ])
                    ->condition('id', (int) $id)
                    ->execute();
            }

            return $cartItem;
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('createCartAppData failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Unable to add cart item.', 0, $exception);
        }
    }


    // Get a cart for database
    public function getCartAppData($uid) {
        try {
            $query = \Drupal::database()->select('user_crud_cart', 'c');
            $query->fields('c');
            $query->condition('c.uid', (int) $uid);
            $query->orderBy('c.id');

            $cart = [];

            foreach ($query->execute()->fetchAll(\PDO::FETCH_ASSOC) as $item) {
                $cart[] = [
                    'id' => (int) ($item['product_id'] ?? 0),
                    'title' => $item['title'] ?? '',
                    'image' => $item['image'] ?? '',
                    'count' => (int) ($item['count'] ?? 1),
                ];
            }

            return $cart;
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('getCartAppData failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Unable to load cart data from storage.', 0, $exception);
        }
    }


    // Update a cart
    public function updateCartAppData($uid, $id, $count) {


        $productData = $this->getValidatedProductData($id);

        $available = (int) ($productData['available'] ?? 0);
        $sales = (int) ($productData['sales'] ?? 0);
        $newCount = (int) $count;

        if (!is_numeric($available)) {
            throw new \RuntimeException('Product stock information is unavailable in database.');
        }

        try {

            $db = \Drupal::database();

            $existingItem = $db->select('user_crud_cart', 'c')
                ->fields('c')
                ->condition('c.uid', (int) $uid)
                ->condition('c.product_id', (int) $id)
                ->range(0, 1)
                ->execute()
                ->fetchAssoc();

            if (!$existingItem) {
                return NULL;
            }

            $existingCount = (int) ($existingItem['count'] ?? 0);
            $countDelta = $newCount - $existingCount;
            $updatedAt = time();

            if ($countDelta > 0 && $countDelta > $available) {
                throw new \RuntimeException('Requested quantity exceeds available stock.');
            }

            $updatedAvailable = $available;
            $updatedSales = $sales;

            if ($countDelta > 0) {
                $updatedAvailable -= $countDelta;
                $updatedSales += $countDelta;
            }
            else {
                $updatedAvailable += abs($countDelta);
                $updatedSales -= abs($countDelta);
            }

            $db->update('user_crud_cart')
                ->fields([
                    'count' => $newCount,
                    'changed' => $updatedAt,
                ])
                ->condition('id', $existingItem['id'])
                ->execute();

            $db->update('user_crud_products')
                ->fields([
                    'available' => $updatedAvailable,
                    'sales' => $updatedSales,
                    'updated_at' => $updatedAt,
                ])
                ->condition('id', (int) $id)
                ->execute();

            return [
                'id' => (int) ($existingItem['product_id'] ?? $id),
                'title' => $existingItem['title'] ?? '',
                'image' => $existingItem['image'] ?? '',
                'count' => (int) $count,
            ];
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('updateCartAppData failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException($exception->getMessage(), 0, $exception);
        }
    }


    // Delete a cart
    public function deleteCartAppData($uid, $id) {
        try {
            $deleted = \Drupal::database()->delete('user_crud_cart')
                ->condition('uid', (int) $uid)
                ->condition('product_id', (int) $id)
                ->execute();

            return $deleted > 0;
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('deleteCartAppData failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Unable to delete cart data from storage.', 0, $exception);
        }
    }


    //store a Notification Token.
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


    // Get the notofication token with user details , only Testing Perpose
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