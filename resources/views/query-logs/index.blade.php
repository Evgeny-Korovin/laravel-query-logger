<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>История SQL-запросов</title>
        <style>
            [data-ai-result] p { margin: .75rem 0; }
            [data-ai-result] p:first-child { margin-top: 0; }
            [data-ai-result] p:last-child { margin-bottom: 0; }
            [data-ai-result] h1, [data-ai-result] h2, [data-ai-result] h3 { margin: 1rem 0 .5rem; font-weight: 700; }
            [data-ai-result] h1 { font-size: 1.25rem; }
            [data-ai-result] h2 { font-size: 1.125rem; }
            [data-ai-result] ul { list-style: disc; padding-left: 1.5rem; }
            [data-ai-result] ol { list-style: decimal; padding-left: 1.5rem; }
            [data-ai-result] li { margin: .25rem 0; }
            [data-ai-result] code { border-radius: .25rem; background: rgb(51 65 85); padding: .125rem .25rem; font-family: ui-monospace, monospace; font-size: .875em; }
            [data-ai-result] pre { overflow-x: auto; border-radius: .5rem; background: rgb(15 23 42); padding: .75rem; }
            [data-ai-result] pre code { background: transparent; padding: 0; }
            [data-ai-result] a { text-decoration: underline; }
            [data-auto-refresh]:checked + span > span { transform: translateX(1.25rem); }
        </style>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @php
            $nextTimeDirection = $sort === 'created_at' && $direction === 'asc' ? 'desc' : 'asc';
            $nextExecutionDirection = $sort === 'time_ms' && $direction === 'asc' ? 'desc' : 'asc';
        @endphp
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <main class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex flex-col gap-2">
                    <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Мониторинг БД</p>
                    <h1 class="text-3xl font-bold tracking-tight">История SQL-запросов</h1>
                    <p class="text-slate-600 dark:text-slate-400">Всего записей: {{ $queryLogs->total() }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300">
                        <input type="checkbox" data-auto-refresh class="peer sr-only">
                        <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-indigo-600 dark:bg-slate-700">
                            <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition"></span>
                        </span>
                        Авто
                    </label>
                    <button type="button" data-clear-open class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Очистить</button>
                </div>
            </header>

            <form method="GET" action="{{ route('query-logs.index') }}" class="flex flex-col gap-3 sm:flex-row">
                <label for="query-search" class="sr-only">Поиск по тексту запроса</label>
                <input id="query-search" type="search" name="search" value="{{ $search }}" placeholder="Поиск по тексту SQL-запроса..." class="min-w-0 flex-1 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-900">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <button type="submit" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Найти</button>
                @if ($search !== '')
                    <a href="{{ route('query-logs.index', ['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage]) }}" class="rounded-md border border-slate-300 px-5 py-2 text-center text-sm font-semibold dark:border-slate-700">Сбросить</a>
                @endif
            </form>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:bg-slate-950">
                            <tr>
                                <th class="px-4 py-3"><a href="{{ route('query-logs.index', ['sort' => 'created_at', 'direction' => $nextTimeDirection, 'search' => $search, 'per_page' => $perPage]) }}">Время @if ($sort === 'created_at') {!! $direction === 'asc' ? '&#8593;' : '&#8595;' !!} @endif</a></th>
                                <th class="px-4 py-3">Запрос</th>
                                <th class="px-4 py-3"><a href="{{ route('query-logs.index', ['sort' => 'time_ms', 'direction' => $nextExecutionDirection, 'search' => $search, 'per_page' => $perPage]) }}">Выполнение @if ($sort === 'time_ms') {!! $direction === 'asc' ? '&#8593;' : '&#8595;' !!} @endif</a></th>
                                <th class="px-4 py-3">Вызывающий код</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @forelse ($queryLogs as $queryLog)
                                <tr @class([
                                    'align-top hover:bg-slate-50 dark:hover:bg-slate-800/60' => $queryLog->time_ms <= 50,
                                    'bg-yellow-50 dark:bg-yellow-950/30' => $queryLog->time_ms > 50 && $queryLog->time_ms <= 100,
                                    'bg-red-50 dark:bg-red-950/40' => $queryLog->time_ms > 100,
                                ])>
                                    <td class="whitespace-nowrap px-4 py-4 text-slate-500">{{ $queryLog->created_at?->format('d.m.Y H:i:s') }}</td>
                                    <td class="max-w-2xl px-4 py-4">
                                        <code class="block whitespace-pre-wrap break-words font-mono text-xs leading-5">{{ $queryLog->sql }}</code>
                                        <div class="mt-2 flex items-center gap-3 text-xs text-slate-500">
                                            @if ($queryLog->bindings)
                                                <details>
                                                    <summary class="cursor-pointer">Параметры</summary>
                                                    <pre class="mt-2 whitespace-pre-wrap break-words">{{ json_encode($queryLog->bindings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </details>
                                            @endif
                                            <button type="button" data-explain-url="{{ route('query-logs.explain', $queryLog) }}" class="cursor-pointer font-semibold text-indigo-600">{{ preg_match('/^select\b/i', ltrim($queryLog->sql)) === 1 ? 'EXPLAIN ANALYZE' : 'EXPLAIN' }}</button>
                                            <button type="button" data-ai-url="{{ route('query-logs.ai-advice', $queryLog) }}" class="cursor-pointer font-semibold text-violet-600">AI-совет</button>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">{{ number_format($queryLog->time_ms, 2, ',', ' ') }} мс</td>
                                    <td class="max-w-xs break-words px-4 py-4 font-mono text-xs">{{ $queryLog->caller_class && $queryLog->caller_method ? $queryLog->caller_class.'::'.$queryLog->caller_method.'()' : '—' }}</td>
                                </tr>
                                <tr id="explain-{{ $queryLog->id }}" hidden>
                                    <td colspan="4" class="bg-slate-950 px-4 py-4 text-slate-100">
                                        <pre data-explain-result class="overflow-x-auto whitespace-pre-wrap break-words font-mono text-xs leading-5"></pre>
                                        <div data-ai-result class="mt-4 whitespace-pre-wrap border-t border-slate-700 pt-4 text-sm leading-6"></div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-12 text-center text-slate-500">Запросы еще не записаны.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{ $queryLogs->links() }}
            <form method="GET" action="{{ route('query-logs.index') }}" class="flex justify-center">
                <label class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                    <span>Записей на странице</span>
                    <select name="per_page" onchange="this.form.submit()" class="rounded-md border border-slate-300 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                        @foreach ([10, 20, 30, 50] as $pageSize)<option value="{{ $pageSize }}" @selected($perPage === $pageSize)>{{ $pageSize }}</option>@endforeach
                    </select>
                    <input type="hidden" name="search" value="{{ $search }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="direction" value="{{ $direction }}">
                </label>
            </form>
        </main>

        <div data-clear-modal hidden class="fixed inset-0 z-50 items-center justify-center bg-slate-950/60 px-4">
            <div role="dialog" aria-modal="true" class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                <h2 class="text-xl font-bold">Очистить журнал?</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">Все сохраненные SQL-запросы будут удалены без возможности восстановления.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-clear-cancel class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold dark:border-slate-700">Отмена</button>
                    <form method="POST" action="{{ route('query-logs.destroy') }}">@csrf @method('DELETE')<button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">Удалить все</button></form>
                </div>
            </div>
        </div>

        <script>
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const expandedRowsStorageKey = 'query-logger-expanded-rows';

            const toggleResultRow = (button) => {
                const row = button.closest('tr');
                const target = row.nextElementSibling;
                target.hidden = false;
                return target;
            };

            document.querySelectorAll('[data-explain-url]').forEach((button) => button.addEventListener('click', async () => {
                const target = toggleResultRow(button);
                const result = target.querySelector('[data-explain-result]');
                if (target.dataset.loaded) return;
                button.disabled = true;
                result.textContent = 'Выполнение EXPLAIN...';
                try {
                    const response = await fetch(button.dataset.explainUrl, { headers: { Accept: 'application/json' } });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Не удалось получить план выполнения.');
                    result.textContent = payload.explain;
                    target.dataset.loaded = 'true';
                } catch (error) { result.textContent = error.message; } finally { button.disabled = false; }
            }));

            document.querySelectorAll('[data-ai-url]').forEach((button) => button.addEventListener('click', async () => {
                const target = toggleResultRow(button);
                const explain = target.querySelector('[data-explain-result]');
                const advice = target.querySelector('[data-ai-result]');
                if (target.dataset.aiLoaded) return;
                button.disabled = true;
                advice.textContent = 'Подготовка EXPLAIN и AI-совета...';
                try {
                    const response = await fetch(button.dataset.aiUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken } });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Не удалось получить AI-совет.');
                    explain.textContent = payload.explain;
                    advice.innerHTML = payload.advice_html;
                    target.dataset.aiLoaded = 'true';
                } catch (error) { advice.textContent = error.message; } finally { button.disabled = false; }
            }));

            const clearModal = document.querySelector('[data-clear-modal]');
            document.querySelector('[data-clear-open]').addEventListener('click', () => { clearModal.hidden = false; clearModal.classList.add('flex'); });
            document.querySelector('[data-clear-cancel]').addEventListener('click', () => { clearModal.hidden = true; clearModal.classList.remove('flex'); });

            const autoRefresh = document.querySelector('[data-auto-refresh]');
            autoRefresh.checked = localStorage.getItem('query-logger-auto-refresh') === 'true';
            autoRefresh.addEventListener('change', () => localStorage.setItem('query-logger-auto-refresh', autoRefresh.checked));
            if (autoRefresh.checked) window.setInterval(() => window.location.reload(), 5000);

            window.addEventListener('beforeunload', () => {
                const state = {};
                document.querySelectorAll('[id^="explain-"]').forEach((row) => {
                    if (!row.hidden) state[row.id] = { explain: row.querySelector('[data-explain-result]').textContent, advice: row.querySelector('[data-ai-result]').innerHTML, loaded: row.dataset.loaded, aiLoaded: row.dataset.aiLoaded };
                });
                sessionStorage.setItem(expandedRowsStorageKey, JSON.stringify(state));
            });
            try {
                const state = JSON.parse(sessionStorage.getItem(expandedRowsStorageKey) || '{}');
                Object.entries(state).forEach(([id, values]) => {
                    const row = document.getElementById(id);
                    if (!row) return;
                    row.hidden = false;
                    row.querySelector('[data-explain-result]').textContent = values.explain;
                    row.querySelector('[data-ai-result]').innerHTML = values.advice;
                    if (values.loaded) row.dataset.loaded = values.loaded;
                    if (values.aiLoaded) row.dataset.aiLoaded = values.aiLoaded;
                });
            } catch { sessionStorage.removeItem(expandedRowsStorageKey); }
        </script>
    </body>
</html>
