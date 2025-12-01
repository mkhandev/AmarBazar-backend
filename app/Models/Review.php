<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Automatically update product rating whenever a review is created/updated/deleted
    protected static function booted()
    {
        static::created(function ($review) {
            $review->product->updateRating();
        });

        static::updated(function ($review) {
            $review->product->updateRating();
        });

        static::deleted(function ($review) {
            $review->product->updateRating();
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
