<?php

namespace Drupal\user_crud\Service;

class UserCrudAPPsService {

    private $httpClient;

    public function __construct($httpClient) {
        $this->httpClient = $httpClient;
    }

    public function getAppData($limit, $skip) {
        $response = $this->httpClient->get('https://dummyjson.com/products', [
            'query' => [
                'limit' => $limit,
                'skip' => $skip,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), TRUE);
    }

    public function createCartAppData($id, $title, $image) {
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

        \Drupal::state()->set('user_crud.cart', array_values($cart));

        return $cartItem;
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


}