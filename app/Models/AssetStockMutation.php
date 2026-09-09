<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class AssetStockMutation extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'asset_id',
        'hotel_id',
        'type',
        'quantity',
        'reason',
        'notes',
        'unit_cost',
        'reference',
        'user_id',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'quantity' => 'integer',
    ];

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';

    public static function reasons(): array
    {
        return [
            'purchase' => 'Pembelian Baru',
            'donation' => 'Hibah/Donasi',
            'transfer_in' => 'Transfer Masuk',
            'transfer_out' => 'Transfer Keluar',
            'broken' => 'Rusak/Tidak Bisa Diperbaiki',
            'disposed' => 'Dibuang',
            'lost' => 'Hilang',
            'adjustment' => 'Penyesuaian Stok',
        ];
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
