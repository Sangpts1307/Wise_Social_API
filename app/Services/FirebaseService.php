<?php

namespace App\Services;

use GuzzleHttp\Client;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FirebaseService
{
  const FCM_URL = 'https://fcm.googleapis.com/v1/projects/wisesocial-api2/messages:send';
  private $client;
  private $firebaseToken;
  private $lastError;

  /**
   * Initialize FirebaseService with Guzzle client and Firebase token.
   */
  public function __construct()
  {
    // Init Guzzle client for HTTP requests
    $this->client = new Client();

    // Define scope for Firebase token
    $scope = [
      'https://www.googleapis.com/auth/firebase.messaging'
    ];

    // Load service account credentials from file
    $pathToServiceAccount = storage_path('firebase_credentials.json');
    $credentials = new ServiceAccountCredentials($scope, $pathToServiceAccount);

    // Fetch and store Firebase token
    $credentials->fetchAuthToken();
    $this->firebaseToken = $credentials->getLastReceivedToken()["access_token"];
  }

  /**
   * Function  service send nofitication to device
   *
   * @param string $content message
   * @param string $deviceToken of device
   * @return void | mixed
   */
  public function sendFCM($content, $deviceToken)
  {
    try {
      // Send POST request to FCM wiƒth notification data
      $response = $this->client->request('POST', self::FCM_URL, [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->firebaseToken, // Use the Firebase token for authorization
          'Content-Type' => 'application/json', // Set content type to JSON
        ],
        'json' => [
          'message' => [
            'token' => $deviceToken, // The device token to send the notification to
            'notification' => [
              'title' => 'Wise Social', // Notification title
              'body' => $content, // Notification body/content
            ],
          ],
        ],
      ]);
      // dd(json_decode($response->getBody(), true)); // Uncomment for debugging, this will dump the response body
      return true; // Return true if the request was successful
    } catch (\Exception $e) {
      // Catch any exceptions that occur during the request
      $this->lastError = $e->getMessage(); // Store the error message
      return false; // Return false if an error occurred
    }
  }

  public function getLastError()
  {
    return $this->lastError;
  }
}
