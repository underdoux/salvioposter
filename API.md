# API Documentation

## Authentication

All API endpoints require OAuth2 authentication via Google. Include the access token in the Authorization header:
```
Authorization: Bearer {access_token}
```

## Endpoints

### Authentication

#### GET /auth/google
Redirects to Google OAuth consent screen.

#### GET /auth/google/callback
Handles OAuth callback from Google.

### Posts

#### GET /api/posts
List all posts.

Parameters:
- `page` (optional): Page number for pagination
- `status` (optional): Filter by status (draft, published)
- `sort` (optional): Sort by field (created_at, updated_at)

Response:
```json
{
    "data": [
        {
            "id": 1,
            "title": "Post Title",
            "content": "Post content...",
            "status": "published",
            "created_at": "2024-03-21T10:00:00Z",
            "updated_at": "2024-03-21T10:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 10,
        "per_page": 15
    }
}
```

#### POST /api/posts
Create a new post.

Request:
```json
{
    "title": "Post Title",
    "content": "Post content...",
    "status": "draft"
}
```

#### GET /api/posts/{id}
Get a specific post.

Response:
```json
{
    "id": 1,
    "title": "Post Title",
    "content": "Post content...",
    "status": "published",
    "created_at": "2024-03-21T10:00:00Z",
    "updated_at": "2024-03-21T10:00:00Z"
}
```

#### PUT /api/posts/{id}
Update a post.

Request:
```json
{
    "title": "Updated Title",
    "content": "Updated content...",
    "status": "published"
}
```

#### DELETE /api/posts/{id}
Delete a post.

### Scheduling

#### POST /api/posts/{id}/schedule
Schedule a post for publication.

Request:
```json
{
    "scheduled_at": "2024-03-22T15:00:00Z"
}
```

#### GET /api/scheduled-posts
List scheduled posts.

Response:
```json
{
    "data": [
        {
            "id": 1,
            "post_id": 1,
            "scheduled_at": "2024-03-22T15:00:00Z",
            "status": "pending"
        }
    ]
}
```

### Analytics

#### GET /api/analytics/posts/{id}
Get analytics for a specific post.

Response:
```json
{
    "views": 1000,
    "likes": 50,
    "comments": 25,
    "shares": 10,
    "engagement_rate": 8.5
}
```

#### GET /api/analytics/export
Export analytics data as CSV.

Parameters:
- `start_date` (optional): Start date for data range
- `end_date` (optional): End date for data range
- `format` (optional): Export format (csv, json)

### Notifications

#### GET /api/notifications
List notifications.

Response:
```json
{
    "data": [
        {
            "id": 1,
            "type": "post_published",
            "message": "Your post has been published",
            "read": false,
            "created_at": "2024-03-21T10:00:00Z"
        }
    ]
}
```

#### POST /api/notifications/{id}/read
Mark notification as read.

#### DELETE /api/notifications
Clear all notifications.

### Content Generation

#### POST /api/generate/title
Generate post title.

Request:
```json
{
    "keywords": ["technology", "AI", "future"]
}
```

Response:
```json
{
    "title": "The Future of AI Technology: What to Expect in 2024"
}
```

#### POST /api/generate/content
Generate post content.

Request:
```json
{
    "title": "The Future of AI Technology",
    "keywords": ["AI", "machine learning", "predictions"]
}
```

Response:
```json
{
    "content": "Generated content..."
}
```

## Error Handling

All endpoints return appropriate HTTP status codes:

- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

Error Response Format:
```json
{
    "error": {
        "code": "validation_error",
        "message": "The given data was invalid",
        "details": {
            "title": ["The title field is required"]
        }
    }
}
```

## Rate Limiting

API requests are limited to:
- 60 requests per minute for authenticated users
- 30 requests per minute for unauthenticated users

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1616876400
```

## Webhooks

Configure webhooks to receive notifications for events:

### Available Events
- post.published
- post.scheduled
- analytics.updated

Webhook payload format:
```json
{
    "event": "post.published",
    "data": {
        "post_id": 1,
        "title": "Post Title",
        "status": "published",
        "timestamp": "2024-03-21T10:00:00Z"
    }
}
