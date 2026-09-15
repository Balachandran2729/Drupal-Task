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
  
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Get all cart products.
   */
  public function getAllProducts() {
    try {
      return $this->database
        ->select('user_crud_products', 'c')
        ->fields('c')
        ->orderBy('id', 'DESC')
        ->execute()
        ->fetchAll();
    }
    catch (\Exception $e) {
      \Drupal::logger('user_crud')->error(
        'Failed to get all products: @message',
        [
          '@message' => $e->getMessage(),
        ]
      );

      return [];
    }
  }

  /**
   * Get one cart product.
   */
  public function getProduct($id) {
    try {
      return $this->database
        ->select('user_crud_products', 'c')
        ->fields('c')
        ->condition('id', $id)
        ->execute()
        ->fetchObject();
    }
    catch (\Exception $e) {
      \Drupal::logger('user_crud')->error(
        'Failed to get product @id: @message',
        [
          '@id' => $id,
          '@message' => $e->getMessage(),
        ]
      );

      return NULL;
    }
  }

  /**
   * Add cart product.
   */
  public function addProduct($data) {

    try {
      $now = time();

      $quantity = (int) $data['quantity'];
      $sales = 0;

      $available = $quantity - $sales;

      $amount = (float) $data['amount'];
      $offer = (float) $data['offer'];

      $offer_price = $amount - (($amount * $offer) / 100);

      return $this->database
        ->insert('user_crud_products')
        ->fields([
          'title' => $data['title'],
          'description' => $data['description'],
          'category' => $data['category'],
          'manufacturer' => $data['manufacturer'],
          'photos' => json_encode($data['photos']),
          'quantity' => $quantity,
          'available' => $available,
          'sales' => $sales,
          'offer' => $offer,
          'amount' => $amount,
          'offer_price' => $offer_price,
          'created_at' => $now,
          'updated_at' => $now,
        ])
        ->execute();
    }
    catch (\Exception $e) {
      \Drupal::logger('user_crud')->error(
        'Failed to add product: @message',
        [
          '@message' => $e->getMessage(),
        ]
      );

      throw new \RuntimeException('Unable to add product.' );
    }
  }

  /**
   * Update cart product.
   */
  public function updateProduct($id, $data) {

    $product = $this->getProduct($id);

      if (!$product) {
        throw new \RuntimeException('Product not found.');
      }

      $quantity = (int) $data['quantity'];
      $sales = (int) $product->sales;

      if ($sales > $quantity) {
        throw new \RuntimeException(
          'Quantity cannot be less than the number of products already sold.'
        );
      }

      $available = $quantity - $sales;

      $amount = (float) $data['amount'];
      $offer = (float) $data['offer'];

      $offer_price = $amount - (($amount * $offer) / 100);
    try {
      
      return $this->database
        ->update('user_crud_products')
        ->fields([
          'title' => $data['title'],
          'description' => $data['description'],
          'category' => $data['category'],
          'manufacturer' => $data['manufacturer'],
          'photos' => json_encode($data['photos']),
          'quantity' => $quantity,
          'available' => $available,
          'offer' => $offer,
          'amount' => $amount,
          'offer_price' => $offer_price,
          'updated_at' => time(),
        ])
        ->condition('id', $id)
        ->execute();
    }
    
    catch (\Exception $e) {
      \Drupal::logger('user_crud')->error(
        'Failed to update product @id: @message',
        [
          '@id' => $id,
          '@message' => $e->getMessage(),
        ]
      );

      throw new \RuntimeException( 'Unable to update product.' );
    }
  }

  /**
   * Delete cart product.
   */
  public function deleteProduct($id) {
    try {
      return $this->database
        ->delete('user_crud_products')
        ->condition('id', $id)
        ->execute();
    }
    catch (\Exception $e) {
      \Drupal::logger('user_crud')->error(
        'Failed to delete product @id: @message',
        [
          '@id' => $id,
          '@message' => $e->getMessage(),
        ]
      );

      throw new \RuntimeException(
        'Unable to delete product.'
      );
    }
  }

  public function getActiveCategories() {
    try {
      return $this->database
        ->select('user_crud_categories', 'c')
        ->fields('c', ['name'])
        ->condition('status', 1)
        ->orderBy('name', 'ASC')
        ->execute()
        ->fetchCol();
    }
    catch (\Exception $e) {
      \Drupal::logger('user_crud')->error(
        'Failed to fetch active categories: @message',
        [
          '@message' => $e->getMessage(),
        ]
      );

      return [];
    }
  }

  /**
   * Add a new category.
   */
  public function addCategory($name) {
    try {
      $timestamp = time();

      return $this->database
        ->insert('user_crud_categories')
        ->fields([
          'name' => $name,
          'status' => 1,
          'created_at' => $timestamp,
          'updated_at' => $timestamp,
        ])
        ->execute();
    }
    catch (\Exception $e) {
      \Drupal::logger('user_crud')->error('Failed to add category: @message',['@message' => $e->getMessage(), ]);

      throw $e;
    }
  }

  /**
   * Check whether a category already exists.
  */
  public function categoryExists($name) {
    try {
      return (bool) $this->database
        ->select('user_crud_categories', 'c')
        ->condition('name', $name)
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      \Drupal::logger('user_crud')->error(
        'Failed to check category: @message',
        [
          '@message' => $e->getMessage(),
        ]
      );

      return FALSE;
    }
  }

}