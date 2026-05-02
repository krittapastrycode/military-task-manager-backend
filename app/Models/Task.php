<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'due_date'      => 'date',
            'completed'     => 'boolean',
            'completed_at'  => 'datetime',
            'reminder_sent' => 'boolean',
            'content'       => 'array',
            'meta'          => 'array',
        ];
    }

    // Always store deadline_at as Asia/Bangkok local time so the value
    // in MySQL is unambiguous, and always return it as a Bangkok Carbon.
    protected function deadlineAt(): Attribute
    {
        return Attribute::make(
            get: fn($v) => $v ? Carbon::createFromFormat('Y-m-d H:i:s', $v, 'Asia/Bangkok') : null,
            set: fn($v) => $v ? Carbon::parse($v)->setTimezone('Asia/Bangkok')->format('Y-m-d H:i:s') : null,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TaskGroup::class, 'group_id');
    }
}
