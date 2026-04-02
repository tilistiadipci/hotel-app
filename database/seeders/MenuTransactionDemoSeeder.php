<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuTenant;
use App\Models\MenuTransaction;
use App\Models\MenuTransactionDetail;
use App\Models\Player;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MenuTransactionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::query()->value('id');

        $players = Player::query()
            ->where('is_active', 1)
            ->orderBy('id')
            ->take(3)
            ->get();

        if ($players->count() < 3) {
            $this->call(PlayerSeeder::class);
            $players = Player::query()
                ->where('is_active', 1)
                ->orderBy('id')
                ->take(3)
                ->get();
        }

        $foodTenant = $this->upsertTenant(
            slug: 'sky-bistro',
            name: 'Sky Bistro',
            description: 'Restaurant tenant for food and beverages.',
            location: 'Lobby Floor',
            serviceCharge: 15000,
            sortOrder: 10,
        );

        $spaTenant = $this->upsertTenant(
            slug: 'serenity-spa',
            name: 'Serenity Spa',
            description: 'Spa tenant for massage and wellness services.',
            location: 'Level 2',
            serviceCharge: 25000,
            sortOrder: 20,
        );

        $shopTenant = $this->upsertTenant(
            slug: 'gift-corner',
            name: 'Gift Corner',
            description: 'Hotel merchandise and souvenir shop.',
            location: 'Lobby Floor',
            serviceCharge: 5000,
            sortOrder: 30,
        );

        $foodCategory = $this->upsertCategory($foodTenant, 'Main Course', 1);
        $spaCategory = $this->upsertCategory($spaTenant, 'Massage', 1);
        $shopCategory = $this->upsertCategory($shopTenant, 'Merchandise', 1);

        $foodItem = $this->upsertItem(
            tenant: $foodTenant,
            category: $foodCategory,
            name: 'Nasi Goreng Special',
            price: 85000,
            description: 'Fried rice with prawn crackers and sunny side up egg.',
            preparationTime: 20,
            sortOrder: 1,
        );

        $spaItem = $this->upsertItem(
            tenant: $spaTenant,
            category: $spaCategory,
            name: 'Balinese Massage 60 Min',
            price: 250000,
            description: 'Traditional full body massage for 60 minutes.',
            preparationTime: 60,
            sortOrder: 1,
        );

        $shopItem = $this->upsertItem(
            tenant: $shopTenant,
            category: $shopCategory,
            name: 'Hotel T-Shirt',
            price: 120000,
            description: 'Cotton hotel branded t-shirt.',
            preparationTime: null,
            sortOrder: 1,
        );

        $this->upsertTransaction(
            uuid: '11111111-1111-1111-1111-111111111111',
            tenant: $foodTenant,
            player: $players[0],
            guestName: 'Andi Saputra',
            paymentMethod: 'bill',
            paymentStatus: 'pending',
            status: 'ordered',
            paidAt: null,
            detailStatus: 'ordered',
            userId: $userId,
            createdAt: Carbon::now()->subHours(3),
            lines: [
                [
                    'item' => $foodItem,
                    'quantity' => 2,
                    'price' => 85000,
                    'notes' => 'Pedas sedang, tanpa acar.',
                ],
            ],
        );

        $this->upsertTransaction(
            uuid: '22222222-2222-2222-2222-222222222222',
            tenant: $spaTenant,
            player: $players[1],
            guestName: 'Maya Lestari',
            paymentMethod: 'qris',
            paymentStatus: 'paid',
            status: 'processing',
            paidAt: Carbon::now()->subHours(2),
            detailStatus: 'prepared',
            userId: $userId,
            createdAt: Carbon::now()->subHours(2)->subMinutes(15),
            lines: [
                [
                    'item' => $spaItem,
                    'quantity' => 1,
                    'price' => 250000,
                    'notes' => 'Therapist wanita, jadwal 19:00.',
                ],
            ],
        );

        $this->upsertTransaction(
            uuid: '33333333-3333-3333-3333-333333333333',
            tenant: $shopTenant,
            player: $players[2],
            guestName: 'Budi Hartono',
            paymentMethod: 'qris',
            paymentStatus: 'paid',
            status: 'completed',
            paidAt: Carbon::now()->subHour(),
            detailStatus: 'delivered',
            userId: $userId,
            createdAt: Carbon::now()->subHour()->subMinutes(10),
            lines: [
                [
                    'item' => $shopItem,
                    'quantity' => 1,
                    'price' => 120000,
                    'notes' => 'Ukuran L.',
                ],
            ],
        );
    }

    private function upsertTenant(string $slug, string $name, string $description, string $location, float $serviceCharge, int $sortOrder): MenuTenant
    {
        return MenuTenant::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'uuid' => MenuTenant::query()->where('slug', $slug)->value('uuid') ?: (string) Str::uuid(),
                'name' => $name,
                'description' => $description,
                'location' => $location,
                'service_charge' => $serviceCharge,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]
        );
    }

    private function upsertCategory(MenuTenant $tenant, string $name, int $sortOrder): MenuCategory
    {
        $slug = Str::slug($name);

        return MenuCategory::query()->updateOrCreate(
            [
                'menu_tenant_id' => $tenant->id,
                'slug' => $slug,
            ],
            [
                'uuid' => MenuCategory::query()
                    ->where('menu_tenant_id', $tenant->id)
                    ->where('slug', $slug)
                    ->value('uuid') ?: (string) Str::uuid(),
                'name' => $name,
                'description' => $name . ' category for ' . $tenant->name . '.',
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]
        );
    }

    private function upsertItem(
        MenuTenant $tenant,
        MenuCategory $category,
        string $name,
        float $price,
        string $description,
        ?int $preparationTime,
        int $sortOrder
    ): MenuItem {
        return MenuItem::query()->updateOrCreate(
            [
                'menu_tenant_id' => $tenant->id,
                'name' => $name,
            ],
            [
                'uuid' => MenuItem::query()
                    ->where('menu_tenant_id', $tenant->id)
                    ->where('name', $name)
                    ->value('uuid') ?: (string) Str::uuid(),
                'category_id' => $category->id,
                'description' => $description,
                'price' => $price,
                'discount_price' => null,
                'is_available' => true,
                'sort_order' => $sortOrder,
                'preparation_time' => $preparationTime,
                'image_id' => null,
            ]
        );
    }

    private function upsertTransaction(
        string $uuid,
        MenuTenant $tenant,
        Player $player,
        string $guestName,
        string $paymentMethod,
        string $paymentStatus,
        string $status,
        ?Carbon $paidAt,
        string $detailStatus,
        ?int $userId,
        Carbon $createdAt,
        array $lines
    ): void {
        $totals = $this->calculateTotals($tenant, $lines);

        $transaction = MenuTransaction::query()->firstOrNew([
            'uuid' => $uuid,
        ]);

        $transaction->fill([
            'menu_tenant_id' => $tenant->id,
            'player_id' => $player->id,
            'guest_name' => $guestName,
            'total_amount' => $totals['total_amount'],
            'tax_amount' => $totals['tax_amount'],
            'service_amount' => $totals['service_amount'],
            'grand_total' => $totals['grand_total'],
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'status' => $status,
            'paid_at' => $paidAt,
            'created_by' => $userId,
            'updated_by' => $userId,
            'processed_by' => in_array($status, ['processing', 'completed'], true) ? $userId : null,
            'completed_by' => $status === 'completed' ? $userId : null,
        ]);

        if (!$transaction->exists) {
            $transaction->created_at = $createdAt;
        }

        $transaction->save();

        foreach ($lines as $index => $line) {
            /** @var MenuItem $item */
            $item = $line['item'];
            $subtotal = $line['price'] * $line['quantity'];

            MenuTransactionDetail::query()->updateOrCreate(
                [
                    'menu_transaction_id' => $transaction->id,
                    'menu_id' => $item->id,
                ],
                [
                    'menu_tenant_id' => $tenant->id,
                    'category_id' => $item->category_id,
                    'menu_name' => $item->name,
                    'price' => $line['price'],
                    'quantity' => $line['quantity'],
                    'subtotal' => $subtotal,
                    'notes' => $line['notes'],
                    'status' => $detailStatus,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $createdAt->copy()->addMinutes($index),
                ]
            );
        }

        if ($transaction->invoice) {
            $transaction->invoice()->update([
                'menu_tenant_id' => $tenant->id,
                'updated_by' => $userId,
                'created_at' => $createdAt,
            ]);
        }
    }

    private function calculateTotals(MenuTenant $tenant, array $lines): array
    {
        $totalAmount = collect($lines)->sum(function ($line) {
            return $line['price'] * $line['quantity'];
        });

        $taxAmount = round($totalAmount * 0.10, 2);
        $serviceAmount = (float) ($tenant->service_charge ?? 0);

        return [
            'total_amount' => $totalAmount,
            'tax_amount' => $taxAmount,
            'service_amount' => $serviceAmount,
            'grand_total' => $totalAmount + $taxAmount + $serviceAmount,
        ];
    }
}
