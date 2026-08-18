<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatementLine extends Model
{
    protected $fillable = [
        'statement_import_id', 'line_date', 'description', 'reference', 'amount',
        'match_type', 'match_id', 'status',
    ];

    protected function casts(): array
    {
        return ['line_date' => 'date', 'amount' => 'float'];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(StatementImport::class, 'statement_import_id');
    }
}
