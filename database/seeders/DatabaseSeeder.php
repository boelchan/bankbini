<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryTypeEnum;
use App\Enums\WalletColorEnum;
use App\Enums\WalletIconEnum;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\TransferService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi data awal aplikasi Bank BINI (peran, kategori transaksi, akun utama, dan kantong contoh).
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

        // Akun Utama (Istri Tercinta / Administrator)
        $mainUser = User::updateOrCreate(
            ['email' => 'admin@app.com'],
            [
                'name' => 'Bunda Sayang',
                'password' => bcrypt('123'),
                'phone' => '081234567890',
                'jabatan' => 'Menteri Keuangan Keluarga',
                'status' => 'active',
            ]
        );
        $mainUser->syncRoles(['administrator']);

        $wifeUser = User::updateOrCreate(
            ['email' => 'istri@bankbini.id'],
            [
                'name' => 'Istri Tercinta',
                'password' => bcrypt('123'),
                'phone' => '081298765432',
                'jabatan' => 'Pemilik Kantong Utama',
                'status' => 'active',
            ]
        );
        $wifeUser->syncRoles(['administrator']);

        $this->seedSampleWalletsAndTransactions($mainUser);
        $this->seedSampleWalletsAndTransactions($wifeUser);
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

    /**
     * Membuat 4 kantong contoh dengan total saldo Rp15.000.000 melalui service transaksi resmi.
     * - Kantong Utama: Rp5.000.000
     * - Kantong Kebutuhan: Rp3.000.000
     * - Kantong Tabungan: Rp5.000.000
     * - Kantong Liburan: Rp2.000.000
     */
    private function seedSampleWalletsAndTransactions(User $user): void
    {
        if ($user->wallets()->withTrashed()->exists()) {
            return;
        }

        $walletService = app(WalletService::class);
        $transactionService = app(TransactionService::class);
        $transferService = app(TransferService::class);

        $catGaji = TransactionCategory::where('type', 'income')->where('name', 'Gaji')->first();
        $catBonus = TransactionCategory::where('type', 'income')->where('name', 'Bonus')->first();
        $catHadiah = TransactionCategory::where('type', 'income')->where('name', 'Hadiah')->first();
        $catMakanan = TransactionCategory::where('type', 'expense')->where('name', 'Makanan')->first();
        $catBelanja = TransactionCategory::where('type', 'expense')->where('name', 'Belanja')->first();
        $catRumah = TransactionCategory::where('type', 'expense')->where('name', 'Rumah tangga')->first();
        $catTagihan = TransactionCategory::where('type', 'expense')->where('name', 'Tagihan')->first();

        // 1. Buat 4 kantong dengan saldo awal 0, lalu isi melalui transaksi agar histori bulanan hidup
        $kantongUtama = $walletService->createWallet([
            'user_id' => $user->id,
            'name' => 'Kantong Utama',
            'description' => 'Dompet utama penerimaan gaji dan pusat pembagian pos anggaran bulanan.',
            'icon' => WalletIconEnum::WALLET->value,
            'color' => WalletColorEnum::ROSE->value,
            'initial_balance' => 0,
            'is_primary' => true,
        ]);

        $kantongKebutuhan = $walletService->createWallet([
            'user_id' => $user->id,
            'name' => 'Kantong Kebutuhan',
            'description' => 'Kebutuhan dapur harian, belanja sayur, listrik, dan perlengkapan rumah.',
            'icon' => WalletIconEnum::TOOLS_KITCHEN_2->value,
            'color' => WalletColorEnum::EMERALD->value,
            'initial_balance' => 0,
        ]);

        $kantongTabungan = $walletService->createWallet([
            'user_id' => $user->id,
            'name' => 'Kantong Tabungan',
            'description' => 'Tabungan masa depan keluarga dan dana cadangan emas.',
            'icon' => WalletIconEnum::PIG_MONEY->value,
            'color' => WalletColorEnum::SKY->value,
            'initial_balance' => 0,
        ]);

        $kantongLiburan = $walletService->createWallet([
            'user_id' => $user->id,
            'name' => 'Kantong Liburan',
            'description' => 'Dana jalan-jalan akhir pekan, kuliner bersama suami, dan me-time istri.',
            'icon' => WalletIconEnum::PLANE_TILT->value,
            'color' => WalletColorEnum::AMBER->value,
            'initial_balance' => 0,
        ]);

        $now = Carbon::now();

        // Bulan lalu: Pemasukan Rp10.000.000, Pengeluaran Rp3.000.000 (Sisa Rp7.000.000)
        $transactionService->recordIncome([
            'user_id' => $user->id,
            'wallet_id' => $kantongUtama->id,
            'category_id' => $catGaji?->id,
            'amount' => 10000000,
            'description' => 'Gaji Bulanan & Uang Belanja Suami (Bulan Lalu)',
            'notes' => 'Transfer rutin awal bulan.',
            'transaction_date' => $now->copy()->subMonth()->startOfMonth()->addDays(1)->setTime(9, 0),
        ]);

        $transferService->executeTransfer([
            'user_id' => $user->id,
            'source_wallet_id' => $kantongUtama->id,
            'destination_wallet_id' => $kantongTabungan->id,
            'amount' => 3000000,
            'description' => 'Sisihkan tabungan rutin bulan lalu',
            'transaction_date' => $now->copy()->subMonth()->startOfMonth()->addDays(2)->setTime(10, 15),
        ]);

        $transactionService->recordExpense([
            'user_id' => $user->id,
            'wallet_id' => $kantongUtama->id,
            'category_id' => $catRumah?->id,
            'amount' => 3000000,
            'description' => 'Belanja bulanan supermarket dan kebutuhan rumah',
            'transaction_date' => $now->copy()->subMonth()->startOfMonth()->addDays(5)->setTime(16, 30),
        ]);
        // Setelah bulan lalu: Utama = 4.000.000, Tabungan = 3.000.000

        // Bulan ini:
        // 1. Gaji Masuk ke Kantong Utama +Rp9.500.000 (Utama = 13.500.000)
        $transactionService->recordIncome([
            'user_id' => $user->id,
            'wallet_id' => $kantongUtama->id,
            'category_id' => $catGaji?->id,
            'amount' => 9500000,
            'description' => 'Pemasukan Gaji & Uang Dapur Bulan Ini',
            'notes' => 'Alhamdulillah rezeki bulan ini lancar.',
            'transaction_date' => $now->copy()->startOfMonth()->addHours(8),
        ]);

        // 2. Hadiah / Bonus ke Kantong Liburan +Rp1.000.000 (Liburan = 1.000.000)
        $transactionService->recordIncome([
            'user_id' => $user->id,
            'wallet_id' => $kantongLiburan->id,
            'category_id' => $catHadiah?->id,
            'amount' => 1000000,
            'description' => 'Hadiah kejutan manis dari suami',
            'notes' => 'Tambahan dana jalan-jalan.',
            'transaction_date' => $now->copy()->startOfMonth()->addDays(1)->setTime(11, 20),
        ]);

        // 3. Transfer dari Kantong Utama ke Kantong Kebutuhan Rp4.200.000 (Utama = 9.300.000, Kebutuhan = 4.200.000)
        $transferService->executeTransfer([
            'user_id' => $user->id,
            'source_wallet_id' => $kantongUtama->id,
            'destination_wallet_id' => $kantongKebutuhan->id,
            'amount' => 4200000,
            'description' => 'Alokasi anggaran belanja dapur & tagihan bulan ini',
            'transaction_date' => $now->copy()->startOfMonth()->addDays(1)->setTime(13, 0),
        ]);

        // 4. Transfer dari Kantong Utama ke Kantong Tabungan Rp2.000.000 (Utama = 7.300.000, Tabungan = 5.000.000)
        $transferService->executeTransfer([
            'user_id' => $user->id,
            'source_wallet_id' => $kantongUtama->id,
            'destination_wallet_id' => $kantongTabungan->id,
            'amount' => 2000000,
            'description' => 'Top up tabungan keluarga bulan ini',
            'transaction_date' => $now->copy()->startOfMonth()->addDays(1)->setTime(13, 15),
        ]);

        // 5. Transfer dari Kantong Utama ke Kantong Liburan Rp1.500.000 (Utama = 5.800.000, Liburan = 2.500.000)
        $transferService->executeTransfer([
            'user_id' => $user->id,
            'source_wallet_id' => $kantongUtama->id,
            'destination_wallet_id' => $kantongLiburan->id,
            'amount' => 1500000,
            'description' => 'Sisihkan dana liburan akhir pekan',
            'transaction_date' => $now->copy()->startOfMonth()->addDays(2)->setTime(9, 45),
        ]);

        // 6. Pengeluaran dari Kantong Utama Rp800.000 -> Utama jadi Rp5.000.000
        $transactionService->recordExpense([
            'user_id' => $user->id,
            'wallet_id' => $kantongUtama->id,
            'category_id' => $catBelanja?->id,
            'amount' => 800000,
            'description' => 'Belanja skincare & perawatan diri',
            'notes' => 'Paket serum dan pelembap wajah.',
            'transaction_date' => $now->copy()->startOfMonth()->addDays(2)->setTime(15, 10),
        ]);

        // 7. Pengeluaran dari Kantong Kebutuhan Rp750.000 & Rp450.000 -> Kebutuhan jadi Rp3.000.000
        $transactionService->recordExpense([
            'user_id' => $user->id,
            'wallet_id' => $kantongKebutuhan->id,
            'category_id' => $catMakanan?->id,
            'amount' => 750000,
            'description' => 'Belanja bahan masakan segar & buah mingguan',
            'transaction_date' => $now->copy()->startOfMonth()->addDays(3)->setTime(8, 30),
        ]);

        $transactionService->recordExpense([
            'user_id' => $user->id,
            'wallet_id' => $kantongKebutuhan->id,
            'category_id' => $catTagihan?->id,
            'amount' => 450000,
            'description' => 'Token listrik rumah dan kuota internet Wi-Fi',
            'transaction_date' => $now->copy()->startOfMonth()->addDays(3)->setTime(19, 0),
        ]);

        // 8. Pengeluaran dari Kantong Liburan Rp500.000 -> Liburan jadi Rp2.000.000
        $transactionService->recordExpense([
            'user_id' => $user->id,
            'wallet_id' => $kantongLiburan->id,
            'category_id' => $catMakanan?->id,
            'amount' => 500000,
            'description' => 'Makan malam romantis akhir pekan berdua',
            'transaction_date' => $now->copy()->startOfMonth()->addDays(4)->setTime(20, 0),
        ]);
    }
}
