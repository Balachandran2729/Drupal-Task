<?php

namespace Drupal\user_crud\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class UserCrudNotificationService {

  protected ClientInterface $httpClient;

  public function __construct(ClientInterface $httpClient) {
    $this->httpClient = $httpClient;
  }

  /**
   * Returns all stored notifications.
   */
  public function getNotifications(): array {
    $notifications = \Drupal::state()->get('user_crud.notifications', []);

    foreach ($notifications as $index => $notification) {
      $notifications[$index]['title'] = trim((string) ($notification['title'] ?? ''));
      $notifications[$index]['description'] = trim((string) ($notification['description'] ?? ''));
      $notifications[$index]['sent_time'] = isset($notification['sent_time']) ? (int) $notification['sent_time'] : NULL;
      $notifications[$index]['recipients'] = is_array($notification['recipients'] ?? NULL) ? $notification['recipients'] : [];
    }

    return array_values($notifications);
  }

  /**
   * Saves a notification entry.
   */
  public function saveNotification(string $title, string $description, array $recipients = [], ?int $sentTime = NULL): array {
    $notifications = $this->getNotifications();

    $notification = [
      'title' => trim($title),
      'description' => trim($description),
      'sent_time' => $sentTime ?? time(),
      'recipients' => array_values($recipients),
    ];

    $notifications[] = $notification;

    \Drupal::state()->set('user_crud.notifications', array_values($notifications));

    return $notification;
  }

  /**
   * Sends the notification to Expo push recipients.
   */
  public function sendNotifications(string $title, string $description, array $recipients): array {
    $validRecipients = [];

    foreach ($recipients as $recipient) {
      $token = trim((string) ($recipient['token'] ?? ''));
      if ($token !== '') {
        $validRecipients[] = [
          'name' => trim((string) ($recipient['name'] ?? '')),
          'id' => (string) ($recipient['id'] ?? ''),
          'device' => trim((string) ($recipient['device'] ?? '')),
          'token' => $token,
        ];
      }
    }

    if (empty($validRecipients)) {
      return [
        'success' => FALSE,
        'message' => 'No registered device tokens found.',
      ];
    }

    $sendResults = [];
    $sendErrors = [];

    foreach ($validRecipients as $recipient) {
      try {
        $response = $this->httpClient->post('https://exp.host/--/api/v2/push/send', [
          'json' => [
            'to' => $recipient['token'],
            'title' => $title,
            'body' => $description,
          ],
        ]);

        $responseBody = json_decode((string) $response->getBody(), TRUE);
        $statusCode = $response->getStatusCode();

        $sendResults[] = [
          'token' => $recipient['token'],
          'status_code' => $statusCode,
          'response' => is_array($responseBody) ? $responseBody : [],
        ];

        if ($statusCode >= 400) {
          $sendErrors[] = [
            'token' => $recipient['token'],
            'error' => is_array($responseBody) ? json_encode($responseBody) : 'HTTP error',
          ];
        }
      }
      catch (GuzzleException $exception) {
        $sendErrors[] = [
          'token' => $recipient['token'],
          'error' => $exception->getMessage(),
        ];

        \Drupal::logger('user_crud')->error('Expo push request failed for token @token: @message', [
          '@token' => $recipient['token'],
          '@message' => $exception->getMessage(),
        ]);
      }
      catch (\Throwable $exception) {
        $sendErrors[] = [
          'token' => $recipient['token'],
          'error' => $exception->getMessage(),
        ];

        \Drupal::logger('user_crud')->error('Unexpected Expo push error for token @token: @message', [
          '@token' => $recipient['token'],
          '@message' => $exception->getMessage(),
        ]);
      }
    }

    $this->saveNotification($title, $description, $validRecipients);

    return [
      'success' => empty($sendErrors),
      'message' => empty($sendErrors) ? 'Notification sent successfully.' : 'Notification sent with some errors.',
      'sent' => $sendResults,
      'errors' => $sendErrors,
    ];
  }

}
