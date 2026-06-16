<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafeGitOperationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'repository_id',
        'operation',
        'command',
        'status',
        'stdout',
        'stderr',
        'exit_code',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(SafeGitRepository::class, 'repository_id');
    }
}
