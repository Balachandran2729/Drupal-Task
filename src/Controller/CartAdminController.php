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
      
      $title_link = Link::createFromRoute(
        $product->title,
        'user_crud.cart_detail',
        ['id' => $product->id]
      )->toRenderable();

      $rows[] = [
        $product->id,
        ['data' => $title_link],
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

  public function detail($id) {

    $product = $this->cartService->getProduct($id);

    if (!$product) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $edit_link = Link::createFromRoute(
      'Update',
      'user_crud.cart_edit',
      ['id' => $product->id]
    )->toRenderable();

    $delete_link = Link::createFromRoute(
      'Delete',
      'user_crud.cart_delete',
      ['id' => $product->id]
    )->toRenderable();

    $photos = json_decode($product->photos, TRUE);

    $photo_markup = '';

    if (!empty($photos)) {
      foreach ($photos as $photo) {
        $photo_markup .= '<div style="margin-bottom: 10px;">';
        $photo_markup .= '<img src="' . htmlspecialchars($photo) . '" width="200">';
        $photo_markup .= '</div>';
      }
    }

    return [
      'actions' => [
        '#type' => 'container',
        '#attributes' => [
          'style' => 'display: flex; gap: 15px; margin-bottom: 20px;',
        ],
        'update' => $edit_link,
        'delete' => $delete_link,
      ],

      'details' => [
        '#type' => 'table',
        '#header' => [
          'Field',
          'Value',
        ],
        '#rows' => [
          ['ID', $product->id],
          ['Title', $product->title],
          ['Description', $product->description],
          ['Photos', ['data' => [ '#markup' => $photo_markup, ],]],
          ['Quantity', $product->quantity],
          ['Offer', $product->offer . '%'],
          ['Amount', $product->amount],
          ['Offer Price', $product->offer_price],
          ['Created', date('Y-m-d H:i:s', $product->created_at)],
          ['Updated', date('Y-m-d H:i:s', $product->updated_at)],
        ],
      ],
    ];
  }

}