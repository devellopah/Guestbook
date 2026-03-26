# Guestbook API Documentation

This document describes the RESTful API endpoints for the Guestbook application.

## Base URL

```
https://your-domain.com/api/v1/
```

## Authentication

Most endpoints require authentication. Use the login endpoint to obtain a session.

## Response Format

All API responses follow this format:

```json
{
  "success": true|false,
  "data": { /* response data */ },
  "message": "Optional message",
  "meta": {
    "timestamp": "2024-01-01T12:00:00Z",
    "version": "v1"
  }
}
```

## Error Response Format

```json
{
  "success": false,
  "error": {
    "message": "Error description",
    "code": 400,
    "details": {}
  },
  "meta": {
    "timestamp": "2024-01-01T12:00:00Z",
    "version": "v1"
  }
}
```

## Endpoints

### Authentication

#### POST /api/v1/auth/login
Login a user and start a session.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "role": 1
    },
    "message": "Login successful"
  }
}
```

#### POST /api/v1/auth/logout
Logout the current user and end the session.

**Response:**
```json
{
  "success": true,
  "data": [],
  "message": "Logout successful"
}
```

#### POST /api/v1/auth/register
Register a new user.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "message": "Registration successful"
  }
}
```

#### GET /api/v1/auth/me
Get information about the current authenticated user.

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "role": 1
    }
  }
}
```

### Messages

#### GET /api/v1/messages
Get a list of messages with pagination.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 10, max: 100)

**Response:**
```json
{
  "success": true,
  "data": {
    "messages": [
      {
        "id": 1,
        "message": "Hello World!",
        "user_id": 1,
        "status": 1,
        "created_at": "2024-01-01 12:00:00",
        "user": {
          "id": 1,
          "name": "Jane Doe"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 25,
      "total_pages": 3,
      "has_next": true,
      "has_prev": false
    }
  }
}
```

#### POST /api/v1/messages
Create a new message.

**Authentication Required**

**Request Body:**
```json
{
  "message": "This is my new message"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "message": "This is my new message",
    "user_id": 1,
    "status": 1,
    "created_at": "2024-01-01 13:00:00"
  },
  "message": "Message created successfully"
}
```

#### GET /api/v1/messages/{id}
Get a specific message by ID.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "message": "Hello World!",
    "user_id": 1,
    "status": 1,
    "created_at": "2024-01-01 12:00:00",
    "user": {
      "id": 1,
      "name": "Jane Doe"
    }
  }
}
```

#### PUT /api/v1/messages/{id}
Update a specific message.

**Authentication Required**

**Request Body:**
```json
{
  "message": "Updated message content"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "message": "Updated message content",
    "user_id": 1,
    "status": 1,
    "created_at": "2024-01-01 12:00:00"
  },
  "message": "Message updated successfully"
}
```

#### DELETE /api/v1/messages/{id}
Delete a specific message.

**Authentication Required**

**Response:**
```json
{
  "success": true,
  "data": [],
  "message": "Message deleted successfully"
}
```

#### PATCH /api/v1/messages/{id}/status
Toggle the status of a message (active/inactive).

**Authentication Required (Admin only)**

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": 0
  },
  "message": "Message status updated successfully"
}
```

## HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `429` - Too Many Requests
- `500` - Internal Server Error

## Rate Limiting

- Message creation: 3 messages per 60 seconds per user
- Login attempts: Protected by session-based rate limiting

## CORS

The API supports Cross-Origin Resource Sharing (CORS) and allows requests from any origin.

## Examples

### JavaScript Fetch Example

```javascript
// Login
fetch('/api/v1/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password123'
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Login successful');
  }
});

// Get messages
fetch('/api/v1/messages?page=1&per_page=10')
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Messages:', data.data.messages);
  }
});
```

### cURL Example

```bash
# Get messages
curl -X GET "https://your-domain.com/api/v1/messages?page=1&per_page=10"

# Login
curl -X POST "https://your-domain.com/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'