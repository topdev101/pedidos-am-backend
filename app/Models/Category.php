<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $guarded = [];

    public function ordered_items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function received_items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
