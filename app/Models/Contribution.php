<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contribution extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'repository_id',
        'contributor_id',
        'commits_count',
        'additions',
        'deletions',
    ];

    protected $casts = [
        'commits_count' => 'integer',
        'additions'     => 'integer',
        'deletions'     => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function contributor(): BelongsTo
    {
        return $this->belongsTo(Contributor::class);
    }

    // G = C / T  — índice gravitacional do colaborador no repositório
    public function getGravityAttribute(): float
    {
        $total = $this->repository->contributions()->sum('commits_count');

        if ($total === 0) {
            return 0.0;
        }

        return round($this->commits_count / $total, 4);
    }
}
