<?php

namespace Drupal\user_crud\Service;

use Drupal\Core\Database\Connection;

class CartAdminService {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructor.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Get all cart products.
   */
  public function getAllProducts() {
    return $this->database
      ->select('user_crud_products', 'c')
      ->fields('c')
      ->orderBy('id', 'DESC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Get one cart product.
   */
  public function getProduct($id) {

    return $this->database
      ->select('user_crud_products', 'c')
      ->fields('c')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  /**
   * Add cart product.
   */
  public function addProduct($data) {

    $now = time();

    $amount = (float) $data['amount'];
    $offer = (float) $data['offer'];

    // Calculate discounted price.
    $offer_price = $amount - (($amount * $offer) / 100);

    return $this->database
      ->insert('user_crud_products')
      ->fields([
        'title' => $data['title'],
        'description' => $data['description'],
        'photos' => $data['photos'],
        'quantity' => $data['quantity'],
        'offer' => $offer,
        'amount' => $amount,
        'offer_price' => $offer_price,
        'created_at' => $now,
        'updated_at' => $now,
      ])
      ->execute();
  }

  /**
   * Update cart product.
   */
  public function updateProduct($id, $data) {

    $amount = (float) $data['amount'];
    $offer = (float) $data['offer'];

    // Calculate discounted price.
    $offer_price = $amount - (($amount * $offer) / 100);

    return $this->database
      ->update('user_crud_products')
      ->fields([
        'title' => $data['title'],
        'description' => $data['description'],
        'photos' => $data['photos'],
        'quantity' => $data['quantity'],
        'offer' => $offer,
        'amount' => $amount,
        'offer_price' => $offer_price,
        'updated_at' => time(),
      ])
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Delete cart product.
   */
  public function deleteProduct($id) {

    return $this->database
      ->delete('user_crud_products')
      ->condition('id', $id)
      ->execute();
  }

}