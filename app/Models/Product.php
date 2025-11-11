<?php

namespace App\Models;

use App\Models\CoreModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends CoreModel
{
    use SoftDeletes;

    protected $fillable = [
        "name",
        "slug",
        "description",
        "price",
        "stock",
        "active",
        "images",
        "created_by",
        "updated_by",
    ];

    protected $casts = [
        "images" => "array",
        "active" => "boolean",
    ];

    protected static function booted(): void
    {
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name . "-" . Str::random(5));
            }
        });
    }

    /* Computed attributes */
    public function getStatusLabelAttribute(): string
    {
        return $this->active ? "ACTIVE" : "INACTIVE";
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(function ($path) {
                // URL encode the path to handle special characters
                return route("api.v1.products.images.show", ["path" => $path]);
            })
            ->toArray();
    }

    protected $appends = ["status_label", "in_stock", "image_urls"];
}
