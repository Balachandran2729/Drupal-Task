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
        return \Drupal::state()->get('user_crud.cart', []);
    }


}