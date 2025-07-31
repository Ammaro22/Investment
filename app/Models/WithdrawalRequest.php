<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class WithdrawalRequest extends Model {
    use HasFactory;
    protected $fillable = [
        'user_id', 'amount', 'method', 'method_details', 'status',
        'admin_notes', 'transaction_reference', 'approved_at', 'processed_at'
    ];

    protected $casts = [
        'method_details' => 'array',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
