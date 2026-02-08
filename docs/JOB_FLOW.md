# News API - Job Flow Documentation

## Overview

This application uses Laravel's queue system to handle asynchronous tasks for fetching news articles and syncing them to Elasticsearch.

---

## Jobs in the Application

| Job | Purpose |
|-----|---------|
| `FetchArticlesFromSource` | Fetches articles from external news APIs |
| `SyncArticleToElasticsearch` | Syncs a single article to Elasticsearch |
| `BulkSyncToElasticsearch` | Bulk syncs multiple articles to Elasticsearch |

---

## Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              ENTRY POINTS                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  1. Artisan Command                    2. Scheduled Task (if configured)   │
│     php artisan news:fetch --all          Schedule::command('news:fetch')  │
│                                                                             │
└────────────────────────────────┬────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         FetchNewsCommand                                     │
│                   app/Console/Commands/FetchNewsCommand.php                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  • Parses options: --source, --all, --query, --category, --sync            │
│  • Loops through requested sources (newsapi, guardian, nytimes)            │
│  • Dispatches FetchArticlesFromSource job for each source                  │
│                                                                             │
│  foreach ($sources as $sourceSlug) {                                       │
│      dispatch(new FetchArticlesFromSource($sourceSlug, $query, $category));│
│  }                                                                          │
│                                                                             │
└────────────────────────────────┬────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                      FetchArticlesFromSource Job                             │
│                   app/Jobs/FetchArticlesFromSource.php                       │
├─────────────────────────────────────────────────────────────────────────────┤
│  Properties:                                                                │
│  • $tries = 3 (retry 3 times on failure)                                   │
│  • $backoff = 60 (wait 60 seconds between retries)                         │
│                                                                             │
│  Flow:                                                                      │
│  1. resolveAdapter() → Returns the correct adapter based on $sourceSlug    │
│     ┌─────────────────────────────────────────────────────────────┐        │
│     │ match ($this->sourceSlug) {                                 │        │
│     │     'newsapi'  => NewsApiAdapter::class,                    │        │
│     │     'guardian' => GuardianAdapter::class,                   │        │
│     │     'nytimes'  => NytimesAdapter::class,                    │        │
│     │ }                                                           │        │
│     └─────────────────────────────────────────────────────────────┘        │
│                                                                             │
│  2. Check if adapter is enabled                                            │
│                                                                             │
│  3. Call $adapter->fetchArticles($query, $category)                        │
│     → Returns array of ArticleDTO objects                                  │
│                                                                             │
│  4. Loop through articles and call $repository->upsertFromDto($dto)        │
│     → Creates or updates articles in database                              │
│                                                                             │
│  5. Log the fetch result to ApiFetchLog table                              │
│                                                                             │
└────────────────────────────────┬────────────────────────────────────────────┘
                                 │
                                 │ (Article created/updated in DB)
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ArticleObserver                                      │
│                   app/Observers/ArticleObserver.php                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Listens to Eloquent events on the Article model:                          │
│                                                                             │
│  • created() → Dispatches SyncArticleToElasticsearch                       │
│  • updated() → Dispatches SyncArticleToElasticsearch                       │
│  • deleted() → Dispatches SyncArticleToElasticsearch (with delete: true)   │
│                                                                             │
│  Only triggers if config('elasticsearch.enabled') is true                  │
│                                                                             │
└────────────────────────────────┬────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                    SyncArticleToElasticsearch Job                            │
│                   app/Jobs/SyncArticleToElasticsearch.php                    │
├─────────────────────────────────────────────────────────────────────────────┤
│  Properties:                                                                │
│  • $tries = 3                                                              │
│  • $backoff = 30                                                           │
│                                                                             │
│  Flow:                                                                      │
│  1. Check if Elasticsearch is available                                    │
│  2. If $delete is true → Remove article from index                         │
│  3. Otherwise → Index the article                                          │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                    BulkSyncToElasticsearch Job                               │
│                   app/Jobs/BulkSyncToElasticsearch.php                       │
├─────────────────────────────────────────────────────────────────────────────┤
│  Used by: ReindexElasticsearchCommand                                       │
│                                                                             │
│  Properties:                                                                │
│  • $tries = 3                                                              │
│  • $backoff = 60                                                           │
│                                                                             │
│  Flow:                                                                      │
│  1. Receives array of article IDs                                          │
│  2. Calls $service->bulkIndex($this->articleIds)                           │
│  3. Logs completion                                                        │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Complete Flow Example

```
User runs: php artisan news:fetch --all

    │
    ▼
┌─────────────────┐
│ FetchNewsCommand│
└────────┬────────┘
         │
         │ dispatch() for each source
         │
    ┌────┴────┬─────────────┐
    ▼         ▼             ▼
┌───────┐ ┌───────┐    ┌───────┐
│newsapi│ │guardian│    │nytimes│   ← FetchArticlesFromSource jobs
└───┬───┘ └───┬───┘    └───┬───┘
    │         │            │
    │    Each job:         │
    │    1. Resolves adapter
    │    2. Fetches articles from API
    │    3. Upserts to database
    │         │
    ▼         ▼            ▼
┌─────────────────────────────┐
│      Database (articles)     │
└──────────────┬──────────────┘
               │
               │ ArticleObserver triggers
               ▼
┌─────────────────────────────┐
│ SyncArticleToElasticsearch  │  ← For each created/updated article
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│       Elasticsearch         │
└─────────────────────────────┘
```

---

## About the Tagged Adapters (Not Currently Used)

In `NewsServiceProvider.php`:

```php
$this->app->tag([
    NewsApiAdapter::class,
    GuardianAdapter::class,
    NytimesAdapter::class,
], 'news.adapters');
```

**Current Status:** This tag is defined but **NOT currently being used** anywhere in the codebase.

**Current Implementation:** The `FetchArticlesFromSource` job uses a `match` statement instead:

```php
private function resolveAdapter(): ?NewsAdapterInterface
{
    $adapterClass = match ($this->sourceSlug) {
        'newsapi' => NewsApiAdapter::class,
        'guardian' => GuardianAdapter::class,
        'nytimes' => NytimesAdapter::class,
        default => null,
    };

    return app($adapterClass);
}
```

**Potential Refactor:** The tag could be used to iterate all adapters without hardcoding:

```php
// Example: Fetch from ALL adapters without knowing their classes
$adapters = app()->tagged('news.adapters');

foreach ($adapters as $adapter) {
    $articles = $adapter->fetchArticles();
    // process articles...
}
```

This would make adding new news sources easier - just add to the tag, no need to update `match` statements.

---

## Queue Configuration

Jobs are dispatched to Laravel's queue. To process them:

```bash
# Process jobs from the queue
php artisan queue:work

# Or for development (no daemon)
php artisan queue:listen
```

For the `--sync` option, jobs run immediately in the same process (useful for debugging).

---

## Retry & Error Handling

| Job | Tries | Backoff |
|-----|-------|---------|
| `FetchArticlesFromSource` | 3 | 60 seconds |
| `SyncArticleToElasticsearch` | 3 | 30 seconds |
| `BulkSyncToElasticsearch` | 3 | 60 seconds |

Failed jobs are logged to `ApiFetchLog` table and Laravel's failed_jobs table.