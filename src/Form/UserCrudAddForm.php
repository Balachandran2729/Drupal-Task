<?php

namespace Drupal\user_crud\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class UserCrudAddForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'user_crud_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Username'),
            '#required' => TRUE,
            '#maxlength' => 60,
        ];

        $form['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#required' => TRUE,
        ];

        $form['password'] = [
            '#type' => 'password',
            '#title' => $this->t('Password'),
            '#required' => TRUE,
            '#description' => $this->t(
                'Password must be at least 8 characters long.'
            ),
        ];

        $form['phone'] = [
            '#type' => 'tel',
            '#title' => $this->t('Phone Number'),
            '#required' => TRUE,
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Create User'),
        ];

        return $form;
    }

    /**
     * Validate the form.
     */
   public function validateForm(array &$form, FormStateInterface $form_state) {
    $username = trim($form_state->getValue('name'));
    $email = trim($form_state->getValue('email'));
    $password = $form_state->getValue('password');
    $phone = trim($form_state->getValue('phone'));

    // Username validation.
    if (strlen($username) < 3) {
        $form_state->setErrorByName(
            'name',
            $this->t('Username must be at least 3 characters long.')
        );
    }

    // Check whether username already exists.
    if (user_load_by_name($username)) {
        $form_state->setErrorByName(
            'name',
            $this->t('This username is already taken.')
        );
    }

    // Check whether email already exists.
    if (user_load_by_mail($email)) {
        $form_state->setErrorByName(
            'email',
            $this->t('This email address is already registered.')
        );
    }

    // Password validation.
    if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}$/', $password)) {
        $form_state->setErrorByName(
            'password',
            $this->t('Password must be at least 8 characters and contain one uppercase letter, one number, and one special character.')
        );
    }

    // Phone validation.
    if (!preg_match('/^\+65[0-9]{8}$/', $phone)) {
        $form_state->setErrorByName(
            'phone',
            $this->t('Phone number must start with +65 and contain 8 digits.')
        );
    }
}
    /**
     * {@inheritdoc}
     */
    public function submitForm(
        array &$form,
        FormStateInterface $form_state
    ) {
        $username = trim($form_state->getValue('name'));
        $email = trim($form_state->getValue('email'));
        $password = $form_state->getValue('password');
        $phone = trim($form_state->getValue('phone'));

        $user = User::create([
            'name' => $username,
            'mail' => $email,
            'status' => 1,
        ]);
        $user->set('field_phone_number', $phone);
        $user->setPassword($password);

        $user->save();

        $this->messenger()->addStatus(
            $this->t(
                'User @name , @email (ID: @id) has been created successfully.',
                [
                    '@name' => $username,
                    '@email' => $email,
                    '@id' => $user->id(),
                ]
            )
        );

        $form_state->setRedirect('user_crud.list');
    }
}