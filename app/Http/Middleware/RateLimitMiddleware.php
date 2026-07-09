<?php

namespace App\Http\Middleware;

use App\Services\InMemoryRateLimiter;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(private readonly InMemoryRateLimiter $rateLimiter)
    {
    }

    /**
     * Handle an incoming request before it reaches the API route.
     *
     * The request is evaluated against:
     * 1. Client/organization rate limits.
     * 2. Endpoint-specific rate limits.
     *
     * If either limit is exceeded, a 429 response is returned immediately.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve the client making the request.
        $orgId = $this->resolveOrgId($request);
        $clientTier = $this->resolveClientTier($request);

        // Apply the client-wide rate limit.
        $clientLimit = config("rate_limits.client_tiers.{$clientTier}");

        $clientResult = $this->rateLimiter->hit(
            key: "client:{$orgId}",
            limit: $clientLimit['limit'],
            windowSeconds: $clientLimit['window_seconds']
        );

        if (! $clientResult['allowed']) {
            return $this->rateLimitExceededResponse('client', $clientResult);
        }

        // Apply endpoint-specific rate limits.
        $method = $request->method();
        $endpointLimit = config("rate_limits.endpoint_limits.{$method}");

        if ($endpointLimit !== null) {
            $endpointResult = $this->rateLimiter->hit(
                key: "endpoint:{$orgId}:{$method}:{$request->path()}",
                limit: $endpointLimit['limit'],
                windowSeconds: $endpointLimit['window_seconds']
            );

            if (! $endpointResult['allowed']) {
                return $this->rateLimitExceededResponse('endpoint', $endpointResult);
            }
        }

        // Continue to the next middleware or route handler.
        return $next($request);
    }

    /**
     * Resolve the organization identifier from the request.
     *
     * Falls back to the configured default if the header is missing.
     */
    private function resolveOrgId(Request $request): string
    {
        $headerName = config('rate_limits.headers.org_id');

        return $request->header(
            $headerName,
            config('rate_limits.default_org_id')
        );
    }

    /**
     * Resolve the client tier from the request.
     *
     * If an unknown tier is provided, the configured default tier is used.
     */
    private function resolveClientTier(Request $request): string
    {
        $headerName = config('rate_limits.headers.org_tier');

        $tier = $request->header(
            $headerName,
            config('rate_limits.default_client_tier')
        );

        if (! array_key_exists($tier, config('rate_limits.client_tiers'))) {
            return config('rate_limits.default_client_tier');
        }

        return $tier;
    }

    /**
     * Build a standardized HTTP 429 response.
     *
     * Includes Retry-After so clients know when they may retry.
     */
    private function rateLimitExceededResponse(string $limitType, array $result): JsonResponse
    {
        return response()
            ->json([
                'error' => 'rate_limit_exceeded',
                'limit_type' => $limitType,
                'message' => ucfirst($limitType) . ' rate limit exceeded.',
                'limit' => $result['limit'],
                'retry_after' => $result['retry_after'],
                'reset_at' => $result['reset_at'],
            ], 429)
            ->header('Retry-After', $result['retry_after']);
    }
}