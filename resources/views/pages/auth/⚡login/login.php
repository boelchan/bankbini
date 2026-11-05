<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    /**
     * Batas maksimal percobaan login gagal per akun sebelum dikunci.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Batas maksimal percobaan login gagal per IP (mencegah password spraying).
     */
    private const MAX_IP_ATTEMPTS = 15;

    /**
     * Durasi penguncian (detik) setelah melewati batas percobaan.
     */
    private const DECAY_SECONDS = 60;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Arahkan pengguna yang sudah login ke dashboard.
     */
    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    /**
     * Aturan validasi form login.
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'email'    => 'required|string|email|max:255',
            'password' => 'required|string|max:255',
        ];
    }

    /**
     * Pesan validasi dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'email.required'    => 'Alamat email wajib diisi.',
            'email.email'       => 'Format alamat email tidak valid.',
            'email.max'         => 'Alamat email terlalu panjang.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.max'      => 'Kata sandi terlalu panjang.',
        ];
    }

    /**
     * Proses autentikasi pengguna.
     */
    public function authenticate(): void
    {
        $this->email = Str::lower(trim($this->email));
        $this->validate();

        $this->ensureIsNotRateLimited();

        // Kredensial salah: pesan generik agar tidak membocorkan apakah email terdaftar.
        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->recordFailedAttempt();
            $this->reset('password');

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        // Akun nonaktif tidak boleh masuk sama sekali.
        if (Auth::user()->status !== 'active') {
            Auth::logout();

            if (request()->hasSession()) {
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }

            $this->recordFailedAttempt();
            $this->reset('password');

            throw ValidationException::withMessages([
                'email' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
            ]);
        }

        // Bersihkan seluruh limiter saat login berhasil
        $this->limiter()->clear($this->throttleKey());
        $this->limiter()->clear($this->ipThrottleKey());

        // Cegah session fixation jika session aktif.
        if (request()->hasSession()) {
            session()->regenerate();
        }

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Catat percobaan login gagal pada limiter email|ip dan limiter ip.
     */
    protected function recordFailedAttempt(): void
    {
        $this->limiter()->hit($this->throttleKey(), self::DECAY_SECONDS);
        $this->limiter()->hit($this->ipThrottleKey(), self::DECAY_SECONDS);
    }

    /**
     * Pastikan pengguna dan alamat IP tidak melebihi batas percobaan login.
     */
    protected function ensureIsNotRateLimited(): void
    {
        // 1. Cek limit per kombinasi email & IP
        if ($this->limiter()->tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            event(new Lockout(request()));
            $seconds = $this->limiter()->availableIn($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan masuk untuk akun ini. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        // 2. Cek limit per IP address (mencegah credential stuffing / dictionary attack)
        if ($this->limiter()->tooManyAttempts($this->ipThrottleKey(), self::MAX_IP_ATTEMPTS)) {
            event(new Lockout(request()));
            $seconds = $this->limiter()->availableIn($this->ipThrottleKey());

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login dari perangkat/jaringan ini. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }
    }

    /**
     * Ambil instance rate limiter.
     */
    protected function limiter(): RateLimiter
    {
        return app(RateLimiter::class);
    }

    /**
     * Kunci rate limit berdasarkan kombinasi email dan IP.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }

    /**
     * Kunci rate limit berdasarkan IP address saja.
     */
    protected function ipThrottleKey(): string
    {
        return Str::transliterate('login_ip|' . request()->ip());
    }
};
