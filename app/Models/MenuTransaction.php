<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\MenuTransactionInvoice;

class MenuTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::created(function (self $model) {
            // Generate invoice immediately after transaksi dibuat.
            MenuTransactionInvoice::create([
                'menu_transaction_id' => $model->id,
                'menu_tenant_id' => $model->menu_tenant_id,
                'uuid' => Str::uuid()->toString(),
                'invoice_number' => MenuTransactionInvoice::generateInvoiceNumber(),
                'created_by' => $model->created_by,
            ]);
        });
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function tenant()
    {
        return $this->belongsTo(MenuTenant::class, 'menu_tenant_id');
    }

    public function details()
    {
        return $this->hasMany(MenuTransactionDetail::class, 'menu_transaction_id');
    }

    public function invoice()
    {
        return $this->hasOne(MenuTransactionInvoice::class, 'menu_transaction_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
