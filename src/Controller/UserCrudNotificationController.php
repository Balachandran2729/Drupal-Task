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
      $rows[] = [
        $notification['title'] ?? '',
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
   * API endpoint to submit a notification payload.
   */
  public function sendNotification(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (!is_array($data)) {
      return new JsonResponse([
        'error' => 'Invalid JSON body.',
      ], 400);
    }

    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));

    if ($title === '' || $description === '') {
      return new JsonResponse([
        'error' => 'Title and description are required.',
      ], 400);
    }

    $recipients = [];
    $targetedToken = trim((string) ($data['token'] ?? ''));

    if ($targetedToken !== '') {
      $tokens = \Drupal::state()->get('user_crud.notification_tokens', []);
      foreach ($tokens as $tokenData) {
        if ((string) ($tokenData['token'] ?? '') === $targetedToken) {
          $recipients[] = $tokenData;
          break;
        }
      }
    }
    else {
      $recipients = \Drupal::state()->get('user_crud.notification_tokens', []);
    }

    $notification = $this->notificationService->saveNotification($title, $description, $recipients);

    return new JsonResponse([
      'message' => 'Notification sent successfully.',
      'data' => $notification,
    ], 201);
  }

}
