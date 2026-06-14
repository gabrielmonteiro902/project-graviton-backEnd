<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrbitConnection extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'name',
        'primary_repository_id',
        'secondary_repository_id',
    ];

    public function primaryRepository(): BelongsTo
    {
        return $this->belongsTo(Repository::class, 'primary_repository_id');
    }

    public function secondaryRepository(): BelongsTo
    {
        return $this->belongsTo(Repository::class, 'secondary_repository_id');
    }
}
