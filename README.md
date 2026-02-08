# News Aggregator API

A RESTful API for aggregating news from multiple sources including NewsAPI, The Guardian, and New York Times. Built with Laravel 12.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+ (or SQLite for development)
- Node.js 18+ (for frontend assets)
- Elasticsearch (optional, for full-text search)

## Quick Start

### 1. Clone and Install

```bash
git clone <repository-url>
cd news-api
composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Environment

Edit `.env` and set your database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=news_api
DB_USERNAME=root
DB_PASSWORD=
```

Add your news source API keys:

```env
NEWSAPI_KEY=your_newsapi_key
GUARDIAN_KEY=your_guardian_key
NYTIMES_KEY=your_nytimes_key
```

### 4. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 5. Run the Application

```bash
# Development server
php artisan serve

# Or use the full dev environment (server, queue, logs, vite)
composer run dev
```

The API will be available at `http://localhost:8000`.

## Docker Setup (Alternative)

```bash
# Start the containers
docker compose up -d

# Run migrations
docker compose exec app php artisan migrate --seed
```

## Fetching News Articles

To fetch articles from configured news sources:

```bash
# Fetch from all enabled sources
php artisan news:fetch

# Fetch from a specific source
php artisan news:fetch --source=newsapi
php artisan news:fetch --source=guardian
php artisan news:fetch --source=nytimes
```

## API Documentation

The API documentation is available at:

- **Interactive Docs**: [http://localhost:8000/docs](http://localhost:8000/docs)
- **Postman Collection**: [http://localhost:8000/docs.postman](http://localhost:8000/docs.postman)
- **OpenAPI Spec**: [http://localhost:8000/docs.openapi](http://localhost:8000/docs.openapi)

### Regenerating Documentation

```bash
php artisan scribe:generate
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/V1/ArticleTest.php

# Run with coverage
php artisan test --coverage
```

## Key Features

- **Multi-source Aggregation**: Fetches news from NewsAPI, The Guardian, and NYTimes
- **Full-text Search**: Powered by Elasticsearch (falls back to MySQL FULLTEXT)
- **Personalized Feed**: Users can set preferences for sources, categories, and authors
- **Authentication**: Token-based auth using Laravel Sanctum
- **API Versioning**: Structured under `/api/v1/`

## Project Structure

```
app/
├── Console/Commands/     # Artisan commands (news:fetch, etc.)
├── Contracts/            # Interface definitions
├── DTOs/                 # Data Transfer Objects
├── Http/
│   ├── Controllers/Api/V1/  # API controllers
│   ├── Requests/         # Form request validation
│   └── Resources/        # API resources
├── Jobs/                 # Queue jobs
├── Models/               # Eloquent models
├── Repositories/         # Repository pattern implementations
├── Services/             # Business logic services
│   └── NewsAdapters/     # News source adapters
└── Pipelines/            # Query filter pipelines
```

## License

This project is open-sourced software licensed under the MIT license.
