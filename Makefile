.PHONY: help install serve test lint fresh seed fetch reindex

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	composer install
	npm install
	cp -n .env.example .env || true
	php artisan key:generate --ansi

serve: ## Start development server
	php artisan serve

test: ## Run test suite
	php artisan test --compact

lint: ## Run Pint code formatter
	./vendor/bin/pint --dirty --format agent

fresh: ## Fresh migration with seed
	php artisan migrate:fresh --seed

seed: ## Run database seeders
	php artisan db:seed

fetch: ## Fetch articles from all sources
	php artisan news:fetch --all

reindex: ## Reindex articles in Elasticsearch
	php artisan elasticsearch:reindex

queue: ## Start queue worker
	php artisan queue:work --tries=3

# Sail commands (when using Docker)
sail-up: ## Start Sail containers
	./vendor/bin/sail up -d

sail-down: ## Stop Sail containers
	./vendor/bin/sail down

sail-test: ## Run tests via Sail
	./vendor/bin/sail artisan test --compact

sail-fresh: ## Fresh migration with seed via Sail
	./vendor/bin/sail artisan migrate:fresh --seed
