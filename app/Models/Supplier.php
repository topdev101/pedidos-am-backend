<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Supplier extends Model
{
    protected $guarded = [];

    protected $appends = [
        'display_name',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function purchase_orders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->name}({$this->company->name})"
        );
    }

    public function ordered_items(): HasManyThrough
    {
        return $this->hasManyThrough(PurchaseOrderItem::class, PurchaseOrder::class);
    }

    public function received_items(): HasManyThrough
    {
        return $this->hasManyThrough(PurchaseItem::class, Purchase::class);
    }
}
