<?php

namespace Drupal\user_crud\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user_crud\Service\UserCrudNotificationService;

class UserCrudSendNotificationForm extends FormBase {

  protected UserCrudNotificationService $notificationService;

  public function __construct(?UserCrudNotificationService $notificationService = NULL) {
    $this->notificationService = $notificationService ?? \Drupal::service('user_crud.notification_service');
  }

  public function getFormId() {
    return 'user_crud_send_notification_form';
  }

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

    $tokens = \Drupal::state()->get('user_crud.notification_tokens', []);

    $form['token_table'] = [
      '#type' => 'table',
      '#header' => ['Name', 'ID', 'Device', 'Token', 'Action'],
      '#empty' => $this->t('No registered device tokens found.'),
    ];

    foreach ($tokens as $index => $tokenData) {
      $form['token_table'][$index] = [
        'name' => ['#plain_text' => $tokenData['name'] ?? ''],
        'id' => ['#plain_text' => $tokenData['id'] ?? ''],
        'device' => ['#plain_text' => $tokenData['device'] ?? ''],
        'token' => ['#plain_text' => $tokenData['token'] ?? ''],
        'action' => [
          '#type' => 'submit',
          '#value' => $this->t('Send to this user'),
          '#name' => 'send_single_' . $index,
          '#submit' => ['::submitForm'],
          '#attributes' => ['class' => ['button', 'button--small']],
        ],
      ];
    }

    $form['send_all'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send to all users'),
      '#submit' => ['::submitForm'],
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $title = trim((string) $form_state->getValue('title'));
    $description = trim((string) $form_state->getValue('description'));

    if ($title === '' || $description === '') {
      $form_state->setErrorByName('title', $this->t('Title and description are required.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $title = trim((string) $form_state->getValue('title'));
    $description = trim((string) $form_state->getValue('description'));

    $tokens = \Drupal::state()->get('user_crud.notification_tokens', []);
    $targetedToken = NULL;

    $triggering_element = $form_state->getTriggeringElement();
    $triggering_name = $triggering_element['#name'] ?? '';

    if (strpos($triggering_name, 'send_single_') === 0) {
      $index = (int) str_replace('send_single_', '', $triggering_name);
      if (isset($tokens[$index])) {
        $targetedToken = (string) ($tokens[$index]['token'] ?? '');
      }
    }

    if ($title === '' || $description === '') {
      $this->messenger()->addError($this->t('Title and description are required.'));
      return;
    }

    if ($targetedToken) {
      $recipients = [
        $tokens[$index] ?? [],
      ];
    }
    else {
      $recipients = $tokens;
    }

    $result = $this->notificationService->sendNotifications($title, $description, $recipients);

    if (!empty($result['errors'])) {
      $this->messenger()->addWarning($this->t('Notification was saved, but some device pushes failed.'));
    }
    elseif ($result['success'] ?? FALSE) {
      $this->messenger()->addStatus($this->t('Notification sent successfully.'));
    }
    else {
      $this->messenger()->addError($this->t($result['message'] ?? 'Unable to send notification.'));
      return;
    }

    $form_state->setRedirect('user_crud.notifications');
  }

}
