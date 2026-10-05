<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
])]
class DeployedApp extends Model
{
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
