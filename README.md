# API Rate Limiter

A proof-of-concept API rate-limiting middleware built with **Laravel 13**.

The middleware enforces configurable per-client and per-endpoint rate limits before requests reach application logic.

---

## Features

- Laravel 13 middleware implementation
- Configurable rate limits
- Per-organization rate limiting
- Per-endpoint rate limiting
- Fixed Window rate-limiting algorithm
- HTTP 429 responses with `Retry-After`
- In-memory request counters using PHP System V Shared Memory (`sysvshm`)
- Semaphore synchronization using `sysvsem`
- Sample healthcare API endpoints

---

## Requirements

- PHP 8.5+
- Composer
- Laravel 13
- PHP extensions:
  - `sysvshm`
  - `sysvsem`

---

## Installation

Clone the repository.

```bash
git clone https://github.com/mi-prakash/rate-limiter.git
```

Go to the project directory.

```bash
cd rate-limiter
```

Install the project dependencies.

```bash
composer install
```

Create the environment file.

```bash
cp .env.example .env
```

Generate the Laravel application key.

```bash
php artisan key:generate
```

Clear Laravel caches.

```bash
php artisan optimize:clear
```

Start the development server.

```bash
php artisan serve
```

The API will be available at:

```
http://127.0.0.1:8000/api
```

---

## Demo Configuration

Current demo limits:

| Tier | Limit |
|------|------:|
| Standard | 5 requests / 60 seconds |
| Premium | 10 requests / 60 seconds |

| Endpoint | Limit |
|----------|------:|
| GET | 3 requests / 60 seconds |
| POST | 30 requests / 60 seconds |
| PUT | 30 requests / 60 seconds |
| DELETE | 20 requests / 60 seconds |

---

## Example Request

```
GET http://127.0.0.1:8000/api/patients
```

Headers:

```
X-Org-ID: org_123
X-Org-Tier: standard
Accept: application/json
```

---

## Expected Behaviour

- Requests within the configured limit return **HTTP 200**.
- Requests exceeding the configured limit return **HTTP 429 Too Many Requests**.
- The response includes a `retry_after` value indicating when the client can retry.
- After the current time window expires, requests are accepted again.

---

## Project Structure

```
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

## Notes

The implementation intentionally uses local shared memory and documents its production trade-offs in the accompanying assessment documentation.