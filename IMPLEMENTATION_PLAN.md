# News Aggregator Backend - Implementation Plan

## Context

Build a comprehensive News Aggregator API that fetches articles from three external sources (NewsAPI.org, The Guardian, New York Times), stores them in MySQL, provides full-text search via Elasticsearch with MySQL fallback, and serves them through a RESTful API with user authentication and personalized feeds.

**Starting Point**: Fresh Laravel 12.50.0 skeleton with PHP 8.5.2, Laravel Sail installed but not initialized.

---

## Implementation Phases

### Phase 1: Infrastructure Setup

**1.1 Initialize Laravel Sail with MySQL & Redis**
```bash
php artisan sail:install --with=mysql,redis
```

**1.2 Modify `docker-compose.yml`** - Add services:
- Elasticsearch 8.15.0 container with health check
- Queue worker container (reuses sail image)
- Scheduler container (reuses sail image)
- Add `sail-elasticsearch` volume

**1.3 Create configuration files:**
- `config/news_sources.php` - API keys, base URLs, timeouts, cache TTLs
- `config/elasticsearch.php` - ES connection settings
- Modify `config/logging.php` - Add `news_api` daily log channel

**1.4 Update environment files:**
- `.env` and `.env.example` with all new variables (DB, Redis, ES, API keys)

**1.5 Create `Makefile`** - Sail command shortcuts

---

### Phase 2: Database Layer

**2.1 Migrations** (create in order):
| Migration | Key Indexes |
|-----------|-------------|
| `create_sources_table` | unique(slug), index(is_active) |
| `create_categories_table` | unique(slug) |
| `create_authors_table` | index(name), unique(name, source_id) |
| `create_articles_table` | unique(url), index(source_id, category_id, author_id, published_at), composite(source_id, published_at), composite(category_id, published_at), FULLTEXT(title, content) |
| `create_user_preferences_table` | unique(user_id) |
| `create_api_fetch_logs_table` | index(source, fetched_at) |
| Add soft deletes to users table |

**2.2 Models** (all with SoftDeletes):
- `Source`, `Category`, `Author`, `Article`, `UserPreference`, `ApiFetchLog`
- Use `casts()` method for JSON/datetime fields
- Define relationships with return types

**2.3 Factories & Seeders:**
- Factory for each model
- `SourceSeeder` with 3 default sources (newsapi, guardian, nytimes)
- `CategorySeeder` with common categories

---

### Phase 3: Core Application Layer

**3.1 Enums:**
- `app/Enums/FetchStatus.php` - pending, in_progress, success, failed, partial
- `app/Enums/NewsSource.php` - newsapi, guardian, nytimes

**3.2 DTOs:**
- `app/DTOs/ArticleDTO.php` - readonly, immutable article data from adapters
- `app/DTOs/ArticleFilterDTO.php` - filter parameters from requests
- `app/DTOs/FetchResultDTO.php` - fetch operation results

**3.3 Contracts:**
- `app/Contracts/NewsAdapterInterface.php`
- `app/Contracts/ArticleRepositoryInterface.php`
- `app/Contracts/SearchServiceInterface.php`

---

### Phase 4: News Adapters (Adapter Pattern)

**4.1 Abstract Base:**
- `app/Services/NewsAdapters/AbstractNewsAdapter.php`
- Shared: HTTP client, retry logic, logging, exception wrapping

**4.2 Concrete Adapters:**
- `app/Services/NewsAdapters/NewsApiAdapter.php`
- `app/Services/NewsAdapters/GuardianAdapter.php`
- `app/Services/NewsAdapters/NytimesAdapter.php`

Each adapter transforms API-specific responses to `ArticleDTO[]`.

---

### Phase 5: Repository Layer (Repository + Decorator Pattern)

**5.1 Base Repository:**
- `app/Repositories/EloquentArticleRepository.php`

**5.2 Cached Decorator:**
- `app/Repositories/CachedArticleRepository.php`
- Wraps Eloquent repo with Redis cache using tags

---

### Phase 6: Filter Pipeline (Pipeline Pattern)

**6.1 Pipeline:**
- `app/Pipelines/ArticleFilterPipeline.php`

**6.2 Pipes:**
- `app/Pipelines/Pipes/KeywordFilter.php`
- `app/Pipelines/Pipes/DateRangeFilter.php`
- `app/Pipelines/Pipes/CategoryFilter.php`
- `app/Pipelines/Pipes/SourceFilter.php`
- `app/Pipelines/Pipes/AuthorFilter.php`
- `app/Pipelines/Pipes/SortFilter.php`

---

### Phase 7: Search Services

**7.1 Elasticsearch Service:**
- `app/Services/ElasticsearchService.php`
- Index mapping, search with bool queries, bulk indexing

**7.2 MySQL Fallback:**
- `app/Services/MySqlSearchService.php`
- Uses FULLTEXT search

**7.3 Factory:**
- `app/Services/SearchServiceFactory.php`
- Returns ES or MySQL based on availability

---

### Phase 8: Observer Pattern (ES Sync)

- `app/Observers/ArticleObserver.php`
- Dispatches `SyncArticleToElasticsearch` on created/updated

---

### Phase 9: Jobs & Commands

**9.1 Jobs:**
- `app/Jobs/FetchArticlesFromSource.php` - Per-source fetch
- `app/Jobs/SyncArticleToElasticsearch.php` - Single article sync
- `app/Jobs/BulkSyncToElasticsearch.php` - Batch sync

**9.2 Commands:**
- `app/Console/Commands/FetchNewsCommand.php` - `news:fetch`
- `app/Console/Commands/ReindexElasticsearchCommand.php` - `elasticsearch:reindex`

**9.3 Scheduler** (in `routes/console.php`):
- `news:fetch --all` hourly, without overlapping

---

### Phase 10: API Layer

**10.1 Install Sanctum:**
```bash
php artisan install:api
```

**10.2 Controllers** (`app/Http/Controllers/Api/V1/`):
- `ArticleController` - index, show
- `SearchController` - search
- `SourceController` - index, show
- `CategoryController` - index, show
- `AuthorController` - index, show
- `AuthController` - register, login, logout
- `UserPreferenceController` - show, update
- `PersonalizedFeedController` - index

**10.3 Form Requests:**
- `ArticleIndexRequest`, `ArticleSearchRequest`, `UserPreferenceRequest`
- `Auth/LoginRequest`, `Auth/RegisterRequest`

**10.4 API Resources:**
- `ArticleResource`, `ArticleCollection`
- `SourceResource`, `CategoryResource`, `AuthorResource`
- `UserPreferenceResource`

**10.5 Routes** (`routes/api.php`):
```
Public:
  POST   /api/v1/auth/register
  POST   /api/v1/auth/login
  GET    /api/v1/articles
  GET    /api/v1/articles/{article}
  GET    /api/v1/articles/search
  GET    /api/v1/sources
  GET    /api/v1/categories
  GET    /api/v1/authors

Authenticated (auth:sanctum):
  POST   /api/v1/auth/logout
  GET    /api/v1/user/preferences
  PUT    /api/v1/user/preferences
  GET    /api/v1/user/feed
```

---

### Phase 11: Exception Handling

**11.1 Custom Exceptions:**
- `app/Exceptions/NewsSourceException.php`
- `app/Exceptions/NewsSourceUnavailableException.php`
- `app/Exceptions/ElasticsearchException.php`

**11.2 Global Handler** (modify `bootstrap/app.php`):
- JSON responses for API routes
- Proper status codes (404, 422, 503)

---

### Phase 12: Service Providers

- `app/Providers/NewsServiceProvider.php` - Binds adapters
- `app/Providers/RepositoryServiceProvider.php` - Binds repositories
- Register in `bootstrap/providers.php`

---

### Phase 13: Testing

**Unit Tests:**
- `tests/Unit/DTOs/ArticleDTOTest.php`
- `tests/Unit/Services/Adapters/*AdapterTest.php` (mock HTTP)
- `tests/Unit/Pipelines/*FilterTest.php`
- `tests/Unit/Repositories/*Test.php`

**Feature Tests:**
- `tests/Feature/Api/V1/ArticleControllerTest.php`
- `tests/Feature/Api/V1/SearchControllerTest.php`
- `tests/Feature/Api/V1/AuthControllerTest.php`
- `tests/Feature/Api/V1/UserPreferenceControllerTest.php`
- `tests/Feature/Jobs/FetchArticlesFromSourceTest.php`
- `tests/Feature/Commands/FetchNewsCommandTest.php`

---

### Phase 14: Documentation

- `README.md` - Full project documentation
- `Makefile` - Command shortcuts

---

## Verification Plan

After implementation, verify:

1. **Infrastructure**: `./vendor/bin/sail up` starts all services
2. **Database**: `sail artisan migrate --seed` runs successfully
3. **Fetching**: `sail artisan news:fetch` fetches from all 3 sources
4. **API Endpoints**: Test all endpoints return correct responses
5. **Filtering**: Verify keyword, date, category, source, author filters work
6. **Search**: ES search works, MySQL fallback activates when ES down
7. **Caching**: Redis caching active (`sail exec redis redis-cli KEYS '*'`)
8. **Queue**: Queue worker processes jobs
9. **Auth**: Register, login, logout flow works
10. **Tests**: `sail artisan test` passes all tests
11. **Logs**: Check `storage/logs/news_api.log` for entries
12. **Pint**: `vendor/bin/pint --dirty --format agent` passes

---

## File Count Summary

| Category | Count |
|----------|-------|
| Migrations | 7 |
| Models | 6 |
| Factories | 6 |
| Seeders | 3 |
| Enums | 2 |
| DTOs | 3 |
| Contracts | 3 |
| Adapters | 4 |
| Services | 4 |
| Repositories | 2 |
| Pipelines/Pipes | 7 |
| Controllers | 8 |
| Form Requests | 5 |
| Resources | 6 |
| Jobs | 3 |
| Commands | 2 |
| Observers | 1 |
| Exceptions | 3 |
| Providers | 2 |
| Config files | 2 |
| **Total** | ~70+ files |