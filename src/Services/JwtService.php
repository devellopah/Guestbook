<?php

namespace Services;

use Core\Request;
use Core\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;

class JwtService extends BaseService
{
  private string $secretKey;
  private string $algorithm;
  private int $accessTokenTtl;
  private int $refreshTokenTtl;

  public function __construct()
  {
    parent::__construct();
    $this->secretKey = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production';
    $this->algorithm = $_ENV['JWT_ALGORITHM'] ?? 'HS256';
    $this->accessTokenTtl = (int)($_ENV['JWT_ACCESS_TTL'] ?? 3600); // 1 hour
    $this->refreshTokenTtl = (int)($_ENV['JWT_REFRESH_TTL'] ?? 86400); // 24 hours
  }

  /**
   * Generate access token
   */
  public function generateAccessToken(array $payload): string
  {
    $tokenPayload = array_merge($payload, [
      'iat' => time(), // Issued at
      'exp' => time() + $this->accessTokenTtl, // Expiration time
      'nbf' => time(), // Not before
      'iss' => $_ENV['APP_URL'] ?? 'http://localhost', // Issuer
      'aud' => $_ENV['APP_URL'] ?? 'http://localhost' // Audience
    ]);

    try {
      $token = JWT::encode($tokenPayload, $this->secretKey, $this->algorithm);
      $this->log('Access token generated', ['user_id' => $payload['user_id'] ?? null]);
      return $token;
    } catch (\Exception $e) {
      $this->log('Access token generation failed', ['error' => $e->getMessage()]);
      throw new \Exception('Failed to generate access token');
    }
  }

  /**
   * Generate refresh token
   */
  public function generateRefreshToken(array $payload): string
  {
    $tokenPayload = array_merge($payload, [
      'iat' => time(), // Issued at
      'exp' => time() + $this->refreshTokenTtl, // Expiration time
      'nbf' => time(), // Not before
      'iss' => $_ENV['APP_URL'] ?? 'http://localhost', // Issuer
      'aud' => $_ENV['APP_URL'] ?? 'http://localhost' // Audience
    ]);

    try {
      $token = JWT::encode($tokenPayload, $this->secretKey, $this->algorithm);
      $this->log('Refresh token generated', ['user_id' => $payload['user_id'] ?? null]);
      return $token;
    } catch (\Exception $e) {
      $this->log('Refresh token generation failed', ['error' => $e->getMessage()]);
      throw new \Exception('Failed to generate refresh token');
    }
  }

  /**
   * Generate token pair (access + refresh)
   */
  public function generateTokenPair(array $payload): array
  {
    return [
      'access_token' => $this->generateAccessToken($payload),
      'refresh_token' => $this->generateRefreshToken($payload),
      'expires_in' => $this->accessTokenTtl,
      'token_type' => 'Bearer'
    ];
  }

  /**
   * Validate and decode JWT token
   */
  public function validateToken(string $token, bool $isAccessToken = true): ?array
  {
    try {
      $key = new Key($this->secretKey, $this->algorithm);
      $payload = JWT::decode($token, $key);

      // Check if token is access token
      if ($isAccessToken) {
        $this->validateAccessTokenPayload($payload);
      }

      $this->log('Token validated successfully', ['user_id' => $payload->user_id ?? null]);
      return (array) $payload;
    } catch (ExpiredException $e) {
      $this->log('Token expired', ['error' => $e->getMessage()]);
      return null;
    } catch (BeforeValidException $e) {
      $this->log('Token not valid yet', ['error' => $e->getMessage()]);
      return null;
    } catch (SignatureInvalidException $e) {
      $this->log('Token signature invalid', ['error' => $e->getMessage()]);
      return null;
    } catch (\Exception $e) {
      $this->log('Token validation failed', ['error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * Validate access token payload
   */
  private function validateAccessTokenPayload($payload): void
  {
    if (!isset($payload->user_id) || !isset($payload->email) || !isset($payload->role)) {
      throw new \Exception('Invalid token payload');
    }

    if (time() > $payload->exp) {
      throw new ExpiredException('Token has expired');
    }

    if (time() < $payload->nbf) {
      throw new BeforeValidException('Token not valid yet');
    }
  }

  /**
   * Refresh access token using refresh token
   */
  public function refreshAccessToken(string $refreshToken): ?array
  {
    $payload = $this->validateToken($refreshToken, false);

    if (!$payload) {
      return null;
    }

    // Check if refresh token is still valid
    if (time() > $payload['exp']) {
      $this->log('Refresh token expired', ['user_id' => $payload['user_id'] ?? null]);
      return null;
    }

    // Generate new token pair
    return $this->generateTokenPair([
      'user_id' => $payload['user_id'],
      'email' => $payload['email'],
      'role' => $payload['role']
    ]);
  }

  /**
   * Get token metadata
   */
  public function getTokenMetadata(string $token): array
  {
    try {
      $key = new Key($this->secretKey, $this->algorithm);
      $payload = JWT::decode($token, $key);

      return [
        'user_id' => $payload->user_id ?? null,
        'email' => $payload->email ?? null,
        'role' => $payload->role ?? null,
        'iat' => $payload->iat ?? null,
        'exp' => $payload->exp ?? null,
        'nbf' => $payload->nbf ?? null,
        'iss' => $payload->iss ?? null,
        'aud' => $payload->aud ?? null,
        'is_expired' => time() > $payload->exp,
        'expires_in' => max(0, $payload->exp - time())
      ];
    } catch (\Exception $e) {
      return [
        'error' => $e->getMessage(),
        'is_valid' => false
      ];
    }
  }

  /**
   * Blacklist token (for logout)
   */
  public function blacklistToken(string $token): bool
  {
    // In production, you would store blacklisted tokens in Redis or database
    // For now, we'll just log it
    $metadata = $this->getTokenMetadata($token);

    if (isset($metadata['user_id'])) {
      $this->log('Token blacklisted', [
        'user_id' => $metadata['user_id'],
        'token_type' => $metadata['is_expired'] ? 'expired' : 'active'
      ]);
      return true;
    }

    return false;
  }

  /**
   * Check if token is valid
   */
  public function isValidToken(string $token, bool $isAccessToken = true): bool
  {
    return $this->validateToken($token, $isAccessToken) !== null;
  }

  /**
   * Get token from request
   */
  public function getTokenFromRequest(Request $request): ?string
  {
    // Try Authorization header
    $authHeader = $request->getHeader('Authorization');
    if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
      return trim(substr($authHeader, 7));
    }

    // Try access token cookie
    $cookies = $request->getCookies();
    if (isset($cookies['access_token'])) {
      return $cookies['access_token'];
    }

    // Try query parameter
    $query = $request->getQuery();
    if (isset($query['token'])) {
      return $query['token'];
    }

    return null;
  }

  /**
   * Get token type
   */
  public function getTokenType(string $token): ?string
  {
    try {
      $key = new Key($this->secretKey, $this->algorithm);
      $payload = JWT::decode($token, $key);

      // Determine token type based on payload
      if (isset($payload->refresh)) {
        return 'refresh';
      }

      return 'access';
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Set token in response
   */
  public function setTokenInResponse(Response $response, string $token, string $type = 'access'): Response
  {
    // Set in Authorization header
    $response->setHeader('Authorization', "Bearer $token");

    // Set in cookie (HTTP-only, secure)
    $secure = $_ENV['APP_ENV'] === 'production';
    $domain = $_ENV['APP_DOMAIN'] ?? '';

    if ($type === 'access') {
      $response->setHeader('Set-Cookie', "access_token=$token; Path=/; HttpOnly; Secure=$secure; SameSite=Strict; Domain=$domain");
    } else {
      $response->setHeader('Set-Cookie', "refresh_token=$token; Path=/; HttpOnly; Secure=$secure; SameSite=Strict; Domain=$domain");
    }

    return $response;
  }
}
