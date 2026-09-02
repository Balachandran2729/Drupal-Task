<?php

namespace Drupal\user_crud\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
        $this->user = $user;

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
        $username = $this->user->getAccountName();

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