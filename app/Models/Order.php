<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $guarded = ['id'];

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . (string) Str::uuid();
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
