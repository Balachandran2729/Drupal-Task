<?php

namespace Drupal\user_crud\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\user_crud\Service\CartAdminService;
use Drupal\user_crud\Service\CloudinaryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartAdminEditForm extends FormBase {

  protected $cartService;

  protected $cloudinaryService;

  protected $product;

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

  public function getFormId() {
    return 'cart_admin_edit_form';
  }


  public function buildForm( array $form, FormStateInterface $form_state, $id = NULL ) {

    $this->product = $this->cartService->getProduct($id);

    if (!$this->product) {
      throw new NotFoundHttpException();
    }

     $categories = $this->cartService->getActiveCategories();

    $category_options = [];

    foreach ($categories as $category) {
      $category_options[$category] = $category;
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

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => $category_options,
      '#default_value' => $this->product->category,
      '#empty_option' => $this->t('- Select Category -'),
      '#required' => TRUE,
    ];

    $form['add_category'] = [
      '#type' => 'link',
      '#title' => $this->t('+ Add Category'),
      '#url' => \Drupal\Core\Url::fromRoute(
        'user_crud.category_add',
        [],
        [
          'query' => [
            'destination' => \Drupal\Core\Url::fromRoute(
              'user_crud.cart_edit',
              ['id' => $this->product->id]
            )->toString(),
          ],
        ]
      ),
      '#attributes' => [
        'class' => [
          'button',
        ],
      ],
    ];

    $form['manufacturer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Manufacturer'),
      '#default_value' => $this->product->manufacturer,
      '#required' => FALSE,
    ];


    $photos = json_decode($this->product->photos, TRUE);

    if (!is_array($photos)) {
      $photos = [];
    }

    $form['existing_photos'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    if (!empty($photos)) {

      foreach ($photos as $index => $photo_url) {

        $form['existing_photos'][$index] = [
          '#type' => 'container',
        ];

        $form['existing_photos'][$index]['url'] = [
          '#type' => 'hidden',
          '#value' => $photo_url,
        ];

        $form['existing_photos'][$index]['remove'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Remove this photo'),
        ];

        $form['existing_photos'][$index]['preview'] = [
          '#type' => 'markup',
          '#markup' => '
            <div style="margin-bottom: 15px;">
              <img
                src="' . htmlspecialchars($photo_url, ENT_QUOTES, 'UTF-8') . '"
                style="width: 120px; height: 120px; object-fit: cover; border: 1px solid #ccc; border-radius: 5px;"
              >
              <div style="margin-top: 5px; word-break: break-all;">
                ' . htmlspecialchars($photo_url, ENT_QUOTES, 'UTF-8') . '
              </div>
            </div>
          ',
        ];
      }
    }
    else {
      $form['existing_photos']['empty'] = [
        '#markup' => '<p>No existing photos.</p>',
      ];
    }

    $form['new_photos'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Add New Photos'),
      '#upload_location' => 'temporary://product-images',
      '#multiple' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png webp'],
        'file_validate_size' => [5 * 1024 * 1024],
      ],
      '#description' => $this->t(
        'Select one or more new images. Maximum size: 5 MB per image.'
      ),
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


  public function validateForm( array &$form, FormStateInterface $form_state ) {

    $title = trim($form_state->getValue('title'));
    $description = trim($form_state->getValue('description'));
    $quantity = $form_state->getValue('quantity');
    $amount = $form_state->getValue('amount');
    $offer = $form_state->getValue('offer');
    $category = trim($form_state->getValue('category'));
    $manufacturer = trim($form_state->getValue('manufacturer'));

    /*
     * Title validation.
     */
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

    /*
     * Description validation.
     */
    if ($description === '') {
      $form_state->setErrorByName(
        'description',
        $this->t('Description cannot be empty.')
      );
    }

    /*
     * Category validation.
     */
    if ($category === '') {
      $form_state->setErrorByName(
        'category',
        $this->t('Category cannot be empty.')
      );
    }

    /*
     * Manufacturer validation.
     */
    if ($manufacturer === '') {
      $form_state->setErrorByName(
        'manufacturer',
        $this->t('Manufacturer cannot be empty.')
      );
    }

    /*
     * Photo validation.
     *
     * We check whether at least one existing photo remains
     * OR a new photo has been selected.
     */
    $existing_photos = $form_state->getValue('existing_photos', []);
    $new_photos = $form_state->getValue('new_photos', []);

    $remaining_existing_photos = [];

    if (is_array($existing_photos)) {

      foreach ($existing_photos as $photo) {

        if (!empty($photo['url']) && empty($photo['remove'])) {
          $remaining_existing_photos[] = $photo['url'];
        }
      }
    }

    if (
      empty($remaining_existing_photos)
      && empty($new_photos)
    ) {
      $form_state->setErrorByName('new_photos', $this->t('Please keep at least one photo or add a new photo.')
      );
    }

    /*
     * Quantity validation.
     */
    if (!is_numeric($quantity) || (int) $quantity != $quantity) {
      $form_state->setErrorByName(
        'quantity',
        $this->t('Quantity must be a whole number.')
      );
    }
    elseif ($quantity < 0) {
      $form_state->setErrorByName(
        'quantity',
        $this->t('Quantity cannot be negative.')
      );
    }

    /*
     * Amount validation.
     */
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

    /*
     * Offer validation.
     */
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


  public function submitForm( array &$form, FormStateInterface $form_state ) {

  
    $existing_photos = $form_state->getValue('existing_photos', []);

    $photos = [];

    if (is_array($existing_photos)) {

      foreach ($existing_photos as $photo) {

        /*
         * Keep photo only if the admin did NOT
         * select the remove checkbox.
         */
        if (
          !empty($photo['url'])
          && empty($photo['remove'])
        ) {
          $photos[] = $photo['url'];
        }
      }
    }

    $new_photos = $form_state->getValue('new_photos', []);

    if (is_array($new_photos)) {

      foreach ($new_photos as $fid) {

        $file = File::load($fid);

        if (!$file) {
          continue;
        }

        try {

          /*
           * Get Drupal temporary file path.
           */
          $file_path = $file->getFileUri();

          $real_path = \Drupal::service('file_system')
            ->realpath($file_path);

          /*
           * Upload image to Cloudinary.
           */
          $cloudinary_url = $this->cloudinaryService
            ->uploadImage($real_path);

          /*
           * Add Cloudinary URL to existing photos.
           */
          if (!empty($cloudinary_url)) {
            $photos[] = $cloudinary_url;
          }

        }
        catch (\Exception $e) {

          \Drupal::logger('user_crud')->error(
            'Cloudinary image upload failed: @message',
            [
              '@message' => $e->getMessage(),
            ]
          );

          $this->messenger()->addError(
            $this->t(
              'Failed to upload one of the new images.'
            )
          );

          return;
        }
      }
    }

    $data = [
      'title' => trim($form_state->getValue('title')),
      'description' => trim($form_state->getValue('description')),
      'category' => trim($form_state->getValue('category')),
      'manufacturer' => trim($form_state->getValue('manufacturer')),
      'photos' => $photos,
      'quantity' => (int) $form_state->getValue('quantity'),
      'amount' => (float) $form_state->getValue('amount'),
      'offer' => (float) $form_state->getValue('offer'),
    ];

    /*
     * Update product.
     */
    $this->cartService->updateProduct($this->product->id, $data );

    $this->messenger()->addStatus( $this->t('Cart product has been updated successfully.' )
    );

    $form_state->setRedirect('user_crud.cart_list');
  }

}