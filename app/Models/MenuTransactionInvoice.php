<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MenuTransactionInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            if (empty($model->invoice_number)) {
                $model->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        do {
            $candidate = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (self::where('invoice_number', $candidate)->exists());

        return $candidate;
    }

    public function transaction()
    {
        return $this->belongsTo(MenuTransaction::class, 'menu_transaction_id');
    }

    public function tenant()
    {
        return $this->belongsTo(MenuTenant::class, 'menu_tenant_id');
    }
}
