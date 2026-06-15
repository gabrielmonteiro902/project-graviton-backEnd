<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    // 'plan' fora do $fillable de propósito: não pode ser mass-assigned via request.
    // É definido server-side (forceFill no registro / fluxo de billing).
    protected $fillable = [
        'id',
        'name',
        'email',
    ];

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }
}
