<?php

namespace Drupal\user_crud\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\user_crud\Service\CartAdminService;
use Drupal\user_crud\Service\CloudinaryService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CartAdminForm extends FormBase {
  protected $cartService;

  protected $cloudinaryService;


  public function __construct( CartAdminService $cart_service, CloudinaryService $cloudinary_service) {
    $this->cartService = $cart_service;
    $this->cloudinaryService = $cloudinary_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_crud.cart_admin_service'),
      $container->get('user_crud.cloudinary')
    );
  }


  // public function getFormId() {
  //   return 'cart_admin_form';
  // }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#required' => TRUE,
    ];

    $form['photos'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Product Photos'),
      '#upload_location' => 'temporary://product-images',
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png webp'],
        'file_validate_size' => [5 * 1024 * 1024],
      ],
    ];

    $form['category'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category'),
      '#required' => TRUE,
    ];

    $form['manufacturer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Manufacturer'),
      '#required' => TRUE,
    ];

    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#step' => '0.01',
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['offer'] = [
      '#type' => 'number',
      '#title' => $this->t('Offer (%)'),
      '#description' => $this->t('Enter discount percentage. Example: 20'),
      '#step' => '0.01',
      '#min' => 0,
      '#max' => 100,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Product'),
    ];

    return $form;
  }


  public function validateForm( array &$form, FormStateInterface $form_state) {

    $title = trim($form_state->getValue('title'));
    $description = trim($form_state->getValue('description'));
    $photos = $form_state->getValue('photos');
    $quantity = $form_state->getValue('quantity');
    $amount = $form_state->getValue('amount');
    $offer = $form_state->getValue('offer');
    $category = trim($form_state->getValue('category'));
    $manufacturer = trim($form_state->getValue('manufacturer'));

    // Title validation.
    if ($title === '') {
      $form_state->setErrorByName(
        'title',
        $this->t('Title cannot be empty.')
      );
    }
    elseif (mb_strlen($title) < 3) {
      $form_state->setErrorByName(
        'title',
        $this->t('Title must be at least 3 characters.')
      );
    }

    // Description validation.
    if ($description === '') {
      $form_state->setErrorByName(
        'description',
        $this->t('Description cannot be empty.')
      );
    }

    // Category validation.
    if ($category === '') {
      $form_state->setErrorByName(
        'category',
        $this->t('Category cannot be empty.')
      );
    }

    // Manufacturer validation.
    if ($manufacturer === '') {
      $form_state->setErrorByName(
        'manufacturer',
        $this->t('Manufacturer cannot be empty.')
      );
    }

    // Photo validation.
    if (empty($photos) || !is_array($photos)) {
      $form_state->setErrorByName(
        'photos',
        $this->t('Please upload at least one photo.')
      );
    }

    // Quantity validation.
    if (!is_numeric($quantity) || (int) $quantity != $quantity) {
      $form_state->setErrorByName(
        'quantity',
        $this->t(
          'Quantity must be a whole number. Example: 1, 2, 3...'
        )
      );
    }
    elseif ($quantity < 0) {
      $form_state->setErrorByName(
        'quantity',
        $this->t('Quantity cannot be negative.')
      );
    }

    // Amount validation.
    if (!is_numeric($amount)) {
      $form_state->setErrorByName(
        'amount',
        $this->t('Amount must be a valid number.')
      );
    }
    elseif ($amount < 0) {
      $form_state->setErrorByName(
        'amount',
        $this->t('Amount cannot be negative.')
      );
    }

    // Offer validation.
    if (!is_numeric($offer)) {
      $form_state->setErrorByName(
        'offer',
        $this->t('Offer must be a valid number.')
      );
    }
    elseif ($offer < 0 || $offer > 100) {
      $form_state->setErrorByName(
        'offer',
        $this->t('Offer must be between 0 and 100.')
      );
    }
  }

  /**
   * Submit form.
   */
  public function submitForm( array &$form,  FormStateInterface $form_state ) {

    try {

      /*
       * Get uploaded file IDs.
       */
      $file_ids = $form_state->getValue('photos');

      /*
       * Store Cloudinary URLs here.
       */
      $photo_urls = [];

      /*
       * Upload every selected image to Cloudinary.
       */
      foreach ($file_ids as $file_id) {

        $file = File::load($file_id);

        if (!$file) {
          throw new \RuntimeException(
            'Uploaded image could not be found.'
          );
        }

        /*
         * Get Drupal temporary file path.
         */
        $file_uri = $file->getFileUri();

        /*
         * Convert Drupal URI to actual server path.
         */
        $file_path = \Drupal::service('file_system') ->realpath($file_uri);

        if (!$file_path || !file_exists($file_path)) {
          throw new \RuntimeException(
            'Uploaded image file could not be found on the server.'
          );
        }

        /*
         * Upload image to Cloudinary.
         *
         * This returns the Cloudinary URL.
         */
        $cloudinary_url = $this->cloudinaryService
          ->uploadImage($file_path);

        /*
         * Store returned URL.
         */
        $photo_urls[] = $cloudinary_url;
      }

     
      $data = [
        'title' => trim($form_state->getValue('title')),
        'description' => trim($form_state->getValue('description')),
        'category' => trim($form_state->getValue('category')),
        'manufacturer' => trim($form_state->getValue('manufacturer')),
        'photos' => $photo_urls,
        'quantity' => (int) $form_state->getValue('quantity'),
        'amount' => (float) $form_state->getValue('amount'),
        'offer' => (float) $form_state->getValue('offer'),
      ];

     
      $this->cartService->addProduct($data);

      
      $this->messenger()->addStatus(
        $this->t('Cart product has been added successfully.')
      );

     
      $form_state->setRedirect('user_crud.cart_list');

    }
    catch (\Throwable $e) {

      \Drupal::logger('user_crud')->error('Error while adding product: @message',['@message' => $e->getMessage(),]);

      $this->messenger()->addError( $this->t('Unable to add the product. Please try again.' ) );
    }
  }

}