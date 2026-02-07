# News Aggregator Backend — Claude Code Prompt

> **Stack**: PHP 8.4 · Laravel 12 · MySQL 8 · Redis · Elasticsearch 8 · Nginx · Docker
> **Sources**: NewsAPI.org, The Guardian, New York Times

---

## 1. Project Setup & Docker

Create a fully Dockerized Laravel 12 project. Everything must work with a single `docker-compose up --build`.

### Docker Services

| Service         | Image / Build        | Ports        | Notes                                          |
|-----------------|----------------------|--------------|-------------------------------------------------|
| `app-1`         | PHP 8.4-FPM (custom) | 9000         | Replica 1 — includes Composer, required PHP exts |
| `app-2`         | PHP 8.4-FPM (custom) | 9001         | Replica 2 — identical to app-1                  |
| `nginx`         | nginx:alpine         | 80 → host    | Load balancer: upstream round-robin to app-1/2  |
| `mysql`         | mysql:8.0            | 3306         | Named volume for persistence                    |
| `redis`         | redis:7-alpine       | 6379         | Used for cache, queues, rate limiting           |
| `elasticsearch` | elasticsearch:8.x    | 9200         | Single-node, xpack security disabled for dev    |
| `queue-worker`  | Same PHP image       | —            | Runs `php artisan queue:work redis`             |
| `scheduler`     | Same PHP image       | —            | Runs `php artisan schedule:work`                |

### Dockerfile (PHP-FPM)

- Base: `php:8.4-fpm`
- Install extensions: `pdo_mysql`, `redis`, `pcntl`, `bcmath`, `zip`, `gd`
- Install Composer
- Set working directory to `/var/www/html`
- Copy codebase, run `composer install --no-dev --optimize-autoloader`
- Set proper permissions on `storage/` and `bootstrap/cache/`

### Nginx Config

```nginx
upstream app_servers {
    server app-1:9000;
    server app-2:9000;
}
```

Route all `/api/*` requests through FastCGI to the upstream. Include proper `try_files`, `fastcgi_params`, and `SCRIPT_FILENAME`.

### docker-compose.yml

- Use `.env` for all credentials
- Health checks on mysql (mysqladmin ping), redis (redis-cli ping), elasticsearch (curl localhost:9200)
- `depends_on` with `condition: service_healthy` so app waits for dependencies
- Named volumes: `mysql_data`, `es_data`, `redis_data`
- Shared `app_code` volume for both app replicas

### Entrypoint Script

On container start:
1. Wait for MySQL to be ready
2. Run `php artisan migrate --force`
3. Run `php artisan config:cache`
4. Run `php artisan route:cache`
5. Start PHP-FPM

Provide a `Makefile` with common commands:
```makefile
setup:     docker-compose up --build -d && docker-compose exec app-1 php artisan migrate --seed
down:      docker-compose down
test:      docker-compose exec app-1 php artisan test
fetch:     docker-compose exec app-1 php artisan news:fetch
logs:      docker-compose logs -f
fresh:     docker-compose exec app-1 php artisan migrate:fresh --seed
es-index:  docker-compose exec app-1 php artisan elasticsearch:reindex
```

---

## 2. Configuration

### `.env` Variables

```env
# News API Keys
NEWS_API_KEY=
NEWS_API_BASE_URL=https://newsapi.org/v2

GUARDIAN_API_KEY=
GUARDIAN_BASE_URL=https://content.guardianapis.com

NYT_API_KEY=
NYT_BASE_URL=https://api.nytimes.com/svc

# Elasticsearch
ELASTICSEARCH_HOST=elasticsearch
ELASTICSEARCH_PORT=9200

# Cache TTL (seconds)
ARTICLES_CACHE_TTL=600
SEARCH_CACHE_TTL=300
SOURCES_CACHE_TTL=3600

# Fetch Schedule (minutes)
NEWS_FETCH_INTERVAL=60

# Queue
QUEUE_CONNECTION=redis
```

### `config/news_sources.php`

```php
return [
    'newsapi' => [
        'key'       => env('NEWS_API_KEY'),
        'base_url'  => env('NEWS_API_BASE_URL', 'https://newsapi.org/v2'),
        'enabled'   => env('NEWS_API_ENABLED', true),
        'timeout'   => 30,
        'retry'     => 3,
        'retry_delay' => 5, // seconds
    ],
    'guardian' => [
        'key'       => env('GUARDIAN_API_KEY'),
        'base_url'  => env('GUARDIAN_BASE_URL', 'https://content.guardianapis.com'),
        'enabled'   => env('GUARDIAN_ENABLED', true),
        'timeout'   => 30,
        'retry'     => 3,
        'retry_delay' => 5,
    ],
    'nytimes' => [
        'key'       => env('NYT_API_KEY'),
        'base_url'  => env('NYT_BASE_URL', 'https://api.nytimes.com/svc'),
        'enabled'   => env('NYT_ENABLED', true),
        'timeout'   => 30,
        'retry'     => 3,
        'retry_delay' => 5,
    ],

    'fetch_interval' => env('NEWS_FETCH_INTERVAL', 60),

    'cache' => [
        'articles_ttl' => env('ARTICLES_CACHE_TTL', 600),
        'search_ttl'   => env('SEARCH_CACHE_TTL', 300),
        'sources_ttl'  => env('SOURCES_CACHE_TTL', 3600),
    ],
];
```

---

## 3. Database Design (Normalized, Indexed)

### Migrations

#### `sources`
| Column       | Type                | Constraints              |
|--------------|---------------------|--------------------------|
| id           | bigIncrements       | PK                       |
| name         | string(100)         | not null                 |
| slug         | string(100)         | unique index             |
| base_url     | string(255)         | nullable                 |
| is_active    | boolean             | default true, index      |
| timestamps   |                     |                          |

**Why index `slug`**: Used in every filter query to match source by name. Unique prevents duplicates.

#### `categories`
| Column       | Type                | Constraints              |
|--------------|---------------------|--------------------------|
| id           | bigIncrements       | PK                       |
| name         | string(100)         | not null                 |
| slug         | string(100)         | unique index             |
| timestamps   |                     |                          |

**Why index `slug`**: Filtered in almost every article listing query.

#### `authors`
| Column       | Type                | Constraints              |
|--------------|---------------------|--------------------------|
| id           | bigIncrements       | PK                       |
| name         | string(255)         | not null                 |
| source_id    | foreignId           | FK → sources, nullable, index |
| timestamps   |                     |                          |

**Index**: Composite `(name, source_id)` unique — same author name from different sources are separate records.

#### `articles`
| Column        | Type               | Constraints                         |
|---------------|--------------------|-------------------------------------|
| id            | bigIncrements      | PK                                  |
| source_id     | foreignId          | FK → sources, index                 |
| category_id   | foreignId          | FK → categories, nullable, index    |
| author_id     | foreignId          | FK → authors, nullable, index       |
| title         | string(500)        | not null                            |
| slug          | string(550)        | index                               |
| content       | text               | nullable                            |
| summary       | text               | nullable                            |
| url           | string(2048)       | unique index (for dedup)            |
| image_url     | string(2048)       | nullable                            |
| published_at  | timestamp          | index                               |
| external_id   | string(255)        | nullable, index (source's own ID)   |
| timestamps    |                    |                                     |

**Indexes & reasons**:
- `url` unique: Prevents duplicate articles across fetches.
- `source_id` index: Filter by source.
- `category_id` index: Filter by category.
- `author_id` index: Filter by author.
- `published_at` index: ORDER BY and date range filtering.
- Composite `(source_id, published_at)`: Covers the most common query "articles from X source sorted by date".
- Composite `(category_id, published_at)`: Covers "articles in X category sorted by date".
- `FULLTEXT(title, content)`: Fallback search when Elasticsearch is unavailable.

**Foreign keys**: All FKs use `constrained()->nullOnDelete()` so deleting a source/category/author nullifies the reference instead of cascading article deletion.

#### `user_preferences`
| Column                | Type          | Constraints              |
|-----------------------|---------------|--------------------------|
| id                    | bigIncrements | PK                       |
| user_id               | foreignId     | FK → users, unique       |
| preferred_sources     | json          | default '[]'             |
| preferred_categories  | json          | default '[]'             |
| preferred_authors     | json          | default '[]'             |
| timestamps            |               |                          |

#### `users` (standard Laravel users table)
Basic auth: id, name, email, password, timestamps.

#### `api_fetch_logs`
| Column       | Type          | Constraints              |
|--------------|---------------|--------------------------|
| id           | bigIncrements | PK                       |
| source       | string(50)    | index                    |
| endpoint     | string(500)   |                          |
| status       | enum          | success, failed, partial |
| status_code  | integer       | nullable                 |
| error_message| text          | nullable                 |
| response_time| integer       | milliseconds             |
| articles_count| integer      | default 0                |
| fetched_at   | timestamp     | index                    |
| timestamps   |               |                          |

---

## 4. Folder Structure

```
app/
├── Console/
│   └── Commands/
│       ├── FetchNewsCommand.php           # php artisan news:fetch
│       └── ReindexElasticsearchCommand.php # php artisan elasticsearch:reindex
├── Contracts/
│   ├── NewsAdapterInterface.php
│   ├── ArticleRepositoryInterface.php
│   └── SearchServiceInterface.php
├── DTOs/
│   ├── ArticleDTO.php                     # Immutable data object from adapters
│   ├── ArticleFilterDTO.php               # Holds validated filter params
│   └── FetchResultDTO.php                 # Holds fetch results + metadata
├── Enums/
│   ├── FetchStatus.php                    # success, failed, partial
│   └── NewsSource.php                     # newsapi, guardian, nytimes
├── Exceptions/
│   ├── NewsSourceException.php
│   ├── NewsSourceUnavailableException.php
│   └── ElasticsearchException.php
├── Filters/
│   ├── Pipes/
│   │   ├── FilterByKeyword.php
│   │   ├── FilterByDateRange.php
│   │   ├── FilterByCategory.php
│   │   ├── FilterBySource.php
│   │   ├── FilterByAuthor.php
│   │   └── SortArticles.php
│   └── ArticleFilterPipeline.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           ├── ArticleController.php
│   │           ├── SourceController.php
│   │           ├── CategoryController.php
│   │           ├── AuthorController.php
│   │           └── UserPreferenceController.php
│   ├── Requests/
│   │   ├── ArticleIndexRequest.php
│   │   ├── ArticleSearchRequest.php
│   │   └── UserPreferenceRequest.php
│   └── Resources/
│       ├── ArticleResource.php
│       ├── ArticleCollection.php
│       ├── SourceResource.php
│       ├── CategoryResource.php
│       ├── AuthorResource.php
│       └── UserPreferenceResource.php
├── Jobs/
│   ├── FetchArticlesFromSource.php        # Per-source fetch job
│   ├── SyncArticleToElasticsearch.php     # Single article ES sync
│   └── BulkSyncToElasticsearch.php        # Batch ES sync
├── Models/
│   ├── Article.php
│   ├── Source.php
│   ├── Category.php
│   ├── Author.php
│   ├── UserPreference.php
│   ├── ApiFetchLog.php
│   └── Scopes/                            # Eloquent global/local scopes
│       ├── PublishedScope.php
│       └── ActiveSourceScope.php
├── Observers/
│   └── ArticleObserver.php                # Syncs to ES on create/update
├── Providers/
│   ├── NewsServiceProvider.php            # Binds adapters & services
│   └── RepositoryServiceProvider.php
├── Repositories/
│   ├── EloquentArticleRepository.php
│   └── CachedArticleRepository.php        # Decorator: wraps Eloquent with Redis
├── Services/
│   ├── NewsAggregatorService.php          # Orchestrates all adapters
│   ├── ArticleService.php                 # Business logic for articles
│   ├── ElasticsearchService.php           # ES indexing & search
│   ├── CacheService.php                   # Cache management & invalidation
│   └── Adapters/
│       ├── AbstractNewsAdapter.php        # Shared logic: HTTP, retry, logging
│       ├── NewsApiAdapter.php
│       ├── GuardianAdapter.php
│       └── NytimesAdapter.php
└── Logging/
    └── ApiCallLogger.php                  # Dedicated logger for API calls
```

---

## 5. Design Patterns — Implementation Details

### 5.1 Adapter Pattern (Core Pattern)

```php
interface NewsAdapterInterface
{
    public function getSourceName(): string;
    public function fetchLatestArticles(int $page = 1, int $pageSize = 50): FetchResultDTO;
    public function searchArticles(string $query, array $filters = []): FetchResultDTO;
    public function isAvailable(): bool;
}
```

`AbstractNewsAdapter` provides shared logic:
- HTTP client setup with timeout from config
- Retry logic with exponential backoff (configurable retries & delay)
- Response logging to the `news_api` log channel
- Exception wrapping (all errors become `NewsSourceException`)
- Rate limit awareness (sleep between requests if needed)

Each concrete adapter (`NewsApiAdapter`, `GuardianAdapter`, `NytimesAdapter`) only implements:
- Building the request URL/params for that specific API
- Mapping the API's JSON response → `ArticleDTO[]`
- Handling that API's specific pagination format

**Critical**: All three adapters output the same `ArticleDTO` shape. The rest of the system never knows which source the data came from — the adapter is the boundary.

### 5.2 Pipeline Pattern (Filtering)

Use `Illuminate\Pipeline\Pipeline` for article query filtering:

```php
class ArticleFilterPipeline
{
    public function apply(Builder $query, ArticleFilterDTO $filters): Builder
    {
        return app(Pipeline::class)
            ->send(['query' => $query, 'filters' => $filters])
            ->through([
                FilterByKeyword::class,
                FilterByDateRange::class,
                FilterByCategory::class,
                FilterBySource::class,
                FilterByAuthor::class,
                SortArticles::class,
            ])
            ->thenReturn()['query'];
    }
}
```

Each pipe checks if the relevant filter param is present, applies the scope/where, and passes along. This is **open/closed** — add a new filter = add a new pipe class, no existing code changes.

### 5.3 Repository + Decorator (Caching)

```
ArticleRepositoryInterface
    └── EloquentArticleRepository      (database queries)
        └── CachedArticleRepository    (wraps Eloquent, adds Redis cache)
```

`CachedArticleRepository` checks Redis first, falls back to `EloquentArticleRepository`, stores result in Redis with TTL. Cache keys are generated from the filter hash: `articles:list:{md5(serialized_filters)}`.

Cache invalidation: When new articles are stored (after a fetch), bust all `articles:*` cache keys using Redis cache tags.

### 5.4 Observer Pattern (ES Sync)

`ArticleObserver::created()` and `ArticleObserver::updated()` dispatch `SyncArticleToElasticsearch` job. This keeps ES in sync without polluting model/service code.

### 5.5 Strategy Pattern (Source Selection)

`NewsAggregatorService` holds all registered adapters. When fetching, it iterates only enabled adapters. When the frontend says `?source=guardian`, the service picks the right adapter. New source = register a new adapter in the service provider.

### 5.6 DTO Pattern (Type Safety)

```php
final readonly class ArticleDTO
{
    public function __construct(
        public string   $title,
        public ?string  $content,
        public ?string  $summary,
        public string   $url,
        public ?string  $imageUrl,
        public ?string  $authorName,
        public ?string  $categoryName,
        public string   $sourceName,
        public ?string  $externalId,
        public Carbon   $publishedAt,
    ) {}
}
```

No setters. Immutable. Created by adapters, consumed by the storage service.

---

## 6. API Endpoints

All routes prefixed with `/api/v1`. Use `api` middleware + `auth:sanctum` where needed.

### Public Endpoints

| Method | URI                          | Controller Method                     | Description                       |
|--------|------------------------------|---------------------------------------|-----------------------------------|
| GET    | `/api/v1/articles`           | `ArticleController@index`             | List articles (paginated + filters)|
| GET    | `/api/v1/articles/{article}` | `ArticleController@show`              | Single article                    |
| GET    | `/api/v1/articles/search`    | `ArticleController@search`            | Full-text search via ES           |
| GET    | `/api/v1/sources`            | `SourceController@index`              | List all sources                  |
| GET    | `/api/v1/categories`         | `CategoryController@index`            | List all categories               |
| GET    | `/api/v1/authors`            | `AuthorController@index`              | List authors (paginated)          |

### Authenticated Endpoints

| Method | URI                              | Controller Method                      | Description                       |
|--------|----------------------------------|----------------------------------------|-----------------------------------|
| GET    | `/api/v1/user/preferences`       | `UserPreferenceController@show`        | Get user preferences              |
| PUT    | `/api/v1/user/preferences`       | `UserPreferenceController@update`      | Set user preferences              |
| GET    | `/api/v1/user/feed`              | `ArticleController@personalizedFeed`   | Feed based on user preferences    |

### Auth Endpoints (Laravel Sanctum)

| Method | URI                      | Description        |
|--------|--------------------------|--------------------|
| POST   | `/api/v1/auth/register`  | Register           |
| POST   | `/api/v1/auth/login`     | Login → token      |
| POST   | `/api/v1/auth/logout`    | Revoke token       |

### Unified Filter & Search Keys (All Endpoints)

These query params work identically on `/articles`, `/articles/search`, and `/user/feed`:

| Param         | Type    | Example                     | Description                          |
|---------------|---------|-----------------------------|--------------------------------------|
| `keyword`     | string  | `?keyword=climate`          | Search in title/content              |
| `date_from`   | date    | `?date_from=2025-01-01`     | Articles published after this date   |
| `date_to`     | date    | `?date_to=2025-02-01`       | Articles published before this date  |
| `category`    | string  | `?category=technology`      | Filter by category slug              |
| `source`      | string  | `?source=guardian`          | Filter by source slug                |
| `author`      | string  | `?author=john-doe`          | Filter by author name (partial match)|
| `sort_by`     | string  | `?sort_by=published_at`     | Sort field (published_at, title)     |
| `sort_order`  | string  | `?sort_order=desc`          | asc or desc                          |
| `per_page`    | int     | `?per_page=15`              | Pagination size (max 100)            |
| `page`        | int     | `?page=2`                   | Page number                          |

### FormRequest Validation

**`ArticleIndexRequest`**:
```php
public function rules(): array
{
    return [
        'keyword'    => ['sometimes', 'string', 'max:200'],
        'date_from'  => ['sometimes', 'date', 'before_or_equal:date_to'],
        'date_to'    => ['sometimes', 'date', 'after_or_equal:date_from'],
        'category'   => ['sometimes', 'string', 'exists:categories,slug'],
        'source'     => ['sometimes', 'string', 'exists:sources,slug'],
        'author'     => ['sometimes', 'string', 'max:200'],
        'sort_by'    => ['sometimes', 'string', 'in:published_at,title,created_at'],
        'sort_order' => ['sometimes', 'string', 'in:asc,desc'],
        'per_page'   => ['sometimes', 'integer', 'min:1', 'max:100'],
    ];
}
```

**`UserPreferenceRequest`**:
```php
public function rules(): array
{
    return [
        'preferred_sources'    => ['sometimes', 'array'],
        'preferred_sources.*'  => ['string', 'exists:sources,slug'],
        'preferred_categories' => ['sometimes', 'array'],
        'preferred_categories.*' => ['string', 'exists:categories,slug'],
        'preferred_authors'    => ['sometimes', 'array'],
        'preferred_authors.*'  => ['string', 'exists:authors,name'],
    ];
}
```

### API Response Format

All responses use API Resources and follow a consistent envelope:

```json
{
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150
    },
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    }
}
```

Error responses:
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "date_from": ["The date from must be a valid date."]
    }
}
```

---

## 7. Queue & Scheduled Jobs

### Scheduled Fetching (Every 60 Minutes)

In `routes/console.php` or `app/Console/Kernel.php`:

```php
Schedule::command('news:fetch')
    ->everyHour()             // every 60 minutes
    ->withoutOverlapping()    // prevent duplicate runs
    ->onOneServer()           // only one server in LB setup
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/news-fetch.log'));
```

### `news:fetch` Command Logic

```
1. Get all enabled news sources from config
2. For EACH source, dispatch FetchArticlesFromSource job to the queue
   - Jobs run in PARALLEL on the queue (not sequentially)
3. Each job:
   a. Call adapter->fetchLatestArticles()
   b. For each ArticleDTO returned:
      - Check if article URL already exists (upsert by url)
      - Find or create Source, Category, Author records
      - Store Article record (updateOrCreate by url)
   c. Log success/failure to api_fetch_logs table
   d. Log to news_api log channel
   e. On success: dispatch BulkSyncToElasticsearch for new articles
   f. On success: invalidate relevant Redis cache tags
4. After all jobs: log summary
```

### Job Failure Handling

```php
class FetchArticlesFromSource implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60; // seconds between retries
    public int $timeout = 120;

    public function handle(): void { ... }

    public function failed(Throwable $exception): void
    {
        // Log to api_fetch_logs with status=failed
        // Log to news_api channel with full context
        // Optionally: notify via Slack/email if critical
        ApiFetchLog::create([
            'source'        => $this->sourceName,
            'endpoint'      => $this->adapter->getEndpoint(),
            'status'        => FetchStatus::FAILED,
            'error_message' => $exception->getMessage(),
            'fetched_at'    => now(),
        ]);
    }
}
```

### Queue Configuration

```php
// config/queue.php → redis connection
'redis' => [
    'driver'     => 'redis',
    'connection' => 'default',
    'queue'      => 'default',
    'retry_after' => 180,
    'block_for'  => 5,
],
```

Use named queues for priority:
- `high`: Article fetch jobs
- `default`: ES sync jobs
- `low`: Cache warming jobs

Queue worker command in Docker: `php artisan queue:work redis --queue=high,default,low --tries=3 --backoff=60 --timeout=120 --memory=256`

---

## 8. Caching Strategy (Redis)

### Cache Layers

| What                    | Key Pattern                                 | TTL     | Invalidated When              |
|-------------------------|---------------------------------------------|---------|-------------------------------|
| Article list (filtered) | `articles:list:{filter_hash}`               | 10 min  | New articles fetched          |
| Single article          | `articles:show:{id}`                        | 30 min  | Article updated               |
| Search results          | `articles:search:{query_hash}`              | 5 min   | New articles fetched          |
| Sources list            | `sources:all`                               | 60 min  | Source created/updated        |
| Categories list         | `categories:all`                            | 60 min  | Category created/updated      |
| Authors list            | `authors:list:{filter_hash}`                | 30 min  | New author created            |
| User feed               | `user:feed:{user_id}:{filter_hash}`         | 5 min   | Preferences changed / new articles |

### Cache Tags for Group Invalidation

```php
Cache::tags(['articles'])->put($key, $data, $ttl);
// After fetch:
Cache::tags(['articles'])->flush();
```

### CachedArticleRepository Example

```php
public function getFilteredArticles(ArticleFilterDTO $filters): LengthAwarePaginator
{
    $cacheKey = 'articles:list:' . md5(serialize($filters));
    $ttl = config('news_sources.cache.articles_ttl');

    return Cache::tags(['articles'])->remember($cacheKey, $ttl, function () use ($filters) {
        return $this->innerRepository->getFilteredArticles($filters);
    });
}
```

---

## 9. Elasticsearch

### Index Mapping (`articles` index)

```json
{
    "mappings": {
        "properties": {
            "id":            { "type": "integer" },
            "title":         { "type": "text", "analyzer": "standard" },
            "content":       { "type": "text", "analyzer": "standard" },
            "summary":       { "type": "text", "analyzer": "standard" },
            "url":           { "type": "keyword" },
            "source_slug":   { "type": "keyword" },
            "source_name":   { "type": "keyword" },
            "category_slug": { "type": "keyword" },
            "category_name": { "type": "keyword" },
            "author_name":   { "type": "keyword" },
            "published_at":  { "type": "date" },
            "created_at":    { "type": "date" }
        }
    }
}
```

### Search Logic

`ElasticsearchService::search()`:
1. Build ES bool query with `must` (keyword match on title+content), `filter` (source, category, date range, author)
2. Execute search with pagination
3. Return article IDs + scores
4. Hydrate from MySQL (one query: `Article::whereIn('id', $ids)->with(...)`)
5. Order by ES relevance score

### Fallback

If Elasticsearch is down:
1. Catch `ElasticsearchException`
2. Log warning
3. Fall back to MySQL FULLTEXT search on `articles(title, content)`
4. Log that fallback was used
5. Continue serving results (degraded but functional)

### ES Sync

- **Real-time**: `ArticleObserver::created/updated` → dispatches `SyncArticleToElasticsearch` job
- **Bulk reindex**: `php artisan elasticsearch:reindex` command — drops index, recreates, chunks all articles (1000 at a time) and bulk-indexes

---

## 10. Eloquent Best Practices

### Eager Loading (N+1 Prevention)

Every query that returns articles MUST use:
```php
Article::with(['source', 'category', 'author'])
```

Never access `$article->source->name` in a loop without eager loading.

### Scopes

```php
// Article.php

public function scopePublished(Builder $query): Builder
{
    return $query->whereNotNull('published_at')
                 ->where('published_at', '<=', now());
}

public function scopeFromSource(Builder $query, string $sourceSlug): Builder
{
    return $query->whereHas('source', fn ($q) => $q->where('slug', $sourceSlug));
}

public function scopeInCategory(Builder $query, string $categorySlug): Builder
{
    return $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
}

public function scopeInDateRange(Builder $query, ?string $from, ?string $to): Builder
{
    return $query
        ->when($from, fn ($q) => $q->where('published_at', '>=', $from))
        ->when($to, fn ($q) => $q->where('published_at', '<=', $to));
}

public function scopeByAuthor(Builder $query, string $authorName): Builder
{
    return $query->whereHas('author', fn ($q) => $q->where('name', 'LIKE', "%{$authorName}%"));
}
```

### Mass Assignment Protection

All models use `$fillable` arrays. No `$guarded = []`.

### Upsert for Article Storage

```php
Article::updateOrCreate(
    ['url' => $dto->url],   // unique key
    [
        'title'        => $dto->title,
        'content'      => $dto->content,
        // ...
    ]
);
```

---

## 11. Logging

### Channels (`config/logging.php`)

```php
'news_api' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/news_api.log'),
    'level'  => 'debug',
    'days'   => 30,
],
```

### What Gets Logged

| Event                           | Level   | Channel    | Info Logged                                                 |
|---------------------------------|---------|------------|-------------------------------------------------------------|
| API request sent                | info    | news_api   | source, endpoint, params                                    |
| API response received           | info    | news_api   | source, status_code, response_time_ms, articles_count       |
| API request failed              | error   | news_api   | source, endpoint, status_code, error_message, response_body |
| API retry attempt               | warning | news_api   | source, attempt_number, will_retry_in                       |
| All retries exhausted           | critical| news_api   | source, endpoint, total_attempts, final_error               |
| ES sync failed                  | error   | news_api   | article_id, error                                           |
| ES fallback activated           | warning | news_api   | search_query, reason                                        |
| Cache miss                      | debug   | news_api   | cache_key                                                   |
| Cache invalidated               | info    | news_api   | tags_flushed                                                |
| Duplicate article skipped       | debug   | news_api   | url, source                                                 |
| Fetch cycle complete            | info    | news_api   | total_sources, total_articles, total_new, total_updated, duration |

### Database Logging

Every external API call also creates an `ApiFetchLog` record for monitoring and debugging via the admin/API.

---

## 12. Error Handling & Edge Cases

### Global Exception Handler

In `bootstrap/app.php` (Laravel 12):

```php
->withExceptions(function (Exceptions $exceptions) {
    // Convert all exceptions to JSON for API routes
    $exceptions->render(function (Throwable $e, Request $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => $e->getMessage(),
                'error'   => config('app.debug') ? $e->getTrace() : null,
            ], $this->getStatusCode($e));
        }
    });
})
```

### Edge Cases to Handle

| Scenario                                   | How to Handle                                                                |
|--------------------------------------------|------------------------------------------------------------------------------|
| API key is missing/invalid                 | Skip source, log error, continue with other sources                          |
| API returns 429 (rate limited)             | Respect Retry-After header, delay retry, log warning                         |
| API returns 500/503                        | Retry with backoff (3 attempts), then mark as failed                         |
| API timeout                                | Configurable timeout (30s), retry, then fail gracefully                      |
| API returns empty results                  | Log info, don't treat as error, skip storage step                            |
| API response format changed                | Catch mapping exceptions, log error with raw response, skip source           |
| Duplicate article (same URL)               | Upsert — update existing record, don't create duplicate                      |
| Article with missing required fields       | Validate DTO, skip article, log warning with context                         |
| Elasticsearch is down                      | Fall back to MySQL FULLTEXT, log warning, continue serving                   |
| Redis is down                              | Fall back to direct DB queries (no cache), log error                         |
| MySQL is down                              | Return 503 Service Unavailable with message                                  |
| Very long article content                  | Truncate content at 65535 chars (TEXT column limit), store full URL           |
| Non-UTF8 characters in API response        | Sanitize with mb_convert_encoding before storage                             |
| Concurrent fetch jobs for same source      | Use `withoutOverlapping()` on schedule + job locks                           |
| Queue worker dies mid-job                  | `retry_after` ensures job is re-released, max 3 tries                        |
| User requests non-existent page            | Return empty data array with pagination meta showing 0 results               |
| Invalid filter values pass validation      | FormRequest catches these; return 422 with field-specific errors             |
| Category/source/author doesn't exist yet   | `firstOrCreate()` when storing articles, so new values are auto-created      |
| Search query with special characters       | Sanitize for ES (escape reserved chars: `+ - = && || > < ! ( ) { } [ ] ^ " ~ * ? : \ /`) |
| Extremely large result sets                | Hard cap `per_page` at 100, default 15                                       |
| Network error during fetch                 | Catch `ConnectionException`, retry with backoff                              |

---

## 13. Tests

### Test Structure

```
tests/
├── Unit/
│   ├── DTOs/
│   │   └── ArticleDTOTest.php
│   ├── Services/
│   │   ├── Adapters/
│   │   │   ├── NewsApiAdapterTest.php
│   │   │   ├── GuardianAdapterTest.php
│   │   │   └── NytimesAdapterTest.php
│   │   ├── NewsAggregatorServiceTest.php
│   │   ├── ArticleServiceTest.php
│   │   ├── ElasticsearchServiceTest.php
│   │   └── CacheServiceTest.php
│   ├── Filters/
│   │   ├── FilterByKeywordTest.php
│   │   ├── FilterByDateRangeTest.php
│   │   ├── FilterByCategoryTest.php
│   │   ├── FilterBySourceTest.php
│   │   └── FilterByAuthorTest.php
│   ├── Repositories/
│   │   ├── EloquentArticleRepositoryTest.php
│   │   └── CachedArticleRepositoryTest.php
│   └── Models/
│       └── ArticleTest.php (scopes, relationships)
├── Feature/
│   ├── Api/
│   │   ├── ArticleEndpointTest.php
│   │   ├── SearchEndpointTest.php
│   │   ├── SourceEndpointTest.php
│   │   ├── CategoryEndpointTest.php
│   │   ├── AuthorEndpointTest.php
│   │   ├── UserPreferenceEndpointTest.php
│   │   ├── PersonalizedFeedTest.php
│   │   └── AuthEndpointTest.php
│   ├── Jobs/
│   │   ├── FetchArticlesFromSourceTest.php
│   │   └── SyncArticleToElasticsearchTest.php
│   └── Commands/
│       ├── FetchNewsCommandTest.php
│       └── ReindexElasticsearchCommandTest.php
```

### Test Requirements

1. **Adapters**: Mock HTTP responses using `Http::fake()`. Test each adapter with real API response fixtures (stored in `tests/Fixtures/`). Test that each adapter correctly maps responses to `ArticleDTO`.

2. **Adapter failure tests**: Test retry logic, timeout handling, rate limit handling, invalid JSON, empty responses, missing fields.

3. **Filter pipes**: Unit test each pipe independently. Verify the query builder has the correct where clauses.

4. **API endpoints**: Feature tests using `$this->getJson('/api/v1/articles?category=tech')`. Test all filter combinations. Test pagination. Test validation errors (422). Test not found (404). Test empty results.

5. **Search**: Mock Elasticsearch client. Test search with various queries and filters. Test fallback to MySQL when ES is down.

6. **Cache**: Test that second call hits cache (mock the repository, assert only called once). Test cache invalidation.

7. **Queue/Jobs**: Test jobs are dispatched. Test job execution with mocked adapters. Test job failure handling.

8. **Auth**: Test register, login, logout. Test protected endpoints require token. Test preferences are user-scoped.

9. **Edge cases**: Test duplicate URL handling (upsert). Test articles with null categories/authors. Test max pagination.

### Test Helpers

Use factories for all models:
```php
Article::factory()->count(50)->create();
Article::factory()->forSource('guardian')->inCategory('tech')->create();
```

Use `RefreshDatabase` trait. Use `Http::fake()` for external APIs. Use `Queue::fake()` for job assertions. Use `Cache::fake()` for cache assertions.

---

## 14. README.md

Write a comprehensive README with these sections:

1. **Project Overview**: What this is, what it does
2. **Architecture**: High-level diagram (text-based), pattern explanations
3. **Tech Stack**: All technologies and why each was chosen
4. **Getting Started**:
   - Prerequisites (Docker, API keys)
   - Step-by-step setup (clone, copy .env, set API keys, docker-compose up)
   - Verify installation (health check endpoint)
5. **API Documentation**: All endpoints with request/response examples
6. **Configuration**: All env variables explained
7. **Design Decisions**: Why Adapter, Pipeline, Repository, etc.
8. **Database Schema**: ER diagram (text-based), index justifications
9. **Caching Strategy**: What's cached, TTLs, invalidation
10. **Queue & Scheduling**: How fetching works, failure handling
11. **Elasticsearch**: Setup, indexing, search fallback
12. **Testing**: How to run tests, what's covered
13. **Adding a New News Source**: Step-by-step guide (shows Open/Closed principle)
14. **Troubleshooting**: Common issues and fixes
15. **Makefile Commands**: Quick reference

---

## 15. Final Checklist

Before completion, verify:

- [ ] `docker-compose up --build` works from scratch with zero errors
- [ ] All migrations run successfully
- [ ] `php artisan news:fetch` fetches from all 3 sources
- [ ] All API endpoints return correct responses
- [ ] Filters work: keyword, date_from, date_to, category, source, author
- [ ] Search via Elasticsearch works
- [ ] ES fallback to MySQL FULLTEXT works when ES is down
- [ ] Redis caching is active (check with `redis-cli KEYS *`)
- [ ] Cache invalidates after new fetch
- [ ] Queue worker processes jobs
- [ ] Scheduler triggers fetch every 60 min
- [ ] Nginx load balances between app-1 and app-2
- [ ] All tests pass: `php artisan test`
- [ ] No N+1 queries (use Laravel Debugbar or check queries)
- [ ] Logs appear in `storage/logs/news_api.log`
- [ ] `api_fetch_logs` table has records after fetch
- [ ] User preferences work (set and get personalized feed)
- [ ] Auth flow works (register → login → token → protected routes)
- [ ] README is complete and accurate
- [ ] No hardcoded API keys or secrets in code
- [ ] PHP 8.4 features used where appropriate (readonly, enums, match, named args)
- [ ] Proper HTTP status codes (200, 201, 204, 401, 403, 404, 422, 500, 503)
