<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineUser extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'line_id',
        'line_display_name',
        'line_picture_url',
        'line_status_message',
        'access_token',
        'refresh_token',
        'token_expiry',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'token_expiry' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
