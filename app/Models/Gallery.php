<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'is_active'];

    public function items()
    {
        return $this->hasMany(GalleryItem::class)->orderBy('order_weight', 'asc');
    }

    /**
     * Scope to get the currently active gallery for display.
     */
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }
}
