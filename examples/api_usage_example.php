<?php

/**
 * Guestbook API Usage Example
 * 
 * This script demonstrates how to use the Guestbook RESTful API
 * using cURL to make HTTP requests.
 */

// Configuration
$baseUrl = 'http://localhost/guestbook/api/v1';

/**
 * Helper function to make API requests
 */
function apiRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
{
  $url = $GLOBALS['baseUrl'] . '/' . ltrim($endpoint, '/');

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

  // Set headers
  $defaultHeaders = [
    'Content-Type: application/json',
    'Accept: application/json'
  ];

  $allHeaders = array_merge($defaultHeaders, $headers);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);

  // Set method and data
  switch (strtoupper($method)) {
    case 'POST':
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      break;
    case 'PUT':
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      break;
    case 'DELETE':
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
      break;
    case 'PATCH':
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      break;
    default:
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
  }

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return [
    'status_code' => $httpCode,
    'response' => json_decode($response, true) ?: $response
  ];
}

/**
 * Example 1: Register a new user
 */
echo "=== Example 1: Register a new user ===\n";

$registerData = [
  'name' => 'John Doe',
  'email' => 'john@example.com',
  'password' => 'password123',
  'password_confirmation' => 'password123'
];

$result = apiRequest('POST', 'auth/register', $registerData);
echo "Status: {$result['status_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

/**
 * Example 2: Login user
 */
echo "=== Example 2: Login user ===\n";

$loginData = [
  'email' => 'john@example.com',
  'password' => 'password123'
];

$result = apiRequest('POST', 'auth/login', $loginData);
echo "Status: {$result['status_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Extract session cookie for subsequent requests
$cookies = [];
if (isset($result['response']['data']['user'])) {
  echo "Login successful! User ID: {$result['response']['data']['user']['id']}\n\n";
}

/**
 * Example 3: Get current user info
 */
echo "=== Example 3: Get current user info ===\n";

$result = apiRequest('GET', 'auth/me');
echo "Status: {$result['status_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

/**
 * Example 4: Get messages list
 */
echo "=== Example 4: Get messages list ===\n";

$result = apiRequest('GET', 'messages?page=1&per_page=5');
echo "Status: {$result['status_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

/**
 * Example 5: Create a new message
 */
echo "=== Example 5: Create a new message ===\n";

$messageData = [
  'message' => 'Hello from the API! This is my first message.'
];

$result = apiRequest('POST', 'messages', $messageData);
echo "Status: {$result['status_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

if (isset($result['response']['data']['id'])) {
  $messageId = $result['response']['data']['id'];
  echo "Message created with ID: {$messageId}\n\n";

  /**
   * Example 6: Get specific message
   */
  echo "=== Example 6: Get specific message ===\n";

  $result = apiRequest('GET', "messages/{$messageId}");
  echo "Status: {$result['status_code']}\n";
  echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

  /**
   * Example 7: Update message
   */
  echo "=== Example 7: Update message ===\n";

  $updateData = [
    'message' => 'Updated message content via API!'
  ];

  $result = apiRequest('PUT', "messages/{$messageId}", $updateData);
  echo "Status: {$result['status_code']}\n";
  echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

  /**
   * Example 8: Delete message
   */
  echo "=== Example 8: Delete message ===\n";

  $result = apiRequest('DELETE', "messages/{$messageId}");
  echo "Status: {$result['status_code']}\n";
  echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";
}

/**
 * Example 9: Logout
 */
echo "=== Example 9: Logout ===\n";

$result = apiRequest('POST', 'auth/logout');
echo "Status: {$result['status_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

echo "=== API Usage Example Complete ===\n";

/**
 * Error Handling Examples
 */
echo "\n=== Error Handling Examples ===\n";

// Example: Try to access protected endpoint without authentication
echo "Trying to create message without authentication:\n";
$result = apiRequest('POST', 'messages', ['message' => 'This should fail']);
echo "Status: {$result['status_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Example: Invalid login
echo "Trying to login with invalid credentials:\n";
$result = apiRequest('POST', 'auth/login', [
  'email' => 'invalid@example.com',
  'password' => 'wrongpassword'
]);
echo "Status: {$result['status_code']}\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Example: Rate limiting
echo "Testing rate limiting (creating too many messages quickly):\n";
for ($i = 0; $i < 5; $i++) {
  $result = apiRequest('POST', 'messages', ['message' => "Rate limit test message {$i}"]);
  echo "Request {$i}: Status {$result['status_code']}\n";
  if ($result['status_code'] == 429) {
    echo "Rate limit hit!\n";
    break;
  }
}
