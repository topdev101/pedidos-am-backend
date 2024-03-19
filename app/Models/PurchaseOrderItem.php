<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrderItem extends Model
{
    protected $guarded = [];

    protected $with = ['images', 'category'];

    protected $appends = [
        'received_quantity',
    ];

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function purchase_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function purchase_items(){
        return $this->hasMany(PurchaseItem::class, 'purchase_order_item_id');
    }

    public function getReceivedQuantityAttribute() {
        return $this->purchase_items()->sum('quantity');
    }
}
