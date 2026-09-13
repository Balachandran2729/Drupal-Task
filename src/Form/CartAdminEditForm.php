<?php

namespace Drupal\user_crud\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user_crud\Service\CartAdminService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartAdminEditForm extends FormBase {

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
    return 'cart_admin_edit_form';
  }

  /**
   * Build edit form.
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

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->product->title,
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->product->description,
      '#required' => TRUE,
    ];

    $form['photos'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Photos'),
      '#default_value' => $this->product->photos,
      '#required' => TRUE,
    ];

    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#default_value' => $this->product->quantity,
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#default_value' => $this->product->amount,
      '#step' => '0.01',
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['offer'] = [
      '#type' => 'number',
      '#title' => $this->t('Offer (%)'),
      '#default_value' => $this->product->offer,
      '#step' => '0.01',
      '#min' => 0,
      '#max' => 100,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Product'),
    ];

    return $form;
  }

  /**
   * Validate form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $title = trim($form_state->getValue('title'));
    $description = trim($form_state->getValue('description'));
    $photos = trim($form_state->getValue('photos'));

    $quantity = $form_state->getValue('quantity');
    $amount = $form_state->getValue('amount');
    $offer = $form_state->getValue('offer');

    // Title validation.
    if ($title === '') {
      $form_state->setErrorByName('title', $this->t('Title cannot be empty.'));
    }
    elseif (mb_strlen($title) < 3) {
      $form_state->setErrorByName('title',$this->t('Title must be at least 3 characters.'));
    }

    // Description validation.
    if ($description === '') {
      $form_state->setErrorByName('description',$this->t('Description cannot be empty.'));
    }

    // Photo URL validation.
    if (!filter_var($photos, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('photos', $this->t('Please enter a valid photo URL.'));
    }

    // Quantity validation.
    if (!is_numeric($quantity) || (int) $quantity != $quantity) {
      $form_state->setErrorByName('quantity', $this->t('Quantity must be a whole number.'));
    }
    elseif ($quantity < 0) {
      $form_state->setErrorByName('quantity', $this->t('Quantity cannot be negative.')
      );
    }

    // Amount validation.
    if (!is_numeric($amount)) {
      $form_state->setErrorByName( 'amount', $this->t('Amount must be a valid number.'));
    }
    elseif ($amount < 0) {
      $form_state->setErrorByName( 'amount', $this->t('Amount cannot be negative.') );
    }

    // Offer validation.
    if (!is_numeric($offer)) {
      $form_state->setErrorByName( 'offer', $this->t('Offer must be a valid number.'));
    }
    elseif ($offer < 0 || $offer > 100) {
      $form_state->setErrorByName('offer', $this->t('Offer must be between 0 and 100.') );
    }
  }

  /**
   * Submit edit form.
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state
  ) {

    $data = [
      'title' => trim($form_state->getValue('title')),
      'description' => trim($form_state->getValue('description')),
      'photos' => trim($form_state->getValue('photos')),
      'quantity' => (int) $form_state->getValue('quantity'),
      'amount' => (float) $form_state->getValue('amount'),
      'offer' => (float) $form_state->getValue('offer'),
    ];

    $this->cartService->updateProduct(
      $this->product->id,
      $data
    );

    $this->messenger()->addStatus(
      $this->t('Cart product has been updated successfully.')
    );

    $form_state->setRedirect('user_crud.cart_list');
  }

}