<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceEntry extends Model
{
    protected $fillable = ['merchant_id', 'type', 'description', 'amount', 'date'];

    protected $casts = ['date' => 'date'];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
