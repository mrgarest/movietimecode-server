<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UserToken extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'token',
        'expires_at',
        'created_at',
        'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUserId(Builder $query, $id): Builder
    {
        return $query->where('user_id', $id);
    }

    public function scopeType(Builder $query, $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeToken(Builder $query, $token): Builder
    {
        return $query->where('token', $token);
    }
}
