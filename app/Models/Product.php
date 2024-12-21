<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Shop;
use App\Models\SecondaryCategory;
use App\Models\Image;
use App\Models\Stock;

class Product extends Model
{
    use HasFactory;

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(SecondaryCategory::class, 'secondary_category_id');
    }

    #productsテーブルのカラム名('image1')と被ってはいけないため、imageFirstにしてある
        public function imageFirst()
    {
        return $this->belongsTo(image::class, 'image1', 'id');
    }

    public function stock()
    {
        return $this->hasMany(Stock::class);
    }
}
