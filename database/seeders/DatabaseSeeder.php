<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryTypeEnum;
use App\Enums\WalletColorEnum;
use App\Enums\WalletIconEnum;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi data sistem dasar aplikasi Bank BINI (peran, kategori transaksi standar, dan akun login) tanpa data kantong/transaksi awal.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'user-list',
            'user-tambah',
            'user-edit',
            'user-hapus',
            'wallet-manage',
            'transaction-manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        $adminRole->syncPermissions($permissions);

        $memberRole = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
        $memberRole->syncPermissions([
            'wallet-manage',
            'transaction-manage',
        ]);

        $this->seedDefaultCategories();

        if (app()->isProduction()) {
            return;
        }

        $defaultPassword = (string) env('SEED_DEFAULT_PASSWORD', '123');

        $mainUser = User::firstOrCreate(
            ['email' => 'admin@app.com'],
            [
                'name' => 'Suami',
                'password' => bcrypt($defaultPassword),
                'phone' => '081234567890',
                'jabatan' => 'Suami / Kepala Keluarga',
                'status' => 'active',
            ]
        );
        $mainUser->syncRoles(['administrator']);

        $husbandUser = User::firstOrCreate(
            ['email' => 'suami@bankbini.id'],
            [
                'name' => 'Suami Tercinta',
                'password' => bcrypt($defaultPassword),
                'phone' => '081234567891',
                'jabatan' => 'Suami',
                'status' => 'active',
            ]
        );
        $husbandUser->syncRoles(['administrator']);

        $wifeUser = User::firstOrCreate(
            ['email' => 'istri@bankbini.id'],
            [
                'name' => 'Istri Tercinta',
                'password' => bcrypt($defaultPassword),
                'phone' => '081298765432',
                'jabatan' => 'Istri / Menteri Keuangan Keluarga',
                'status' => 'active',
            ]
        );
        $wifeUser->syncRoles(['administrator']);
    }

    /**
     * Mengisi daftar kategori pemasukan dan pengeluaran bawaan sistem.
     */
    public function seedDefaultCategories(): void
    {
        $incomeCategories = [
            ['name' => 'Gaji', 'icon' => WalletIconEnum::BRIEFCASE->value, 'color' => WalletColorEnum::EMERALD->value],
            ['name' => 'Bonus', 'icon' => WalletIconEnum::GIFT->value, 'color' => WalletColorEnum::AMBER->value],
            ['name' => 'Bisnis', 'icon' => WalletIconEnum::COIN->value, 'color' => WalletColorEnum::TEAL->value],
            ['name' => 'Investasi', 'icon' => WalletIconEnum::PIG_MONEY->value, 'color' => WalletColorEnum::SKY->value],
            ['name' => 'Hadiah', 'icon' => WalletIconEnum::HEART->value, 'color' => WalletColorEnum::ROSE->value],
            ['name' => 'Lainnya', 'icon' => WalletIconEnum::WALLET->value, 'color' => WalletColorEnum::VIOLET->value],
        ];

        foreach ($incomeCategories as $cat) {
            TransactionCategory::updateOrCreate(
                [
                    'user_id' => null,
                    'name' => $cat['name'],
                    'type' => CategoryTypeEnum::INCOME->value,
                ],
                [
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'is_default' => true,
                ]
            );
        }

        $expenseCategories = [
            ['name' => 'Makanan', 'icon' => WalletIconEnum::TOOLS_KITCHEN_2->value, 'color' => WalletColorEnum::ORANGE->value],
            ['name' => 'Transportasi', 'icon' => WalletIconEnum::CAR->value, 'color' => WalletColorEnum::SKY->value],
            ['name' => 'Belanja', 'icon' => WalletIconEnum::SHOPPING_BAG->value, 'color' => WalletColorEnum::ROSE->value],
            ['name' => 'Tagihan', 'icon' => WalletIconEnum::RECEIPT->value, 'color' => WalletColorEnum::AMBER->value],
            ['name' => 'Kesehatan', 'icon' => WalletIconEnum::FIRST_AID_KIT->value, 'color' => WalletColorEnum::EMERALD->value],
            ['name' => 'Hiburan', 'icon' => WalletIconEnum::DEVICE_GAMEPAD_2->value, 'color' => WalletColorEnum::VIOLET->value],
            ['name' => 'Pendidikan', 'icon' => WalletIconEnum::SCHOOL->value, 'color' => WalletColorEnum::TEAL->value],
            ['name' => 'Rumah tangga', 'icon' => WalletIconEnum::HOME->value, 'color' => WalletColorEnum::PINK->value],
            ['name' => 'Lainnya', 'icon' => WalletIconEnum::SPARKLES->value, 'color' => WalletColorEnum::AMBER->value],
        ];

        foreach ($expenseCategories as $cat) {
            TransactionCategory::updateOrCreate(
                [
                    'user_id' => null,
                    'name' => $cat['name'],
                    'type' => CategoryTypeEnum::EXPENSE->value,
                ],
                [
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'is_default' => true,
                ]
            );
        }
    }
}
