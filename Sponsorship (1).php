<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sponsorship extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'dish_id',
        'title',
        'amount',
        'currency',
        'date',
        'attribution',
        'relationship',
        'note',
        'upgradable',
        'anonymous',
        'transaction_id',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dish(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Dish::class, 'id', 'dish_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function feedback(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Feedback::class, 'sponsorship_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
