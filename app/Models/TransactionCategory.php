<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class TransactionCategory extends Model
{
    use BelongsToHotel;

    protected $fillable = ['hotel_id', 'name', 'type', 'is_active'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }
}
