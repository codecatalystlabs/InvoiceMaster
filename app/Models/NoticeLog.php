<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class NoticeLog extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'channel', 'to', 'subject', 'body', 'status',
        'reference_type', 'reference_id', 'error_message',
    ];
}
