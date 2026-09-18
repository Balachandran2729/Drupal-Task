<?php

namespace Drupal\user_crud\Service;

use Drupal\Core\Database\Connection;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class UserCrudNotificationService {

  protected ClientInterface $httpClient;
  protected Connection $database;

  public function __construct(ClientInterface $httpClient, Connection $database) {
    $this->httpClient = $httpClient;
    $this->database = $database;
  }

  /**
   * Returns all stored notifications from the database.
   */
  public function getNotifications(): array {
    try {
      $rows = $this->database->select('user_crud_notifications', 'n')
        ->fields('n')
        ->orderBy('n.id', 'DESC')
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC);

      $notifications = [];

      foreach ($rows as $row) {
        $notifications[] = [
          'id' => (int) $row['id'],
          'title' => (string) $row['title'],
          'description' => (string) $row['description'],
          'sent_time' => $row['sent_time'] ? (int) $row['sent_time'] : NULL,
          'recipients' => json_decode((string) $row['recipients'], TRUE) ?: [],
        ];
      }

      return $notifications;
    }
    catch (\Throwable $exception) {
      \Drupal::logger('user_crud')->error('getNotifications failed: @message', [
        '@message' => $exception->getMessage(),
      ]);

      return [];
    }
  }

  public function getNotification(int $id): ?array {
    try {
      $row = $this->database->select('user_crud_notifications', 'n')
        ->fields('n')
        ->condition('n.id', $id)
        ->execute()
        ->fetchAssoc();
 
      if (!$row) {
        return NULL;
      }
 
      return [
        'id' => (int) $row['id'],
        'title' => (string) $row['title'],
        'description' => (string) $row['description'],
        'sent_time' => $row['sent_time'] ? (int) $row['sent_time'] : NULL,
        'recipients' => json_decode((string) $row['recipients'], TRUE) ?: [],
      ];
    }
    catch (\Throwable $exception) {
      \Drupal::logger('user_crud')->error('getNotification failed for id @id: @message', [
        '@id' => $id,
        '@message' => $exception->getMessage(),
      ]);
 
      return NULL;
    }
  }

  /**
   * Saves one notification entry into the database.
   */
  public function saveNotification(string $title, string $description, array $recipients = [], ?int $sentTime = NULL): array {
    $now = time();

    $notification = [
      'title' => trim($title),
      'description' => trim($description),
      'recipients' => array_values($recipients),
      'sent_time' => $sentTime ?? $now,
    ];

    try {
      $id = $this->database->insert('user_crud_notifications')
        ->fields([
          'title' => $notification['title'],
          'description' => $notification['description'],
          // Recipients aren't a fixed set of columns (name/device/token
          // varies per send), so we store the list as JSON in one column
          // rather than needing a 3rd join table.
          'recipients' => json_encode($notification['recipients'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
          'sent_time' => $notification['sent_time'],
          'created' => $now,
        ])
        ->execute();

      $notification['id'] = (int) $id;

      return $notification;
    }
    catch (\Throwable $exception) {
      \Drupal::logger('user_crud')->error('saveNotification failed: @message', [
        '@message' => $exception->getMessage(),
      ]);

      throw new \RuntimeException('Unable to save notification to storage.', 0, $exception);
    }
  }

    /**
   * Sends notifications . 
   */
  public function sendNotifications( string $title, string $description, array $recipients): array {
    
    $validRecipients = [];

    foreach ($recipients as $recipient) {
      $token = trim((string) ($recipient['token'] ?? ''));

      if ($token !== '') {
        $validRecipients[] = [
          'name' => trim((string) ($recipient['name'] ?? '')),
          'id' => (string) ($recipient['id'] ?? ($recipient['app_user_id'] ?? '')),
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

    $tickets = [];

    $sendResults = [];
    $sendErrors = [];

    // Send notifications to Expo.

    foreach ($validRecipients as $recipient) {
      try {
        $response = $this->httpClient->post(
          'https://exp.host/--/api/v2/push/send',
          [
            'json' => [
              'to' => $recipient['token'],
              'title' => $title,
              'body' => $description,
            ],
          ]
        );

        $responseBody = json_decode((string) $response->getBody(), TRUE );

        $statusCode = $response->getStatusCode();

        $sendResults[] = [
          'token' => $recipient['token'],
          'status_code' => $statusCode,
          'response' => is_array($responseBody) ? $responseBody : [],
        ];

        if ($statusCode >= 400) {
          $sendErrors[] = [
            'token' => $recipient['token'],
            'error' => is_array($responseBody)
              ? json_encode($responseBody)
              : 'HTTP error',
          ];

          continue;
        }

        // Get Expo ticket ID.

        $ticketId = $responseBody['data']['id'] ?? NULL;

        $ticketStatus = $responseBody['data']['status'] ?? NULL;

        if ($ticketStatus !== 'ok' || empty($ticketId)) {
          $sendErrors[] = [
            'token' => $recipient['token'],
            'error' => 'Expo did not return a valid ticket ID.',
          ];

          continue;
        }

        // Save ticket ID so we can check the receipt later.
        
        $tickets[] = [
          'ticket_id' => $ticketId,
          'token' => $recipient['token'],
        ];
      }
      catch (GuzzleException $exception) {
        $sendErrors[] = [
          'token' => $recipient['token'],
          'error' => $exception->getMessage(),
        ];

        \Drupal::logger('user_crud')->error(
          'Expo push request failed for token @token: @message',
          [
            '@token' => $recipient['token'],
            '@message' => $exception->getMessage(),
          ]
        );
      }
      catch (\Throwable $exception) {
        $sendErrors[] = [
          'token' => $recipient['token'],
          'error' => $exception->getMessage(),
        ];

        \Drupal::logger('user_crud')->error(
          'Unexpected Expo push error for token @token: @message',
          [
            '@token' => $recipient['token'],
            '@message' => $exception->getMessage(),
          ]
        );
      }
    }

 
    // Check Expo receipts.

    if (!empty($tickets)) {

      sleep(1);

      $receiptIds = [];

      foreach ($tickets as $ticket) {
        $receiptIds[] = $ticket['ticket_id'];
      }

      try {
        $receiptResponse = $this->httpClient->post(
          'https://exp.host/--/api/v2/push/getReceipts',
          [
            'json' => [
              'ids' => $receiptIds,
            ],
          ]
        );

        $receiptBody = json_decode((string) $receiptResponse->getBody(), TRUE );

        $receipts = $receiptBody['data'] ?? [];

        foreach ($tickets as $ticket) {

          $ticketId = $ticket['ticket_id'];
          $token = $ticket['token'];

          $receipt = $receipts[$ticketId] ?? NULL;

          if (!$receipt) {
            $sendErrors[] = [
              'token' => $token,
              'ticket_id' => $ticketId,
              'error' => 'Expo receipt was not found.',
            ];

            continue;
          }

          $receiptStatus = $receipt['status'] ?? NULL;

          if ($receiptStatus === 'ok') {

            \Drupal::logger('user_crud')->info(
              'Expo notification delivered successfully for token @token. Ticket: @ticket',
              [
                '@token' => $token,
                '@ticket' => $ticketId,
              ]
            );
          }

          else {

            $errorCode = $receipt['details']['error'] ?? 'Unknown error';

            $errorMessage = $receipt['message'] ?? '';

            $sendErrors[] = [
              'token' => $token,
              'ticket_id' => $ticketId,
              'error' => $errorCode,
              'message' => $errorMessage,
            ];

            \Drupal::logger('user_crud')->error(
              'Expo receipt failed for token @token. Error: @error. Message: @message',
              [
                '@token' => $token,
                '@error' => $errorCode,
                '@message' => $errorMessage,
              ]
            );
          }
        }
      }
      catch (GuzzleException $exception) {

        \Drupal::logger('user_crud')->error(
          'Expo receipt request failed: @message',
          [
            '@message' => $exception->getMessage(),
          ]
        );

        $sendErrors[] = [
          'error' => 'Unable to retrieve Expo receipts.',
          'message' => $exception->getMessage(),
        ];
      }

      catch (\Throwable $exception) {

        \Drupal::logger('user_crud')->error(
          'Unexpected Expo receipt error: @message',
          [
            '@message' => $exception->getMessage(),
          ]
        );

        $sendErrors[] = [
          'error' => 'Unexpected receipt error.',
          'message' => $exception->getMessage(),
        ];
      }
    }

    $this->saveNotification( $title,  $description, $validRecipients );

    return [
      'success' => empty($sendErrors),
      'message' => empty($sendErrors)
        ? 'Notification sent and receipt confirmed successfully.'
        : 'Notification was sent, but some delivery checks failed.',
      'sent' => $sendResults,
      'tickets' => $tickets,
      'errors' => $sendErrors,
    ];
  }

}