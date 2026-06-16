<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafeGitRepository extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'local_path',
        'remote_name',
        'remote_url',
        'default_branch',
        'current_branch',
        'last_checked_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(SafeGitOperationLog::class, 'repository_id');
    }
}
