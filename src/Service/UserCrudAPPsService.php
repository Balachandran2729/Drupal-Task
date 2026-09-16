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

    // Validate a product and return its current database data.
    public function getValidatedProductData($id) {
        try {
            $productData = $this->database
                ->select('user_crud_products', 'c')
                ->fields('c')
                ->condition('id', $id)
                ->execute()
                ->fetchAssoc();

            if (!$productData) {
                throw new \RuntimeException('Product not found in database.', 404);
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

            throw new \RuntimeException(
                'Unable to validate product in database.',
                $exception->getCode() === 404 ? 404 : 500,
                $exception
            );
        }
    }


    // create a cart 
    public function createCartAppData($uid, $id, $count) {

        $productData = $this->getValidatedProductData($id);

        $available = (int) ($productData['available'] ?? 0);
        $itemCount = (int) $count;

        if ($itemCount > $available) {
            throw new \RuntimeException('Requested quantity exceeds available stock.');
        }

        $offer = (float) ($productData['offer'] ?? 0);
        $amount = (float) ($productData['amount'] ?? 0);
        $offerPrice = (float) ($productData['offer_price'] ?? 0);
        $title = (string) ($productData['title'] ?? '');
        $image = json_decode((string) ($productData['photos'] ?? ''), TRUE);

        if (!is_array($image)) {
            $image = [];
        }


        try {
            $db = \Drupal::database();

            $existingItem = $db->select('user_crud_cart', 'c')
                ->fields('c', ['id'])
                ->condition('c.uid', (int) $uid)
                ->condition('c.product_id', (int) $id)
                ->range(0, 1)
                ->execute()
                ->fetchAssoc();

            if ($existingItem) {
                throw new \RuntimeException('Product is already in the cart.');
            }

            $now = time();

            $db->insert('user_crud_cart')
                ->fields([
                    'uid' => (int) $uid,
                    'product_id' => (int) $id,
                    'title' => '',
                    'image' => '',
                    'count' => $itemCount,
                    'offer' => $offer,
                    'offer_price' => $offerPrice,
                    'amount' => $amount,
                    'created' => $now,
                    'changed' => $now,
                ])
                ->execute();

            return [
                'id' => (int) $id,
                'title' => $title,
                'image' => $image,
                'count' => $itemCount,
                'offer' => $offer,
                'offer_price' => $offerPrice,
                'amount' => $amount,
            ];
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
            $db = \Drupal::database();

            $query = $db->select('user_crud_cart', 'c');
            $query->fields('c');
            $query->condition('c.uid', (int) $uid);
            $query->orderBy('c.id');

            $rows = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

            $products = [];
            $total = 0;
            $totalAmount = 0.0;
            $now = time();

            foreach ($rows as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $count = (int) ($item['count'] ?? 1);
                $title = '';
                $image = [];

                $offer = (float) ($item['offer'] ?? 0);
                $amount = (float) ($item['amount'] ?? 0);
                $offerPrice = (float) ($item['offer_price'] ?? 0);

                try {
                    $productData = $this->getValidatedProductData($productId);
                    $title = (string) ($productData['title'] ?? '');
                    $image = json_decode((string) ($productData['photos'] ?? ''), TRUE);
                    if (!is_array($image)) {
                        $image = [];
                    }
                    $freshOffer = (float) ($productData['offer'] ?? 0);
                    $freshAmount = (float) ($productData['amount'] ?? 0);
                    $freshOfferPrice = (float) ($productData['offer_price'] ?? 0);

                    if ($freshOffer !== $offer || $freshAmount !== $amount || $freshOfferPrice !== $offerPrice) {
                        $db->update('user_crud_cart')
                            ->fields([
                                'offer' => $freshOffer,
                                'offer_price' => $freshOfferPrice,
                                'amount' => $freshAmount,
                                'changed' => $now,
                            ])
                            ->condition('id', $item['id'])
                            ->execute();
                    }

                    $offer = $freshOffer;
                    $amount = $freshAmount;
                    $offerPrice = $freshOfferPrice;
                }
                catch (\Throwable $exception) {
                    \Drupal::logger('user_crud')->warning(
                        'getCartAppData: could not refresh product @id: @message',
                        ['@id' => $productId, '@message' => $exception->getMessage()]
                    );
                }

                $lineTotal = round($offerPrice * $count, 2);

                $products[] = [
                    'id' => $productId,
                    'title' => $title,
                    'image' => $image,
                    'count' => $count,
                    'offer' => $offer,
                    'offer_price' => $offerPrice,
                    'Real_price' => $amount,
                    'Total amount' => round($count * $offerPrice , 2)
                ];

                $cartcount += $count;
                $total = count($products);
                $totalAmount += $lineTotal;
            }

            return [
                'products' => $products,
                'total prodects' => $total,
                'total prodects Items' => $cartcount,
                'total_amount' => round($totalAmount, 2),
            ];
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
        $newCount = (int) $count;

        if ($newCount > $available) {
            throw new \RuntimeException('Requested quantity exceeds available stock.');
        }

        $offer = (float) ($productData['offer'] ?? 0);
        $amount = (float) ($productData['amount'] ?? 0);
        $offerPrice = (float) ($productData['offer_price'] ?? 0);

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

            $updatedAt = time();

            $db->update('user_crud_cart')
                ->fields([
                    'count' => $newCount,
                    'offer' => $offer,
                    'offer_price' => $offerPrice,
                    'amount' => $amount,
                    'changed' => $updatedAt,
                ])
                ->condition('id', $existingItem['id'])
                ->execute();

            return [
                'id' => (int) ($existingItem['product_id'] ?? $id),
                'title' => $existingItem['title'] ?? '',
                'count' => $newCount,
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


    // Purchase validation and function
    public function validateCartAvailability(array $cartItems) {
        $errors = [];

        foreach ($cartItems as $cartItem) {
            $id = (int) ($cartItem['id'] ?? 0);
            $count = (int) ($cartItem['count'] ?? 0);

            if ($count <= 0) {
                $errors[] = [
                    'id' => $id,
                    'requested' => $count,
                    'error' => 'Purchase quantity must be greater than zero.',
                ];
                continue;
            }

            try {
                $productData = $this->getValidatedProductData($id);
            }
            catch (\Throwable $exception) {
                $errors[] = [
                    'id' => $id,
                    'error' => 'Product not found.',
                ];
                continue;
            }

            $available = (int) ($productData['available'] ?? 0);

            if ($count > $available) {
                $errors[] = [
                    'id' => $id,
                    'title' => $productData['title'] ?? '',
                    'requested' => $count,
                    'available' => $available,
                    'error' => 'Requested quantity exceeds available stock.',
                ];
            }
        }

        return $errors;
    }


    // Purchase: validates, then atomically decrements stock and records the order.
    public function createPurchase($uid, array $cartItems) {

        $errors = $this->validateCartAvailability($cartItems);

        if (!empty($errors)) {
            return ['success' => FALSE, 'errors' => $errors];
        }

        $db = \Drupal::database();
        $transaction = $db->startTransaction();
        $purchased = [];

        try {
            foreach ($cartItems as $cartItem) {
                $id = (int) ($cartItem['id'] ?? 0);
                $count = (int) ($cartItem['count'] ?? 0);

                $productData = $this->getValidatedProductData($id);
                $offer = (float) ($productData['offer'] ?? 0);
                $amount = (float) ($productData['amount'] ?? 0);
                $offerPrice = (float) ($productData['offer_price'] ?? 0);
                $now = time();

            
                $updated = $db->update('user_crud_products')
                    ->expression('available', 'available - :count', [':count' => $count])
                    ->expression('sales', 'sales + :count', [':count' => $count])
                    ->fields(['updated_at' => $now])
                    ->condition('id', $id)
                    ->condition('available', $count, '>=')
                    ->execute();

                if (!$updated) {
                    throw new \RuntimeException(
                        'Requested quantity for "' . ($productData['title'] ?? $id) . '" exceeds available stock.'
                    );
                }

                $totalAmount = round($offerPrice * $count, 2);

                $db->insert('user_crud_purchase')
                    ->fields([
                        'uid' => (int) $uid,
                        'product_id' => $id,
                        'title' => $productData['title'] ?? ($productData['title'] ?? ''),
                        'image' => $cartItem['image'] ?? ($productData['photos'] ?? ''),
                        'count' => $count,
                        'offer' => $offer,
                        'offer_price' => $offerPrice,
                        'amount' => $amount,
                        'total_amount' => $totalAmount,
                        'created' => $now,
                        'changed' => $now,
                    ])
                    ->execute();

                // Purchased items come out of the cart.
                // $db->delete('user_crud_cart')
                //     ->condition('uid', (int) $uid)
                //     ->condition('product_id', $id)
                //     ->execute();

                $purchased[] = [
                    'id' => $id,
                    'title' => $productData['title'] ?? '',
                    'count' => $count,
                    'offer' => $offer,
                    'offer_price' => $offerPrice,
                    'amount' => $amount,
                    'total_amount' => $totalAmount,
                ];
            }

            return ['success' => TRUE, 'purchased' => $purchased];
        }
        catch (\Throwable $exception) {
            $transaction->rollBack();

            \Drupal::logger('user_crud')->error('createPurchase failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            return [
                'success' => FALSE,
                'errors' => [['error' => $exception->getMessage()]],
            ];
        }
    }

        // Get purchase history for a user.
    public function getPurchaseHistory($uid) {
        try {
            $query = \Drupal::database()->select('user_crud_purchase', 'p');
            $query->fields('p');
            $query->condition('p.uid', (int) $uid);
            $query->orderBy('p.id', 'DESC');

            $rows = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

            $purchases = [];
            $totalAmount = 0.0;

            foreach ($rows as $item) {
                $lineAmount = (float) ($item['total_amount'] ?? 0);

                $purchases[] = [
                    'id' => (int) ($item['id'] ?? 0),
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'title' => $item['title'] ?? '',
                    'image' => $item['image'] ?? '',
                    'count' => (int) ($item['count'] ?? 0),
                    'offer' => (float) ($item['offer'] ?? 0),
                    'offer_price' => (float) ($item['offer_price'] ?? 0),
                    'amount' => (float) ($item['amount'] ?? 0),
                    'total_amount' => $lineAmount,
                    'purchased_at' => (int) ($item['created'] ?? 0),
                ];

                $totalAmount += $lineAmount;
            }

            return [
                'purchases' => $purchases,
                'total' => count($purchases),
                'total_amount' => round($totalAmount, 2),
            ];
        }
        catch (\Throwable $exception) {
            \Drupal::logger('user_crud')->error('getPurchaseHistory failed: @message', [
                '@message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Unable to load purchase history from storage.', 0, $exception);
        }
    }


}