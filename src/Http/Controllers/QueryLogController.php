<?php

namespace Godmode\QueryLogger\Http\Controllers;

use Godmode\QueryLogger\Models\QueryLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class QueryLogController
{
    public function index(Request $request): View
    {
        $sort      = $request->string('sort')->toString();
        $direction = $request->string('direction')->lower()->toString();
        $perPage   = $request->integer('per_page', 20);
        $search    = trim($request->string('search')->toString());
        $sort      = in_array($sort, ['created_at', 'time_ms'], true) ? $sort : 'created_at';
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
        $perPage   = in_array($perPage, [10, 20, 30, 50], true) ? $perPage : 20;

        $query = QueryLog::query()->whereRaw("LOWER(TRIM(sql)) NOT LIKE 'explain%'");

        if ($search !== '') {
            $query->whereRaw('LOWER(sql) LIKE ?', ['%'.mb_strtolower($search).'%']);
        }

        $queryLogs = $query->orderBy($sort, $direction)->latest('id')->paginate($perPage)->withQueryString();

        return view('query-logger::query-logs.index', compact('queryLogs', 'sort', 'direction', 'perPage', 'search'));
    }

    public function destroy(): RedirectResponse
    {
        QueryLog::query()->delete();

        return redirect()->route('query-logs.index');
    }

    public function explain(QueryLog $queryLog): JsonResponse
    {
        try {
            return response()->json(['explain' => $this->getExplainResult($queryLog)]);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'Не удалось выполнить EXPLAIN: '.$exception->getMessage()], 422);
        }
    }

    public function aiAdvice(QueryLog $queryLog): JsonResponse
    {
        if (! config('query-logger.opencode.key')) {
            return response()->json(['message' => 'OpenCode API key не настроен. Укажите OPENCODE_API_KEY.'], 503);
        }

        try {
            $explain  = $this->getExplainResult($queryLog);
            $prompt   = "Вот SQL запрос {$queryLog->sql}, а вот его EXPLAIN {$explain}. Дай пошаговый план по улучшению производительности запроса.";
            $response = Http::withToken(config('query-logger.opencode.key'))
                ->withHeaders(['x-opencode-session' => 'query-log-'.$queryLog->id])
                ->timeout(60)
                ->post('https://opencode.ai/zen/v1/chat/completions', [
                    'model'    => config('query-logger.opencode.model', 'big-pickle'),
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);

            if ($response->tooManyRequests()) {
                return response()->json(['message' => 'Бесплатная модель OpenCode временно недоступна или превышен лимит запросов.'], 429);
            }

            $response->throw();
            $advice = (string) $response->json('choices.0.message.content', '');

            return response()->json([
                'explain'     => $explain,
                'advice'      => $advice,
                'advice_html' => Str::markdown($advice, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
            ]);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'Не удалось получить AI-совет OpenCode: '.$exception->getMessage()], 422);
        }
    }

    private function getExplainResult(QueryLog $queryLog): string
    {
        if ($queryLog->explain_result) {
            return $queryLog->explain_result;
        }

        $sql        = trim($queryLog->sql);
        $isSelect   = preg_match('/^select\b/i', $sql) === 1;
        $explainSql = ($isSelect ? 'EXPLAIN ANALYZE ' : 'EXPLAIN ').$sql;
        $rows       = DB::connection($queryLog->connection)->select($explainSql);
        $lines      = [];

        foreach ($rows as $row) {
            foreach ((array) $row as $value) {
                $lines[] = is_scalar($value) ? (string) $value : json_encode($value);
            }
        }

        $explain = implode(PHP_EOL, $lines);
        $queryLog->update(['explain_result' => $explain]);

        return $explain;
    }
}
