<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
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
