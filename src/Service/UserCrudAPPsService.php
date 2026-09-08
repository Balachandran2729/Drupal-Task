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

}