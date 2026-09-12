<?php

namespace Godmode\QueryLogger\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['connection', 'sql', 'bindings', 'time_ms', 'route_name', 'url', 'ip_address', 'caller_class', 'caller_method', 'explain_result', 'created_at'])]
class QueryLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'bindings'   => 'array',
            'time_ms'    => 'float',
            'created_at' => 'datetime',
        ];
    }
}
