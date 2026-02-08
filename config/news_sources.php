<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Fetch Settings
    |--------------------------------------------------------------------------
    */

    'default_timeout' => env('NEWS_API_TIMEOUT', 30),
    'default_cache_ttl' => env('NEWS_CACHE_TTL', 3600),
    'max_articles_per_fetch' => env('NEWS_MAX_ARTICLES', 100),

    /*
    |--------------------------------------------------------------------------
    | News Sources Configuration
    |--------------------------------------------------------------------------
    */

    'sources' => [

        'newsapi' => [
            'name' => 'NewsAPI.org',
            'enabled' => env('NEWSAPI_ENABLED', true),
            'api_key' => env('NEWSAPI_KEY'),
            'base_url' => env('NEWSAPI_BASE_URL', 'https://newsapi.org/v2'),
            'timeout' => env('NEWSAPI_TIMEOUT', 30),
            'endpoints' => [
                'everything' => '/everything',
                'top_headlines' => '/top-headlines',
            ],
        ],

        'guardian' => [
            'name' => 'The Guardian',
            'enabled' => env('GUARDIAN_ENABLED', true),
            'api_key' => env('GUARDIAN_KEY'),
            'base_url' => env('GUARDIAN_BASE_URL', 'https://content.guardianapis.com'),
            'timeout' => env('GUARDIAN_TIMEOUT', 30),
            'endpoints' => [
                'search' => '/search',
                'content' => '/content',
            ],
        ],

        'nytimes' => [
            'name' => 'New York Times',
            'enabled' => env('NYTIMES_ENABLED', true),
            'api_key' => env('NYTIMES_KEY'),
            'base_url' => env('NYTIMES_BASE_URL', 'https://api.nytimes.com/svc'),
            'timeout' => env('NYTIMES_TIMEOUT', 30),
            'endpoints' => [
                'article_search' => '/search/v2/articlesearch.json',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Category Mapping
    |--------------------------------------------------------------------------
    |
    | Maps external source categories to internal category slugs.
    |
    */

    'category_mapping' => [
        'newsapi' => [
            'business' => 'business',
            'entertainment' => 'entertainment',
            'general' => 'general',
            'health' => 'health',
            'science' => 'science',
            'sports' => 'sports',
            'technology' => 'technology',
        ],
        'guardian' => [
            'world' => 'world',
            'uk-news' => 'uk',
            'politics' => 'politics',
            'sport' => 'sports',
            'football' => 'sports',
            'culture' => 'entertainment',
            'business' => 'business',
            'technology' => 'technology',
            'science' => 'science',
            'environment' => 'environment',
            'money' => 'business',
            'education' => 'education',
        ],
        'nytimes' => [
            'world' => 'world',
            'us' => 'us',
            'politics' => 'politics',
            'business' => 'business',
            'technology' => 'technology',
            'science' => 'science',
            'health' => 'health',
            'sports' => 'sports',
            'arts' => 'entertainment',
            'fashion' => 'lifestyle',
            'food' => 'lifestyle',
            'travel' => 'travel',
        ],
    ],

];
