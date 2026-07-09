# API Rate Limiter Design Under Constraints

## Section A: Architecture & Trade-offs

This proof-of-concept implements a custom Laravel middleware that intercepts incoming API requests before they reach application logic. I selected middleware because rate limiting is a cross-cutting concern that should be enforced consistently without modifying every endpoint individually.

The implementation uses a Fixed Window rate-limiting algorithm. This approach was chosen because it is straightforward to implement, easy to understand, and appropriate for a proof-of-concept completed under time constraints. The primary trade-off is that clients may experience burst traffic at window boundaries, but the algorithm remains predictable and easy to maintain.

Configuration is separated into `config/rate_limits.php`, allowing limits to be modified without changing application code. Organization-wide limits are determined from the `X-Org-ID` and `X-Org-Tier` headers, while endpoint limits are configured independently for different HTTP methods.

To satisfy the in-memory requirement, request counters are stored using PHP System V Shared Memory (`sysvshm`). Access is synchronized using `sysvsem` semaphores to avoid concurrent updates corrupting counters. This approach avoids Redis, databases, sessions, or external cache services while providing a working demonstration across multiple HTTP requests.

The middleware first validates the organization-wide limit and then evaluates the endpoint-specific limit. If either threshold is exceeded, it immediately returns HTTP 429 together with a structured JSON response and a `Retry-After` header.

With additional time, I would add automated tests, stronger error handling, route-pattern normalization, configurable algorithms (sliding window/token bucket), and a Redis-backed storage provider while preserving the middleware interface.

---

## Section B: Production Readiness Plan

### B1 – Failure Modes & Scaling Plan

This implementation stores counters in local shared memory. A process or machine restart clears all counters, giving clients fresh quotas. That is acceptable for this proof-of-concept but would not be ideal for production environments requiring strict enforcement.

The solution is also limited to a single server. In a horizontally scaled deployment, each server would maintain its own counters, allowing clients to exceed intended global limits. A production implementation would replace the storage layer with Redis using atomic increment operations and key expiration while keeping the middleware unchanged.

Memory usage increases as the number of organizations, endpoints, and active windows grows. Expired counters are removed during requests, but production monitoring should track memory consumption, active counter count, request latency, HTTP 429 frequency, and storage errors.

### B2 – Required Reasoning Question

One example of an AI-generated solution that looked reasonable but was actually incorrect was using a simple static PHP array to store the request counters. At first glance the logic seemed fine because the counter increased on every request and checked the configured limit.

However, after testing it with repeated requests in Postman, I noticed that the counter was not being preserved between requests in my Laravel environment. Every request started with a fresh counter, so the rate limiter never actually blocked requests.

Instead of assuming the generated code was correct, I verified the behavior by testing the API rather than just reviewing the code. After confirming the issue, I looked for another approach that still met the "in-memory only" requirement and eventually chose PHP System V Shared Memory (`sysvshm`) with semaphores, which provided a working proof-of-concept.

---

## Section C: AI Usage Log

### Interaction 1 – Static PHP Array

AI initially suggested storing the request counters in a static PHP array. The implementation looked correct, so I built and tested it. However, repeated requests from Postman always returned HTTP 200 because the counters were not preserved across requests in my Laravel environment. Since the implementation did not produce a working proof-of-concept, I decided not to use it.

---

### Interaction 2 – Laravel Array Cache

The next suggestion was to use Laravel's built-in `array` cache driver as the in-memory storage. After updating the implementation and testing it, I found that it still did not maintain the request counters as expected for this demo. Based on those results, I rejected this approach as well.

---

### Interaction 3 – Shared Memory

After reviewing the available PHP extensions, I suggested using shared memory instead of a PHP array or Laravel cache. We confirmed that `sysvshm` and `sysvsem` were available in my PHP installation and updated the implementation to use shared memory with semaphore locking. This approach satisfied the in-memory requirement and produced a working rate limiter without using Redis, a database, sessions, or external cache services.