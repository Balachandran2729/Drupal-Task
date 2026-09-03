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

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Create User'),
        ];

        return $form;
    }

    /**
     * Validate the form.
     */
    public function validateForm(
        array &$form,
        FormStateInterface $form_state
    ) {
        $username = trim($form_state->getValue('name'));
        $email = trim($form_state->getValue('email'));
        $password = $form_state->getValue('password');

        // Username validation.
        if (strlen($username) < 3) {
            $form_state->setErrorByName(
                'name',
                $this->t('Username must be at least 3 characters long.')
            );
        }

        // Check whether username already exists.
        $existing_user = user_load_by_name($username);

        if ($existing_user) {
            $form_state->setErrorByName(
                'name',
                $this->t('This username is already taken.')
            );
        }

        // Check whether email already exists.
        $existing_email = user_load_by_mail($email);

        if ($existing_email) {
            $form_state->setErrorByName(
                'email',
                $this->t('This email address is already registered.')
            );
        }

        // Password validation.
        if (strlen($password) < 8) {
            $form_state->setErrorByName(
                'password',
                $this->t('Password must be at least 8 characters long.')
            );
        }

        // At least one uppercase letter.
        if (!preg_match('/[A-Z]/', $password)) {
            $form_state->setErrorByName(
                'password',
                $this->t('Password must contain at least one uppercase letter.')
            );
        }

        // At least one number.
        if (!preg_match('/[0-9]/', $password)) {
            $form_state->setErrorByName(
                'password',
                $this->t('Password must contain at least one number.')
            );
        }

        // At least one special character.
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $form_state->setErrorByName(
                'password',
                $this->t('Password must contain at least one special character.')
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

        $user = User::create([
            'name' => $username,
            'mail' => $email,
            'status' => 1,
        ]);

        $user->setPassword($password);

        $user->save();

        $this->messenger()->addStatus(
            $this->t(
                'User @name has been created successfully.',
                [
                    '@name' => $username,
                ]
            )
        );

        $form_state->setRedirect('user_crud.list');
    }
}