<?php

namespace Drupal\user_crud\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserCrudDeleteForm extends ConfirmFormBase
{
    /**
     * The user being deleted.
     *
     * @var \Drupal\user\UserInterface
     */
    protected $user;

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager
    ) {
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
        return 'user_crud_delete_form';
    }

    /**
     * Build the confirmation form.
     */
    public function buildForm(
        array $form,
        FormStateInterface $form_state,
        UserInterface $user = NULL
    ) {
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $this->user = $user;

        // Prevent deleting the current logged-in user.
        if ($user->id() == $this->currentUser()->id()) {
            $this->messenger()->addError(
                $this->t('You cannot delete your own account.')
            );

            $form_state->setRedirect('user_crud.list');

            return [];
        }

        // Prevent deleting the main administrator account.
        if ($user->id() == 1) {
            $this->messenger()->addError(
                $this->t('The main administrator account cannot be deleted.')
            );

            $form_state->setRedirect('user_crud.list');

            return [];
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t(
            'Are you sure you want to delete the user "@name"?',
            [
                '@name' => $this->user->getAccountName(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('user_crud.list');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return $this->t('Delete User');
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelText()
    {
        return $this->t('Cancel');
    }

    /**
     * Submit the delete form.
     */
    public function submitForm(
        array &$form,
        FormStateInterface $form_state
    ) {
        // Extra security check before deletion.
        if (!$this->user) {
            $this->messenger()->addError(
                $this->t('User not found.')
            );

            return;
        }

        // Prevent deleting the current logged-in user.
        if ($this->user->id() == $this->currentUser()->id()) {
            $this->messenger()->addError(
                $this->t('You cannot delete your own account.')
            );

            return;
        }

        // Prevent deleting the main administrator account.
        if ($this->user->id() == 1) {
            $this->messenger()->addError(
                $this->t('The main administrator account cannot be deleted.')
            );

            return;
        }

        $username = $this->user->getAccountName();

        // Delete the user.
        $this->user->delete();

        $this->messenger()->addStatus(
            $this->t(
                'User @name has been deleted successfully.',
                [
                    '@name' => $username,
                ]
            )
        );

        $form_state->setRedirect('user_crud.list');
    }
}