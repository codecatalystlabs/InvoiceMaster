<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmailAttachment extends Model
{
    protected $fillable = ['email_id', 'filename', 'filepath', 'filesize', 'mime_type'];

    public function email(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class, 'email_id');
    }

    public function absolutePath(): string
    {
        return Storage::disk('local')->path($this->filepath);
    }
}
