<?php

use App\Enums\DevicePlatformEnum;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;
    use Interactions;

    public bool $isMobile = false;

    public bool $isActive = false;

    /**
     * Menyiapkan status notifikasi berdasarkan perangkat yang sedang dipakai.
     */
    public function mount(): void
    {
        $platform = $this->detectPlatform();
        $this->isMobile = $platform !== null;

        if (! $this->isMobile || Auth::id() === null) {
            return;
        }

        $this->isActive = FcmToken::query()
            ->where('user_id', Auth::id())
            ->where('platform', $platform?->value)
            ->exists();
    }

    /**
     * Menyimpan token FCM perangkat hp setelah izin notifikasi diberikan.
     */
    public function saveFcmToken(string $token): void
    {
        $platform = $this->detectPlatform();
        if ($platform === null || Auth::id() === null) {
            $this->toast()->error('Notifikasi hanya bisa diaktifkan dari hp.', 'Buka halaman ini di browser hp Anda.')->send();

            return;
        }

        Validator::make(
            ['token' => $token],
            ['token' => ['required', 'string', 'max:512']],
            [
                'token.required' => 'Token notifikasi tidak ditemukan.',
                'token.string' => 'Token notifikasi tidak valid.',
                'token.max' => 'Token notifikasi terlalu panjang.',
            ]
        )->validate();

        FcmToken::query()
            ->where('user_id', Auth::id())
            ->where('platform', $platform->value)
            ->where('token', '!=', $token)
            ->delete();

        FcmToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'user_id' => Auth::id(),
                'platform' => $platform->value,
                'label' => $platform->label(),
            ]
        );

        $this->isActive = true;
        $this->toast()->success('Notifikasi Aktif', 'Notifikasi transaksi akan dikirim ke hp ini.')->send();
    }

    /**
     * Mematikan notifikasi untuk perangkat hp yang sedang dipakai.
     */
    public function disableFcmToken(): void
    {
        if (Auth::id() === null) {
            return;
        }

        $platform = $this->detectPlatform() ?? DevicePlatformEnum::WEB;
        FcmToken::query()
            ->where('user_id', Auth::id())
            ->where('platform', $platform->value)
            ->delete();

        $this->isActive = false;
        $this->toast()->success('Notifikasi Dimatikan', 'Hp ini tidak lagi menerima notifikasi transaksi.')->send();
    }

    /**
     * Menampilkan pesan kegagalan saat browser tidak dapat membuat token FCM.
     */
    public function showActivationError(string $message): void
    {
        $this->toast()->error('Notifikasi Gagal Diaktifkan', Str::limit($message, 120))->send();
    }

    /**
     * Mendeteksi platform hp dari user agent.
     */
    private function detectPlatform(): ?DevicePlatformEnum
    {
        $userAgent = (string) request()->userAgent();

        if (Str::contains($userAgent, ['Android', 'Mobile'])) {
            return DevicePlatformEnum::ANDROID;
        }

        if (Str::contains($userAgent, ['iPhone', 'iPad', 'iPod'])) {
            return DevicePlatformEnum::IOS;
        }

        return null;
    }
};
?>

<div @if (! $isMobile) hidden @endif>
    @if ($isMobile)
        <div class="mt-1 border-t border-slate-100 pt-1" role="menu">
            @if ($isActive)
                <button
                    type="button"
                    role="menuitem"
                    wire:click="disableFcmToken"
                    class="flex w-full cursor-pointer items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-emerald-700 transition-colors hover:bg-emerald-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
                    title="Matikan notifikasi transaksi di hp ini"
                >
                    <x-ti-bell-check class="size-4 text-emerald-600" aria-hidden="true" />
                    Notifikasi HP Aktif
                </button>
            @else
                <button
                    type="button"
                    role="menuitem"
                    x-data
                    x-on:click.prevent="
                        const button = $el;
                        const activate = window.BiniNotifications?.activate;
                        if (typeof activate !== 'function') {
                            $wire.showActivationError('Berkas notifikasi belum siap. Muat ulang halaman ini.');
                            return;
                        }
                        button.disabled = true;
                        activate()
                            .then(token => $wire.saveFcmToken(token))
                            .catch(error => $wire.showActivationError(error.message))
                            .finally(() => { button.disabled = false; });
                    "
                    class="flex w-full cursor-pointer items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:cursor-not-allowed disabled:opacity-60"
                    title="Aktifkan notifikasi transaksi di hp ini"
                >
                    <x-ti-bell class="size-4 text-slate-500" aria-hidden="true" />
                    Aktifkan Notifikasi HP
                </button>
            @endif
        </div>
    @endif
</div>
