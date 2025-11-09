<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageAttribute($value)
    {
        $imagePath = config('custom.image_path');

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return $value
            ? $imagePath . '/' . ltrim($value, '/')
            : null;
    }
}
