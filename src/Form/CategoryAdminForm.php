<?php

namespace Drupal\user_crud\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user_crud\Service\CartAdminService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CategoryAdminForm extends FormBase {

  protected $cartService;


  public function __construct( CartAdminService $cart_service) {
    $this->cartService = $cart_service;
  }

 
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_crud.cart_admin_service')
    );
  }


  public function getFormId() {
    return 'category_admin_form';
  }

  public function buildForm( array $form, FormStateInterface $form_state ) {

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category Name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Category'),
    ];

    return $form;
  }


  public function validateForm( array &$form, FormStateInterface $form_state ) {

    $name = trim(
      $form_state->getValue('name')
    );

    if ($name === '') {
      $form_state->setErrorByName(
        'name',
        $this->t('Category name cannot be empty.')
      );

      return;
    }

    if (mb_strlen($name) < 2) {
      $form_state->setErrorByName(
        'name',
        $this->t(
          'Category name must be at least 2 characters.'
        )
      );

      return;
    }

    if ($this->cartService->categoryExists($name)) {
      $form_state->setErrorByName(
        'name',
        $this->t(
          'This category already exists.'
        )
      );
    }
  }


  public function submitForm( array &$form,  FormStateInterface $form_state ) {

    $name = trim(
      $form_state->getValue('name')
    );

    try {

      $this->cartService->addCategory($name);

      $this->messenger()->addStatus(
        $this->t(
          'Category @name has been added successfully.',
          [
            '@name' => $name,
          ]
        )
      );

      $destination = \Drupal::request()->query->get('destination');

      if (is_string($destination) && str_starts_with($destination, '/') && !str_starts_with($destination, '//')) {
        $form_state->setRedirectUrl(Url::fromUserInput($destination));
      }
      else {
        $form_state->setRedirect('user_crud.cart_add');
      }
    }
    catch (\Exception $e) {

      $this->messenger()->addError(
        $this->t(
          'Failed to add category.'
        )
      );
    }
  }

}