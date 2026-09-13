<?php

namespace Drupal\user_crud\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user_crud\Service\CartAdminService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartAdminDeleteForm extends ConfirmFormBase {

  protected $cartService;

  protected $product;

  /**
   * Constructor.
   */
  public function __construct(CartAdminService $cart_service) {
    $this->cartService = $cart_service;
  }

  /**
   * Create form from container.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_crud.cart_admin_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cart_admin_delete_form';
  }

  /**
   * Build confirmation form.
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    $id = NULL
  ) {

    $this->product = $this->cartService->getProduct($id);

    if (!$this->product) {
      throw new NotFoundHttpException();
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Question shown to admin.
   */
  public function getQuestion() {

    return $this->t(
      'Are you sure you want to delete "@title"?',
      [
        '@title' => $this->product->title,
      ]
    );
  }

  /**
   * Cancel URL.
   */
  public function getCancelUrl() {
    return new Url('user_crud.cart_list');
  }

  /**
   * Confirm button.
   */
  public function getConfirmText() {
    return $this->t('Delete Product');
  }

  /**
   * Cancel button.
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * Delete product.
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state
  ) {

    $this->cartService->deleteProduct($this->product->id);

    $this->messenger()->addStatus(
      $this->t('Cart product has been deleted successfully.')
    );

    $form_state->setRedirect('user_crud.cart_list');
  }

}