<?php

return [

    /*
    * Client Identification
    *
    * Each client organization is identified by request headers for this
    * proof-of-concept. In production, this would usually come from an API key,
    * token, or authenticated tenant context.
    */

    'headers' => [
        'org_id' => 'X-Org-ID',
        'org_tier' => 'X-Org-Tier',
    ],

    /*
    * Client Tier Limits
    *
    * These limits apply across all API requests for an organization.
    */

    'client_tiers' => [
        'standard' => [
            'limit' => 5, // Demo value. Production example: 100.
            'window_seconds' => 60,
        ],

        'premium' => [
            'limit' => 10, // Demo value. Production example: 500.
            'window_seconds' => 60,
        ],
    ],

    /*
    * Endpoint Limits
    *
    * Write operations are intentionally stricter than read operations.
    */

    'endpoint_limits' => [
        'GET' => [
            'limit' => 3, // Demo value. Production example: 100.
            'window_seconds' => 60,
        ],

        'POST' => [
            'limit' => 30,
            'window_seconds' => 60,
        ],

        'PUT' => [
            'limit' => 30,
            'window_seconds' => 60,
        ],

        'DELETE' => [
            'limit' => 20,
            'window_seconds' => 60,
        ],
    ],

    /*
    * Defaults
    */

    'default_org_id' => 'anonymous',
    'default_client_tier' => 'standard',
];