<?php

namespace Drupal\user_crud\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\user_crud\Service\CartAdminService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CartAdminController extends ControllerBase {

  /**
   * Cart service.
   *
   * @var \Drupal\user_crud\Service\CartAdminService
   */
  protected $cartService;

  /**
   * Constructor.
   */
  public function __construct(CartAdminService $cart_service) {
    $this->cartService = $cart_service;
  }

  /**
   * Create controller from container.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_crud.cart_admin_service')
    );
  }

  /**
   * Display all cart products.
   */
  public function list() {

    $products = $this->cartService->getAllProducts();

    $rows = [];

    foreach ($products as $product) {

      $edit_link = Link::createFromRoute(
        'Edit',
        'user_crud.cart_edit',
        ['id' => $product->id]
      )->toRenderable();

      $delete_link = Link::createFromRoute(
        'Delete',
        'user_crud.cart_delete',
        ['id' => $product->id]
      )->toRenderable();

      $rows[] = [
        $product->id,
        $product->title,
        $product->quantity,
        $product->offer . '%',
        $product->amount,
        $product->offer_price,
        date('Y-m-d H:i:s', $product->created_at),
        date('Y-m-d H:i:s', $product->updated_at),
        ['data' => $edit_link],
        ['data' => $delete_link],
      ];
    }

    $build = [];

    $build['heading'] = [
      '#markup' => '<h2>Cart Products</h2>',
    ];

    $build['add_link'] = Link::createFromRoute(
      'Add Cart Product',
      'user_crud.cart_add'
    )->toRenderable();

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        'ID',
        'Title',
        'Quantity',
        'Offer',
        'Amount',
        'Offer Price',
        'Created',
        'Updated',
        'Edit',
        'Delete',
      ],
      '#rows' => $rows,
      '#empty' => 'No cart products found.',
    ];

    return $build;
  }

}