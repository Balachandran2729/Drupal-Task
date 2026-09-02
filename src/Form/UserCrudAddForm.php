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
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Create User'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $username = $form_state->getValue('name');
        $email = $form_state->getValue('email');
        $password = $form_state->getValue('password');

        $user = User::create([
            'name' => $username,
            'mail' => $email,
            'status' => 1,
        ]);

        $user->setPassword($password);

        $user->save();

        $this->messenger()->addStatus(
            $this->t('User @name has been created successfully.', [
                '@name' => $username,
            ])
        );

        $form_state->setRedirect('user_crud.list');
    }
}