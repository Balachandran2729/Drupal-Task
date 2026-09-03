<?php

namespace Drupal\user_crud\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserCrudEditForm extends FormBase
{
    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructor.
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager)
    {
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'user_crud_edit_form';
    }

    /**
     * Build the edit form.
     */
    public function buildForm(
        array $form,
        FormStateInterface $form_state,
        UserInterface $user = NULL
    ) {
        if (!$user) {
            return $form;
        }

        // Store the user ID for submitForm().
        $form['user_id'] = [
            '#type' => 'hidden',
            '#value' => $user->id(),
        ];

        $form['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Username'),
            '#default_value' => $user->getAccountName(),
            '#required' => TRUE,
        ];

        $form['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#default_value' => $user->getEmail(),
            '#required' => TRUE,
        ];

        $form['phone'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Phone Number'),
            '#default_value' => $user->get('field_phone_number')->value,
            '#required' => TRUE,
        ];

        $form['status'] = [
            '#type' => 'select',
            '#title' => $this->t('Status'),
            '#options' => [
                1 => $this->t('Active'),
                0 => $this->t('Blocked'),
            ],
            '#default_value' => $user->isActive() ? 1 : 0,
        ];

        $form['password'] = [
            '#type' => 'password',
            '#title' => $this->t('New Password'),
            '#description' => $this->t('Leave empty if you do not want to change the password.'),
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Update User'),
        ];

        $form['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancel'),
            '#url' => \Drupal\Core\Url::fromRoute('user_crud.list'),
        ];

        return $form;
    }

    /**
 * Validate the edit form.
 */
public function validateForm(
    array &$form,
    FormStateInterface $form_state
) {
    $user_id = $form_state->getValue('user_id');

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

    // Check username belongs to another user.
    $existing_user = user_load_by_name($username);

    if ($existing_user && $existing_user->id() != $user_id) {
        $form_state->setErrorByName(
            'name',
            $this->t('This username is already taken.')
        );
    }

    // Check email belongs to another user.
    $existing_email = user_load_by_mail($email);

    if ($existing_email && $existing_email->id() != $user_id) {
        $form_state->setErrorByName(
            'email',
            $this->t('This email address is already registered.')
        );
    }

    // Password is optional during editing.
        if (!empty($password) && !preg_match(
            '/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}$/',
            $password
        )) {
            $form_state->setErrorByName(
                'password',
                $this->t(
                    'Password must be at least 8 characters long and contain at least one uppercase letter, one number, and one special character.'
                )
            );
        }

        // Phone number validation.
    if (!preg_match('/^\+65[0-9]{8}$/', $phone)) {
        $form_state->setErrorByName(
            'phone',
            $this->t(
                'Phone number must start with +65 and contain 8 digits after it.'
            )
        );
    }
}

    /**
     * Submit the edit form.
     */
    public function submitForm(
        array &$form,
        FormStateInterface $form_state
    ) {
        $user_id = $form_state->getValue('user_id');

        $user = $this->entityTypeManager
            ->getStorage('user')
            ->load($user_id);

        if (!$user) {
            $this->messenger()->addError(
                $this->t('User not found.')
            );

            return;
        }

        // Update username.
        $user->setUsername(
            $form_state->getValue('name')
        );

        // Update email.
        $user->setEmail(
            $form_state->getValue('email')
        );

        // Update status.
        $user->set(
            'status',
            $form_state->getValue('status')
        );

        // Update phone number.

        $user->set(
            'field_phone_number',
            $form_state->getValue('phone')
        );

        // Update password only if entered.
        $password = $form_state->getValue('password');

        if (!empty($password)) {
            $user->setPassword($password);
        }

        // Save changes to database.
        $user->save();

        $this->messenger()->addStatus(
            $this->t('User @name has been updated successfully.', [
                '@name' => $user->getAccountName(),
            ])
        );

        $form_state->setRedirect('user_crud.list');
    }
}