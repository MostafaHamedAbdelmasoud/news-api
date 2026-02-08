<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Connection
    |--------------------------------------------------------------------------
    */

    'enabled' => env('ELASTICSEARCH_ENABLED', true),

    'hosts' => [
        [
            'host' => env('ELASTICSEARCH_HOST', 'localhost'),
            'port' => env('ELASTICSEARCH_PORT', 9200),
            'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),
            'user' => env('ELASTICSEARCH_USER'),
            'pass' => env('ELASTICSEARCH_PASSWORD'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Index Settings
    |--------------------------------------------------------------------------
    */

    'indices' => [
        'articles' => [
            'name' => env('ELASTICSEARCH_ARTICLES_INDEX', 'news_articles'),
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ],
            'mappings' => [
                'properties' => [
                    'title' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                        ],
                    ],
                    'content' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                    ],
                    'summary' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                    ],
                    'source_id' => [
                        'type' => 'integer',
                    ],
                    'category_id' => [
                        'type' => 'integer',
                    ],
                    'author_id' => [
                        'type' => 'integer',
                    ],
                    'published_at' => [
                        'type' => 'date',
                    ],
                    'url' => [
                        'type' => 'keyword',
                    ],
                    'image_url' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    */

    'search' => [
        'default_limit' => 15,
        'max_limit' => 100,
        'highlight' => [
            'pre_tags' => ['<mark>'],
            'post_tags' => ['</mark>'],
            'fields' => [
                'title' => new stdClass,
                'content' => new stdClass,
            ],
        ],
    ],

];
