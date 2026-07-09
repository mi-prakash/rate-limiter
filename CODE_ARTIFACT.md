# API Rate Limiter Proof-of-Concept

## Project Overview

This project implements a configurable API rate-limiting middleware in Laravel 13 as a proof-of-concept for enforcing request limits before API requests reach application logic.

The middleware sits between incoming API requests and the application routes, enforcing configurable limits before requests reach business logic. The implementation demonstrates configurable organization-level and endpoint-level rate limiting while following Laravel's middleware architecture.

For this proof-of-concept, request counters are stored in local shared memory using PHP System V Shared Memory (`sysvshm`) with semaphore synchronization (`sysvsem`). This satisfies the assignment requirement of using in-memory storage without Redis, databases, or external cache services.

---

## Implemented Features

- Custom Laravel middleware
- Configurable rate limits through `config/rate_limits.php`
- Per-organization rate limiting
- Organization tier support (Standard / Premium)
- Per-endpoint rate limiting (GET, POST, PUT, DELETE)
- Fixed Window rate limiting algorithm
- HTTP 429 responses when limits are exceeded
- Shared-memory counter storage (`sysvshm`)
- Semaphore locking (`sysvsem`) for safe concurrent updates
- Automatic cleanup of expired rate-limit counters
- Sample healthcare API endpoints for demonstration

---

## Rate Limiting Algorithm

This implementation uses a **Fixed Window Counter** algorithm.

Each request is evaluated within a configurable time window. Separate counters are maintained for both the client organization and the requested endpoint. If either counter reaches its configured threshold, the middleware immediately returns an **HTTP 429 (Too Many Requests)** response along with a `retry_after` value indicating when the client may retry the request.

The algorithm was selected because it is simple, predictable, easy to configure, and appropriate for a proof-of-concept while still demonstrating the core rate-limiting behavior.

---

## Project Structure

```text
app/
├── Http/
│   └── Middleware/
│       └── RateLimitMiddleware.php
│
└── Services/
    └── InMemoryRateLimiter.php

bootstrap/
└── app.php

config/
└── rate_limits.php

routes/
└── api.php

```

---

## Running the Project

Clear Laravel caches:

```bash
php artisan optimize:clear
```

Start the local development server:

```bash
php artisan serve
```

The demo API will be available at:

```
http://127.0.0.1:8000/api
```

---

## Demo Configuration

The current configuration uses reduced limits to simplify testing during the demonstrat

| Organization Tier | Limit | Window |
|-------------------|------:|-------:|
| Standard | 5 requests | 60 seconds |
| Premium | 10 requests | 60 seconds |

| Endpoint | Limit | Window |
|----------|------:|-------:|
| GET | 3 requests | 60 seconds |
| POST | 30 requests | 60 seconds |
| PUT | 30 requests | 60 seconds |
| DELETE | 20 requests | 60 seconds |

---

## Example Request

### Endpoint

```
GET http://127.0.0.1:8000/api/patients

Headers:
X-Org-ID: org_123
X-Org-Tier: standard
Accept: application/json
```

---

## Successful Response

HTTP 200

```json
{
    "message": "Patient list retrieved successfully"
}
```

---

## Rate Limited Response

HTTP 429

```json
{
    "error": "rate_limit_exceeded",
    "limit_type": "endpoint",
    "message": "Endpoint rate limit exceeded.",
    "limit": 3,
    "retry_after": 53,
    "reset_at": 1783444780
}
```

---

## Demo Verification

Using the default demo configuration:

1. Send four consecutive GET requests to: `GET /patients`

2. The first three requests return **HTTP 200**.

3. The fourth request returns **HTTP 429 Too Many Requests**.

4. The response includes the `retry_after` field indicating how many seconds remain before the current rate-limit window resets.

5. Wait for the specified number of seconds and send the request again. It should return HTTP 200 OK.

---

## Notes

This implementation demonstrates a configurable middleware-based rate limiter using local shared-memory storage. Detailed architecture decisions, trade-offs, failure modes, and production considerations are documented in the accompanying README.