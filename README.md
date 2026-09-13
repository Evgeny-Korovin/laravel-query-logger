# Laravel Query Logger

`evgeny-korovin/laravel-query-logger` is a Laravel package that automatically records executed SQL queries and helps you find slow parts of your application.

[Русская версия](README.ru.md)

## Features

- automatic SQL query logging through `DB::listen`;
- storage of the actual SQL with bindings substituted;
- storage of execution time, database connection, and query timestamp;
- storage of the HTTP context: route name, URL, and IP address;
- detection of the application class and method that issued the query;
- a web interface with query listing, search, sorting, and pagination;
- color-coded queries based on execution time;
- `EXPLAIN` support for queries and `EXPLAIN ANALYZE` for `SELECT` statements;
- caching of the EXPLAIN result in the query record;
- deletion of all stored logs from the interface;
- optional AI query optimization recommendations through OpenCode;
- safe logger behavior: a logging failure does not interrupt the original query.

## Requirements

- PHP 8.2 or newer;
- Laravel 12 or 13;
- a Laravel-supported database with a `query_logs` table.

## Installation

Install the package through Composer:

```bash
composer require evgeny-korovin/laravel-query-logger --dev
```

Laravel will automatically discover the package service provider. Migrations are loaded automatically and will run with the regular migration command:

```bash
php artisan migrate
```

After installation, open:

```text
/sql-queries
```

For example, if your application is running at `http://localhost`, the interface will be available at `http://localhost/sql-queries`.

## Configuration

Publish the configuration file if needed:

```bash
php artisan vendor:publish --tag=query-logger-config
```

The configuration file will be located at `config/query-logger.php`.

## AI Recommendations

The **AI advice** button sends the SQL and EXPLAIN result to the selected AI provider. The package supports providers with an OpenAI-compatible API. You can configure the URL, API key, model, and additional headers for each provider.

OpenCode is used by default:

```dotenv
QUERY_LOGGER_AI_PROVIDER=opencode
QUERY_LOGGER_AI_MODEL=big-pickle
OPENCODE_API_KEY=your-api-key
```

To use OpenAI, set:

```dotenv
QUERY_LOGGER_AI_PROVIDER=openai
QUERY_LOGGER_AI_MODEL=gpt-4o-mini
OPENAI_API_KEY=your-api-key
```

The model can be overridden for a specific provider with `OPENCODE_MODEL` or `OPENAI_MODEL`. The list of providers, their URLs, and additional headers are configured in `config/query-logger.php`:

```php
'ai' => [
    'provider' => env('QUERY_LOGGER_AI_PROVIDER', 'opencode'),
    'model' => env('QUERY_LOGGER_AI_MODEL'),
    'providers' => [
        'opencode' => [
            'url' => env('OPENCODE_API_URL', 'https://opencode.ai/zen/v1/chat/completions'),
            'key' => env('OPENCODE_API_KEY'),
            'model' => env('OPENCODE_MODEL', 'big-pickle'),
            'headers' => [],
        ],
        'openai' => [
            'url' => env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions'),
            'key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'headers' => [],
        ],
    ],
],
```

If `QUERY_LOGGER_AI_MODEL` is not set, the selected provider's model is used. If the selected provider's API key is not configured, the package's other features continue to work, but AI recommendations will be unavailable.

## Routes

The package registers routes with the `web` middleware:

| Method | URI | Purpose |
| --- | --- | --- |
| `GET` | `/sql-queries` | list and search queries |
| `DELETE` | `/sql-queries` | delete all records |
| `GET` | `/sql-queries/{queryLog}/explain` | get EXPLAIN |
| `POST` | `/sql-queries/{queryLog}/ai-advice` | get AI recommendations |

Routes are not restricted by authorization by default. In production, make sure to protect the interface and delete operations with your own middleware or restrict access at the web server level.

## What Is Not Logged

The package ignores queries against the `query_logs` table to prevent recursion, as well as `EXPLAIN` queries executed by the interface itself.

## License

This package is distributed under the [MIT](https://opensource.org/licenses/MIT) license.

[![Telegram](https://img.shields.io/badge/Telegram-2CA5E0?logo=telegram&logoColor=white)](https://t.me/korovin_evgeny)

[Русская версия](README.ru.md)
