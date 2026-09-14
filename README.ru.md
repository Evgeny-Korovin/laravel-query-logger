# Laravel Query Logger

`evgeny-korovin/laravel-query-logger` — пакет для Laravel, который автоматически записывает выполненные SQL-запросы и помогает находить медленные места в приложении.

## Возможности

- автоматическое логирование SQL-запросов через `DB::listen`;
- сохранение фактического SQL с подставленными bindings;
- сохранение времени выполнения, подключения к БД и времени выполнения запроса;
- сохранение HTTP-контекста: route name, URL и IP-адрес;
- определение класса и метода приложения, из которого был вызван запрос;
- веб-интерфейс со списком запросов, поиском, сортировкой и пагинацией;
- цветовая индикация запросов по времени выполнения;
- просмотр `EXPLAIN` для запросов и `EXPLAIN ANALYZE` для `SELECT`;
- кэширование результата EXPLAIN в записи запроса;
- удаление всех сохранённых логов из интерфейса;
- необязательные AI-рекомендации по оптимизации запросов через OpenCode;
- безопасное поведение логгера: ошибка записи лога не прерывает исходный запрос.

## Требования

- PHP 8.2 или новее;
- Laravel 12 или 13;
- поддерживаемая Laravel база данных с таблицей `query_logs`.

## Установка

Установите пакет через Composer:

```bash
composer require evgeny-korovin/laravel-query-logger --dev
```

Laravel автоматически обнаружит service provider пакета. Миграции загружаются автоматически и будут выполнены при обычном запуске миграций:

```bash
php artisan migrate
```

Страница списка запросов подключает Tailwind CSS через официальный CDN-скрипт, поэтому для работы интерфейса не нужно добавлять npm-пакеты или менять frontend-сборку приложения.

После установки откройте:

```text
/sql-queries
```

Например, если приложение запущено на `http://localhost`, интерфейс будет доступен по адресу `http://localhost/sql-queries`.

## Конфигурация

При необходимости опубликуйте конфигурацию:

```bash
php artisan vendor:publish --tag=query-logger-config
```

Файл конфигурации будет расположен в `config/query-logger.php`.

Пороговые значения времени выполнения запросов можно настроить через переменные окружения:

```dotenv
QUERY_LOGGER_WARNING_THRESHOLD_MS=50
QUERY_LOGGER_CRITICAL_THRESHOLD_MS=100
```

Запросы до порога предупреждения отображаются обычным цветом, запросы между порогами предупреждения и критическим выделяются желтым, а запросы выше критического порога — красным.

## AI-рекомендации

Кнопка **AI-совет** отправляет SQL и результат EXPLAIN в выбранный AI-провайдер. Пакет поддерживает провайдеры с OpenAI-compatible API. В конфигурации можно указать URL, API-ключ, модель и дополнительные заголовки для каждого провайдера.

По умолчанию используется OpenCode:

```dotenv
QUERY_LOGGER_AI_PROVIDER=opencode
QUERY_LOGGER_AI_MODEL=big-pickle
OPENCODE_API_KEY=your-api-key
```

Чтобы использовать OpenAI, укажите:

```dotenv
QUERY_LOGGER_AI_PROVIDER=openai
QUERY_LOGGER_AI_MODEL=gpt-4o-mini
OPENAI_API_KEY=your-api-key
```

Модель можно переопределить для конкретного провайдера через `OPENCODE_MODEL` или `OPENAI_MODEL`. Список провайдеров, их URL и дополнительные заголовки настраиваются в `config/query-logger.php`:

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

Если `QUERY_LOGGER_AI_MODEL` не указан, используется модель выбранного провайдера. Если API-ключ выбранного провайдера не настроен, остальные возможности пакета продолжают работать, а AI-рекомендации недоступны.

## Маршруты

Пакет регистрирует маршруты с middleware `web`:

| Метод | URI | Назначение |
| --- | --- | --- |
| `GET` | `/sql-queries` | список и поиск запросов |
| `DELETE` | `/sql-queries` | удалить все записи |
| `GET` | `/sql-queries/{queryLog}/explain` | получить EXPLAIN |
| `POST` | `/sql-queries/{queryLog}/ai-advice` | получить AI-рекомендацию |

Маршруты по умолчанию не ограничены авторизацией. В production обязательно защитите интерфейс и операции удаления своим middleware или ограничьте доступ на уровне веб-сервера.

## Что не записывается

Пакет игнорирует запросы к таблице `query_logs`, чтобы не создавать рекурсию, а также запросы `EXPLAIN`, выполняемые самим интерфейсом.

## Лицензия

Пакет распространяется под лицензией [MIT](https://opensource.org/licenses/MIT).
