<?php

namespace Drupal\user_crud\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\user_crud\Service\UserCrudNotificationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserCrudNotificationController extends ControllerBase {

  protected UserCrudNotificationService $notificationService;

  public function __construct(UserCrudNotificationService $notificationService) {
    $this->notificationService = $notificationService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_crud.notification_service')
    );
  }

  /**
   * Displays the admin notification list page.
   */
  public function list() {
    $notifications = $this->notificationService->getNotifications();
 
    $rows = [];
 
    foreach ($notifications as $notification) {
      $titleLink = Link::createFromRoute(
        $notification['title'] ?? '(untitled)',
        'user_crud.notifications.detail',
        ['notification_id' => $notification['id']]
      )->toRenderable();
 
      $rows[] = [
        ['data' => $titleLink],
        $notification['description'] ?? '',
        $notification['sent_time'] ? date('Y-m-d H:i:s', (int) $notification['sent_time']) : 'No notification',
      ];
    }
 
    $build = [];
 
    $build['heading'] = [
      '#markup' => '<h2>Notifications</h2>',
    ];
 
    $build['send_link'] = Link::createFromRoute('Send Notification', 'user_crud.notifications.send')->toRenderable();
 
    $build['table'] = [
      '#type' => 'table',
      '#header' => ['Title', 'Description', 'Send Time'],
      '#rows' => $rows,
      '#empty' => 'No notifications found.',
    ];
 
    return $build;
  }

  /**
   * Displays a single notification's details, including who it went to.
   */
  public function detail(int $notification_id) {

    $notification = $this->notificationService->getNotification($notification_id);
 
    if (!$notification) {
      return [
        '#markup' => '<p>' . $this->t('Notification not found.') . '</p>',
      ];
    }
 
    $build = [];
 
    $build['back_link'] = Link::createFromRoute(
      $this->t('&laquo; Back to notifications'),
      'user_crud.notifications'
    )->toRenderable();
 
    $build['heading'] = [
      '#markup' => '<h2>' . $notification['title'] . '</h2>',
    ];
 
    $sentTime = $notification['sent_time'] ? date('Y-m-d H:i:s', $notification['sent_time']) : 'Not sent';
 
    $build['summary'] = [
      '#markup' => '<p><strong>' . $this->t('Sent:') . '</strong> ' . $sentTime . '</p>'
        . '<p><strong>' . $this->t('Description:') . '</strong><br>' . nl2br($notification['description']) . '</p>',
    ];
 
    $recipientRows = [];
    foreach ($notification['recipients'] as $recipient) {
      $recipientRows[] = [
        $recipient['name'] ?? '',
        $recipient['id'] ?? '',
        $recipient['device'] ?? '',
        $recipient['token'] ?? '',
      ];
    }
 
    $build['recipients_heading'] = [
      '#markup' => '<h3>' . $this->t('Recipients (@count)', ['@count' => count($recipientRows)]) . '</h3>',
    ];
 
    $build['recipients_table'] = [
      '#type' => 'table',
      '#header' => ['Name', 'ID', 'Device', 'Token'],
      '#rows' => $recipientRows,
      '#empty' => 'No recipients recorded for this notification.',
    ];
 
    return $build;
  }

  /**
   * API endpoint to submit a notification payload.
   *
   * NOTE: this now calls sendNotifications() (push + save) instead of
   * saveNotification() (save only), which is what your original code
   * was doing - that's why hitting this endpoint never actually sent
   * anything to Expo.
   */
  // public function sendNotification(Request $request): JsonResponse {

  //   $data = json_decode($request->getContent(), TRUE);

  //   if (!is_array($data)) {
  //     return new JsonResponse([
  //       'error' => 'Invalid JSON body.',
  //     ], 400);
  //   }

  //   $title = trim((string) ($data['title'] ?? ''));
  //   $description = trim((string) ($data['description'] ?? ''));

  //   if ($title === '' || $description === '') {
  //     return new JsonResponse([
  //       'error' => 'Title and description are required.',
  //     ], 400);
  //   }

  //   $targetedToken = trim((string) ($data['token'] ?? ''));

  //   $query = \Drupal::database()->select('user_crud_notification_tokens', 't')
  //     ->fields('t');

  //   if ($targetedToken !== '') {
  //     $query->condition('t.token', $targetedToken);
  //   }

  //   $recipients = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

  //   $result = $this->notificationService->sendNotifications($title, $description, $recipients);

  //   if (empty($result['sent']) && !empty($result['message']) && ($result['success'] ?? FALSE) === FALSE) {
  //     return new JsonResponse([
  //       'error' => $result['message'],
  //     ], 400);
  //   }

  //   return new JsonResponse([
  //     'message' => $result['message'] ?? 'Notification sent successfully.',
  //     'sent' => $result['sent'] ?? [],
  //     'errors' => $result['errors'] ?? [],
  //   ], 201);
  // }

}