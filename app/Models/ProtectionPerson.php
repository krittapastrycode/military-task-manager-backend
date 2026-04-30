<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProtectionPerson extends Model
{
    protected $table = 'protection_persons';

    protected $fillable = ['category', 'name', 'order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
