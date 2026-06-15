<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Admin extends Authenticatable implements JWTSubject
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name_admin',
        'email_admin',
        'password_admin',
        'tenant_id',
        'github_id',
        'avatar_url',
        'github_token',
    ];

    protected $hidden = [
        'password_admin',
        'github_token',
    ];

    protected $casts = [
        // Token OAuth do GitHub criptografado em repouso (usa APP_KEY).
        'github_token' => 'encrypted',
        'github_id'    => 'integer',
    ];

    // JWT exige o campo "password" para o guard. Pode ser null em contas OAuth.
    public function getAuthPassword(): ?string
    {
        return $this->password_admin;
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'tenant_id' => $this->tenant_id,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
