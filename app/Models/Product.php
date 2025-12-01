<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $product->slug = static::generateUniqueSlug($product->name);
        });
    }

    protected static function generateUniqueSlug($name)
    {
        $slug         = Str::slug($name);
        $originalSlug = $slug;

        $allSlugs = Product::where('slug', 'LIKE', "{$slug}%")
            ->pluck('slug')
            ->toArray();

        if (! in_array($slug, $allSlugs)) {
            return $slug;
        }

        $i = 1;
        while (in_array("{$originalSlug}-{$i}", $allSlugs)) {
            $i++;
        }

        return "{$originalSlug}-{$i}";
    }

    /**
     * Update product rating and number of reviews
     */
    public function updateRating(): void
    {
        $approvedReviews = $this->reviews()->where('is_approved', true);

        $this->num_reviews = $approvedReviews->count();
        $this->rating      = $approvedReviews->avg('rating') ?? 0;

        $this->save();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function mainImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->orderBy('id', 'desc');
    }
}
