<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UPTD Puskesmas Pragaan — Layanan Ibu Hamil, Persalinan PONED 24 Jam & Cek HPL</title>

    <link type="image/png" href="{{ asset('favicon/favicon-96x96.png') }}" rel="icon" sizes="96x96" />
    <link type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}" rel="icon" />
    <link href="{{ asset('favicon/favicon.ico') }}" rel="shortcut icon" />
    <link href="{{ asset('favicon/apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <meta name="description" content="Pelayanan Kesehatan Ibu & Anak (KIA) UPTD Puskesmas Pragaan, Kabupaten Sumenep. Pemeriksaan Kehamilan Terpadu, Skrining USG Dokter, Laboratorium Ibu Hamil Satu Atap, Kamar Bersalin PONED 24 Jam, dan Cek HPL Mandiri.">
    <meta name="keywords" content="puskesmas pragaan, layanan kia pragaan, poned pragaan, usg ibu hamil sumenep, cek hpl, hari perkiraan lahir, puskesmas sumenep">
    <meta name="author" content="UPTD Puskesmas Pragaan">
    <link href="{{ url()->current() }}" rel="canonical">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <tallstackui:script />

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #FAF8F4;
            color: #1C2621;
        }
        .font-editorial {
            font-family: 'Fraunces', Georgia, Cambria, 'Times New Roman', serif;
            font-variation-settings: "SOFT" 50, "WONK" 0;
        }
    </style>
</head>

<body class="antialiased min-h-screen flex flex-col selection:bg-[#D7E8DF] selection:text-[#12352A]" x-data="welcomeApp()">

    @php
        $posts = \App\Models\Post::where('publish', 'ya')->latest()->take(6)->get();
        $villages = [
            'Aeng Panas', 'Jaddung', 'Kaduara Timur', 'Karduluk',
            'Larangan Perreng', 'Pakamban Daya', 'Pakamban Laok', 'Pragaan Daya',
            'Pragaan Laok', 'Prenduan', 'Rombasan', 'Sendang',
            'Sentol Daya', 'Sentol Laok',
        ];
    @endphp

    <!-- Pita Informasi Operasional (Tampil pada Tablet & Desktop) -->
    <div class="hidden md:block bg-[#12352A] text-[#E3EDE8] text-xs border-b border-[#1E4B3D]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-2 flex items-center justify-between gap-4">
            <div class="inline-flex items-center gap-2 font-medium">
                <span class="size-2 rounded-full bg-emerald-400"></span>
                <span>Kamar Bersalin & UGD PONED: <strong class="text-white">Siaga 24 Jam Setiap Hari</strong></span>
            </div>
            <div class="flex items-center gap-4 text-[#C5D8CE]">
                <span>Poli KIA & USG: Senin – Sabtu (07.30 – 13.00 WIB)</span>
                <span>&bull;</span>
                <span>Melayani Peserta BPJS / JKN & Umum</span>
            </div>
        </div>
    </div>

    <!-- Navigasi Utama Responsif -->
    <header class="sticky top-0 inset-x-0 z-40 bg-[#FAF8F4]/95 backdrop-blur-xs border-b border-[#E5E0D5]" @keydown.escape.window="mobileMenu = false">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-15 sm:h-17 flex items-center justify-between gap-3">
            <!-- Identitas Puskesmas (Ringkas di Mobile, Lengkap di Desktop) -->
            <a href="#" class="flex items-center gap-2.5 min-w-0 group">
                <div class="size-9 sm:size-10 rounded-xl bg-[#143D30] flex items-center justify-center text-[#F4F1EA] shrink-0">
                    <x-ti-hospital class="size-5" />
                </div>
                <div class="min-w-0">
                    <span class="font-bold text-[#14261F] text-sm sm:text-base tracking-tight block leading-tight truncate">
                        <span class="hidden sm:inline">UPTD </span>Puskesmas Pragaan
                    </span>
                    <span class="text-xs text-[#4D6359] font-medium block truncate">
                        Layanan KIA & PONED 24 Jam
                    </span>
                </div>
            </a>

            <!-- Menu Navigasi Desktop -->
            <nav class="hidden lg:flex items-center gap-7 text-sm font-medium text-[#4A5952]">
                <a href="#keunggulan" class="hover:text-[#143D30] transition-colors">Keunggulan KIA</a>
                <a href="#alur" class="hover:text-[#143D30] transition-colors">Perjalanan 9 Bulan</a>
                <a href="#cek-hpl" class="text-[#143D30] font-semibold hover:text-[#0E2B22] transition-colors">Cek HPL</a>
                @if ($posts->isNotEmpty())
                    <a href="#edukasi" class="hover:text-[#143D30] transition-colors">Edukasi</a>
                @endif
                <a href="#kunjungan" class="hover:text-[#143D30] transition-colors">Panduan Periksa</a>
            </nav>

            <!-- Tombol Aksi & Hamburger Mobile -->
            <div class="flex items-center gap-2 shrink-0">
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-lg bg-[#143D30] hover:bg-[#0F2E24] text-white text-xs sm:text-sm font-semibold transition-colors">
                        <span>Dasbor</span>
                        <x-ti-arrow-right class="size-4" />
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-lg bg-white hover:bg-[#F2EFE8] text-[#143D30] border border-[#D8D1C3] text-xs sm:text-sm font-semibold transition-colors">
                        <x-ti-user class="size-4 text-[#1F5C48] shrink-0" />
                        <span class=" sm:hidden">Masuk</span>
                        <span class="hidden sm:inline">Portal Nakes</span>
                    </a>
                @endauth

                <button type="button"
                        @click="mobileMenu = !mobileMenu"
                        aria-label="Menu Navigasi"
                        class="lg:hidden size-9 rounded-lg bg-white hover:bg-[#F2EFE8] text-[#143D30] border border-[#D8D1C3] flex items-center justify-center transition-colors">
                    <x-ti-menu-2 x-show="!mobileMenu" class="size-5" />
                    <x-ti-x x-show="mobileMenu" x-cloak class="size-5" />
                </button>
            </div>
        </div>

        <!-- Panel Menu Navigasi Mobile -->
        <div x-show="mobileMenu"
             x-cloak
             @click.outside="mobileMenu = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="lg:hidden border-t border-[#E5E0D5] bg-[#FAF8F4] px-4 pt-3 pb-4 shadow-sm space-y-3">
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-[#12352A] text-[#E3EDE8] text-xs">
                <span class="size-2 rounded-full bg-emerald-400 shrink-0"></span>
                <span>PONED Bersalin: <strong class="text-white">Siaga 24 Jam</strong> &bull; Poli KIA: 07.30–13.00</span>
            </div>

            <nav class="grid grid-cols-1 gap-1 text-sm font-medium text-[#1C2621]">
                <a href="#cek-hpl" @click="mobileMenu = false" class="flex items-center justify-between px-3 py-2.5 rounded-lg bg-[#E6EFEA] text-[#143D30] font-semibold">
                    <span>Cek Usia Kehamilan & HPL</span>
                    <x-ti-calendar-event class="size-4" />
                </a>
                <a href="#keunggulan" @click="mobileMenu = false" class="px-3 py-2 rounded-lg hover:bg-[#EFECE4] transition-colors">
                    Keunggulan Layanan KIA
                </a>
                <a href="#alur" @click="mobileMenu = false" class="px-3 py-2 rounded-lg hover:bg-[#EFECE4] transition-colors">
                    Perjalanan 9 Bulan Kehamilan
                </a>
                @if ($posts->isNotEmpty())
                    <a href="#edukasi" @click="mobileMenu = false" class="px-3 py-2 rounded-lg hover:bg-[#EFECE4] transition-colors">
                        Bacaan & Edukasi KIA
                    </a>
                @endif
                <a href="#kunjungan" @click="mobileMenu = false" class="px-3 py-2 rounded-lg hover:bg-[#EFECE4] transition-colors">
                    Jadwal, Syarat & Tanda Bahaya
                </a>
            </nav>
        </div>
    </header>

    <main class="flex-1">

        <!-- HERO EDITORIAL SPLIT: Proposisi Nilai Puskesmas + Kartu Cek HPL Interaktif -->
        <section class="py-10 sm:py-16 px-4 sm:px-6 border-b border-[#E5E0D5]">
            <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-start">

                <!-- Kolom Kiri: Cerita & Keunggulan Nyata Puskesmas Pragaan -->
                <div class="lg:col-span-7 space-y-7">
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#E6EFEA] border border-[#C7DBD0] text-[#143D30] text-xs font-semibold tracking-wide">
                            <x-ti-shield-check class="size-4 text-[#1E5B47]" />
                            <span>PUSKESMAS PONED • KECAMATAN PRAGAAN</span>
                        </div>

                        <h1 class="font-editorial text-3xl sm:text-4xl lg:text-[2.75rem] font-semibold text-[#132820] tracking-tight leading-[1.15]">
                            Pendampingan hangat sejak awal kehamilan, hingga buah hati lahir <span class="italic font-medium text-[#1E5B47]">dengan selamat.</span>
                        </h1>

                        <p class="text-sm sm:text-base text-[#4A5851] leading-relaxed max-w-2xl">
                            Tak perlu jauh ke luar kecamatan untuk memperoleh layanan kehamilan berstandar medis lengkap. UPTD Puskesmas Pragaan menghadirkan pemeriksaan dokter & bidan terlatih, skrining USG, laboratorium ibu hamil satu atap, hingga kamar bersalin siaga 24 jam yang dekat dengan keluarga Anda.
                        </p>
                    </div>

                    <!-- 4 Bukti Layanan Nyata (Editorial Ledger Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-px bg-[#E2DDD1] border border-[#E2DDD1] rounded-xl overflow-hidden">
                        <div class="bg-[#FAF8F4] p-4 sm:p-5 space-y-1">
                            <div class="flex items-center gap-2 text-[#143D30] font-semibold text-sm">
                                <x-ti-stethoscope class="size-4 text-[#1E5B47] shrink-0" />
                                <span>Skrining USG oleh Dokter</span>
                            </div>
                            <p class="text-xs text-[#56635C] leading-relaxed">
                                Evaluasi perkembangan janin, detak jantung, letak plasenta, dan ketepatan HPL pada Trimester 1 & Trimester 3.
                            </p>
                        </div>

                        <div class="bg-[#FAF8F4] p-4 sm:p-5 space-y-1">
                            <div class="flex items-center gap-2 text-[#143D30] font-semibold text-sm">
                                <x-ti-microscope class="size-4 text-[#1E5B47] shrink-0" />
                                <span>Laboratorium Satu Atap</span>
                            </div>
                            <p class="text-xs text-[#56635C] leading-relaxed">
                                Cek kadar Hb (cegah anemia), golongan darah, protein urin, gula darah, dan Triple Eliminasi di hari yang sama.
                            </p>
                        </div>

                        <div class="bg-[#FAF8F4] p-4 sm:p-5 space-y-1">
                            <div class="flex items-center gap-2 text-[#143D30] font-semibold text-sm">
                                <x-ti-heart-handshake class="size-4 text-[#1E5B47] shrink-0" />
                                <span>Gratis Peserta BPJS / JKN</span>
                            </div>
                            <p class="text-xs text-[#56635C] leading-relaxed">
                                Pelayanan publik resmi yang transparan, ramah, dan ditanggung penuh sesuai kepesertaan JKN aktif.
                            </p>
                        </div>

                        <div class="bg-[#FAF8F4] p-4 sm:p-5 space-y-1">
                            <div class="flex items-center gap-2 text-[#143D30] font-semibold text-sm">
                                <x-ti-baby-carriage class="size-4 text-[#1E5B47] shrink-0" />
                                <span>Tuntas Hingga Nifas & Bayi</span>
                            </div>
                            <p class="text-xs text-[#56635C] leading-relaxed">
                                Pemantauan berlanjut pasca melahirkan: kunjungan nifas (KF1–KF4), neonatus (KN1–KN3), dan skrining SHK bayi.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="#keunggulan" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#143D30] hover:bg-[#0F2E24] text-white text-sm font-semibold transition-colors">
                            <span>Mengapa Bersalin di Sini?</span>
                            <x-ti-arrow-right class="size-4" />
                        </a>
                        <a href="#kunjungan" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-white hover:bg-[#F2EFE8] text-[#1C2621] border border-[#D8D1C3] text-sm font-semibold transition-colors">
                            <x-ti-book class="size-4 text-[#B85D43]" />
                            <span>Syarat & Jadwal Periksa</span>
                        </a>
                    </div>
                </div>

                <!-- Kolom Kanan: Kartu Cek Usia Kehamilan & HPL (Tactile Clinical Card) -->
                <div id="cek-hpl" class="lg:col-span-5 scroll-mt-24">
                    <div class="bg-white rounded-2xl border border-[#DDD6C7] shadow-sm overflow-hidden">
                        <!-- Kepala Kartu HPL -->
                        <div class="bg-[#143D30] text-[#F6F4EE] px-6 py-5 border-b border-[#1E4F3F]">
                            <div class="flex items-center justify-between gap-3 mb-1.5">
                                <span class="text-xs font-semibold uppercase tracking-widest text-[#9CC5B5]">Panduan Kehamilan Mandiri</span>
                                <x-ti-calendar-event class="size-5 text-[#9CC5B5]" />
                            </div>
                            <h2 class="font-editorial text-xl sm:text-2xl font-medium text-white">
                                Cek Usia Kehamilan & HPL
                            </h2>
                            <p class="text-xs text-[#C6DCD3] mt-1">
                                Hitung taksiran tanggal persalinan dan ketahui layanan yang tepat untuk fase kehamilan Ibu saat ini.
                            </p>
                        </div>

                        <!-- Isi Formulir & Hasil -->
                        <div class="p-6 space-y-5">
                            <div>
                                <x-date
                                    name="hpht"
                                    label="Hari Pertama Haid Terakhir (HPHT)"
                                    hint="Pilih dari kalender atau ketik langsung (format DD/MM/YYYY)."
                                    placeholder="Contoh: 15/06/{{ date('Y') }}"
                                    format="DD/MM/YYYY"
                                    :min-date="now()->subWeeks(45)->format('Y-m-d')"
                                    :max-date="now()->format('Y-m-d')"
                                    typeable
                                    x-on:select="hphtDate = $event.detail.date || ''"
                                    x-on:clear="hphtDate = ''"
                                    x-on:blur="if (!$event.target.value) hphtDate = ''"
                                />
                            </div>

                            <!-- Tampilan Sebelum Memilih Tanggal -->
                            <div x-show="!hphtDate" class="rounded-xl bg-[#FAF8F4] border border-[#E6E0D3] p-4 space-y-2.5">
                                <div class="text-xs font-semibold text-[#143D30]">
                                    Masukkan tanggal HPHT Ibu untuk melihat:
                                </div>
                                <ul class="space-y-2 text-xs text-[#525E58]">
                                    <li class="flex items-start gap-2">
                                        <span class="size-1.5 rounded-full bg-[#1E5B47] mt-1.5 shrink-0"></span>
                                        <span><strong>Hari Perkiraan Lahir (HPL)</strong> berdasarkan Rumus Naegele (siklus 280 hari).</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="size-1.5 rounded-full bg-[#1E5B47] mt-1.5 shrink-0"></span>
                                        <span><strong>Usia kehamilan berjalan</strong> dalam minggu & hari beserta posisi trimester.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="size-1.5 rounded-full bg-[#B85D43] mt-1.5 shrink-0"></span>
                                        <span><strong>Saran pemeriksaan berikutnya</strong> (USG Dokter / Cek Lab / Persiapan Bersalin) di Puskesmas Pragaan.</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Tampilan Hasil Interaktif -->
                            <div x-show="hphtDate" x-cloak class="space-y-4">
                                <!-- Kotak Utama HPL -->
                                <div class="rounded-xl bg-[#EEF4F0] border border-[#C6DBD0] p-4">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-[#265947] block">
                                        Hari Perkiraan Lahir (HPL)
                                    </span>
                                    <div class="font-editorial text-2xl sm:text-3xl font-semibold text-[#12352A] mt-1" x-text="hphtResult.edd"></div>
                                    <span class="text-xs font-medium text-[#2F6350] block mt-1.5" x-text="hphtResult.countdownText"></span>
                                </div>

                                <!-- Metrik Usia & Visual Garis Waktu 40 Minggu -->
                                <div class="rounded-xl bg-[#FAF8F4] border border-[#E5E0D5] p-4 space-y-3">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <div>
                                            <span class="text-xs text-[#5B6761] block">Usia Kehamilan</span>
                                            <strong class="text-base font-bold text-[#14261F]" x-text="hphtResult.weeks + ' Minggu ' + hphtResult.days + ' Hari'"></strong>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs text-[#5B6761] block">Fase Saat Ini</span>
                                            <strong class="text-sm font-semibold text-[#1E5B47]" x-text="'Trimester ' + hphtResult.trimester + ' (' + hphtResult.trimesterRange + ')'"></strong>
                                        </div>
                                    </div>

                                    <!-- Bar Progres Kehamilan 40 Minggu -->
                                    <div class="space-y-1.5">
                                        <div class="h-2 w-full rounded-full bg-[#E5E0D5] overflow-hidden">
                                            <div class="h-full rounded-full bg-[#1E5B47] transition-all duration-300"
                                                 :style="'width: ' + hphtResult.progressPercent + '%'"></div>
                                        </div>
                                        <div class="flex justify-between text-xs text-[#6B7770]">
                                            <span>0 Mg</span>
                                            <span>TM 1</span>
                                            <span>TM 2</span>
                                            <span>TM 3</span>
                                            <span>40 Mg</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rekomendasi Layanan Puskesmas Sesuai Trimester -->
                                <div class="rounded-xl bg-[#FDF3EE] border border-[#ECCDC0] p-4 flex gap-3 items-start">
                                    <x-ti-info-circle class="size-5 text-[#B85D43] shrink-0 mt-0.5" />
                                    <div class="space-y-1">
                                        <span class="text-xs font-bold text-[#6E301E] block" x-text="hphtResult.adviceTitle"></span>
                                        <p class="text-xs text-[#5E372B] leading-relaxed" x-text="hphtResult.adviceBody"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- SEKSI BENTO EDITORIAL: Keunggulan Pelayanan KIA Puskesmas Pragaan -->
        <section id="keunggulan" class="py-12 sm:py-18 px-4 sm:px-6 scroll-mt-16">
            <div class="max-w-6xl mx-auto space-y-8">
                <div class="max-w-2xl space-y-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#1E5B47]">Nilai Lebih Pelayanan Kami</span>
                    <h2 class="font-editorial text-2xl sm:text-3xl font-semibold text-[#132820] leading-snug">
                        Mengapa ratusan keluarga di Pragaan mempercayakan kehamilan & persalinannya di sini?
                    </h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <!-- Panel Utama (7 Kolom): Kamar Bersalin & PONED 24 Jam -->
                    <div class="lg:col-span-7 bg-[#143D30] text-[#F6F4EE] rounded-2xl p-6 sm:p-9 flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#1F5443] text-[#CBE3D8] text-xs font-semibold">
                                <x-ti-ambulance class="size-4" />
                                Layanan Unggulan PONED 24 Jam
                            </span>

                            <h3 class="font-editorial text-2xl sm:text-3xl font-medium text-white leading-snug">
                                Ketenangan bersalin kapan pun waktunya tiba — siang maupun tengah malam.
                            </h3>

                            <p class="text-sm text-[#C9DDD4] leading-relaxed">
                                Sebagai Puskesmas berstatus <strong class="text-white">PONED (Pelayanan Obstetri Neonatal Emergensi Dasar)</strong>, Kamar Bersalin kami dilengkapi tim dokter, bidan terlatih, serta peralatan penanganan kegawatdaruratan ibu dan bayi baru lahir yang siaga penuh 24 jam setiap hari.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-4 border-t border-[#245948] text-xs sm:text-sm text-[#E4F0EA]">
                            <div class="flex items-center gap-2">
                                <x-ti-circle-check class="size-4 text-emerald-300 shrink-0" />
                                <span>Asuhan Persalinan Aman & IMD</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-ti-circle-check class="size-4 text-emerald-300 shrink-0" />
                                <span>Penanganan Cepat Darurat Maternal</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-ti-circle-check class="size-4 text-emerald-300 shrink-0" />
                                <span>Resusitasi & Perawatan Neonatus</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-ti-circle-check class="size-4 text-emerald-300 shrink-0" />
                                <span>Rujukan RS Terkoordinasi Cepat</span>
                            </div>
                        </div>
                    </div>

                    <!-- Panel Kanan Bertumpuk (5 Kolom) -->
                    <div class="lg:col-span-5 flex flex-col gap-5">
                        <div class="bg-white rounded-2xl border border-[#E2DDD1] p-6 space-y-2.5 flex-1">
                            <div class="size-10 rounded-xl bg-[#EAF2ED] text-[#143D30] flex items-center justify-center">
                                <x-ti-stethoscope class="size-5" />
                            </div>
                            <h3 class="font-editorial text-lg sm:text-xl font-semibold text-[#132820]">
                                Deteksi Risiko Dini & USG Dokter
                            </h3>
                            <p class="text-xs sm:text-sm text-[#525E58] leading-relaxed">
                                Setiap kunjungan ANC memetakan kondisi kesehatan ibu menggunakan standar Skoring Poedji Rochyati & pemeriksaan USG oleh dokter, sehingga faktor risiko seperti preeklamsia, letak sungsang, atau gangguan pertumbuhan janin terdeteksi lebih awal.
                            </p>
                        </div>

                        <div class="bg-white rounded-2xl border border-[#E2DDD1] p-6 space-y-2.5 flex-1">
                            <div class="size-10 rounded-xl bg-[#FDF1EC] text-[#B85D43] flex items-center justify-center">
                                <x-ti-microscope class="size-5" />
                            </div>
                            <h3 class="font-editorial text-lg sm:text-xl font-semibold text-[#132820]">
                                Pencegahan Anemia, KEK & Stunting Sejak Janin
                            </h3>
                            <p class="text-xs sm:text-sm text-[#525E58] leading-relaxed">
                                Integrasi pemeriksaan laboratorium, pengukuran status gizi (LILA), pemberian Tablet Tambah Darah (TTD), serta konseling gizi bagi ibu hamil Kurang Energi Kronis (KEK) demi melahirkan bayi sehat dengan berat badan optimal.
                            </p>
                        </div>
                    </div>

                    <!-- Panel Lebar Penuh (12 Kolom): Jejaring 14 Desa Kecamatan Pragaan -->
                    <div class="lg:col-span-12 bg-[#F2EDE3] border border-[#DDD5C5] rounded-2xl p-6 sm:p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                            <div class="lg:col-span-5 space-y-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#8C4732]">Inovasi Pemantauan Wilayah</span>
                                <h3 class="font-editorial text-xl sm:text-2xl font-semibold text-[#132820]">
                                    Terhubung langsung dengan Bidan Desa di 14 Desa Kecamatan Pragaan
                                </h3>
                                <p class="text-xs sm:text-sm text-[#515C56] leading-relaxed">
                                    Melalui sistem <strong>Kantong Kehamilan Digital</strong>, bidan desa memantau jadwal kontrol dan kesiapsiagaan menjelang HPL setiap ibu hamil di desa tempat tinggalnya.
                                </p>
                            </div>

                            <div class="lg:col-span-7 flex flex-wrap gap-2">
                                @foreach ($villages as $village)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/90 border border-[#DBD3C3] text-xs font-medium text-[#24362E]">
                                        <x-ti-map-pin class="size-3.5 text-[#1E5B47]" />
                                        {{ $village }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEKSI TIMELINE 9 BULAN: Alur Pendampingan Berkelanjutan -->
        <section id="alur" class="py-12 sm:py-16 px-4 sm:px-6 bg-[#ECF2EE] border-y border-[#D4E0D8] scroll-mt-16">
            <div class="max-w-6xl mx-auto space-y-8">
                <div class="max-w-2xl space-y-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#1E5B47]">Standar Minimal 6x Pemeriksaan ANC</span>
                    <h2 class="font-editorial text-2xl sm:text-3xl font-semibold text-[#132820]">
                        Perjalanan perawatan Ibu & Buah Hati tanpa terputus
                    </h2>
                    <p class="text-sm text-[#4A5952]">
                        Panduan tahapan kunjungan di Puskesmas Pragaan dan Polindes desa agar setiap fase kehamilan terlewati dengan aman.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white rounded-2xl border border-[#D5E0D8] p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-editorial text-3xl font-semibold text-[#1E5B47]">01</span>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-[#EAF2ED] text-[#143D30]">0 – 11 Minggu</span>
                        </div>
                        <h3 class="text-base font-bold text-[#132820]">Trimester 1: K1 & USG Awal</h3>
                        <p class="text-xs sm:text-sm text-[#525E58] leading-relaxed">
                            Pemeriksaan pertama begitu terlambat haid: konfirmasi kehamilan dengan USG dokter, cek laboratorium lengkap, dan pencatatan Buku KIA.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#D5E0D8] p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-editorial text-3xl font-semibold text-[#1E5B47]">02</span>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-[#EAF2ED] text-[#143D30]">12 – 24 Minggu</span>
                        </div>
                        <h3 class="text-base font-bold text-[#132820]">Trimester 2: Pantau Tumbuh</h3>
                        <p class="text-xs sm:text-sm text-[#525E58] leading-relaxed">
                            Kontrol tekanan darah, kenaikan berat badan, tinggi fundus & detak jantung janin, disertai konsumsi rutin Tablet Tambah Darah (TTD).
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#D5E0D8] p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-editorial text-3xl font-semibold text-[#1E5B47]">03</span>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-[#EAF2ED] text-[#143D30]">≥ 25 Minggu</span>
                        </div>
                        <h3 class="text-base font-bold text-[#132820]">Trimester 3: USG Ke-2 & P4K</h3>
                        <p class="text-xs sm:text-sm text-[#525E58] leading-relaxed">
                            Kontrol minimal 3 kali termasuk skrining USG dokter usia 29–32 minggu untuk cek posisi bayi serta pemantapan rencana persalinan & calon donor.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#D5E0D8] p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-editorial text-3xl font-semibold text-[#B85D43]">04</span>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-[#FDF1EC] text-[#8C422C]">Persalinan & 42 Hari</span>
                        </div>
                        <h3 class="text-base font-bold text-[#132820]">Bersalin, Nifas & Neonatus</h3>
                        <p class="text-xs sm:text-sm text-[#525E58] leading-relaxed">
                            Persalinan aman di Kamar Bersalin PONED 24 Jam, dilanjutkan 4x kontrol nifas ibu (KF), 3x kontrol bayi baru lahir (KN), dan Skrining Hipotiroid (SHK).
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEKSI PAMFLET & EDUKASI KIA (Dinamis Jika Ada Pamflet Terpublikasi) -->
        @if ($posts->isNotEmpty())
            <section id="edukasi" class="py-12 sm:py-16 px-4 sm:px-6 border-b border-[#E5E0D5] scroll-mt-16">
                <div class="max-w-6xl mx-auto space-y-8">
                    <div class="max-w-2xl space-y-2">
                        <span class="text-xs font-bold uppercase tracking-widest text-[#1E5B47]">Penyuluhan Kesehatan</span>
                        <h2 class="font-editorial text-2xl sm:text-3xl font-semibold text-[#132820]">
                            Bacaan & Edukasi Resmi bagi Ibu dan Keluarga
                        </h2>
                        <p class="text-sm text-[#525E58]">
                            Materi informasi kesehatan yang disusun oleh tim medis dan penyuluh UPTD Puskesmas Pragaan.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($posts as $post)
                            <article class="bg-white rounded-2xl border border-[#E2DDD1] overflow-hidden hover:border-[#1E5B47] transition-colors cursor-pointer flex flex-col group"
                                     x-on:click="openPost({{ Js::from($post) }})">
                                <div class="aspect-video w-full overflow-hidden bg-[#F2EFE8] border-b border-[#EAE5DA]">
                                    @if ($post->gambar)
                                        <img src="{{ Storage::url($post->gambar) }}" alt="{{ $post->judul }}" class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-300" loading="lazy">
                                    @else
                                        <div class="w-full h-full flex flex-col items-center justify-center text-[#8B968F]">
                                            <x-ti-photo class="size-8 mb-1" />
                                            <span class="text-xs font-medium">Informasi KIA</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="p-5 flex flex-col flex-1 space-y-2.5">
                                    <div class="flex items-center gap-1.5 text-xs font-medium text-[#637069]">
                                        <x-ti-calendar class="size-3.5 text-[#1E5B47]" />
                                        <span>{{ $post->created_at->translatedFormat('d F Y') }}</span>
                                    </div>

                                    <h3 class="font-editorial text-lg font-semibold text-[#132820] group-hover:text-[#1E5B47] transition-colors line-clamp-2 leading-snug">
                                        {{ $post->judul }}
                                    </h3>

                                    <p class="text-xs sm:text-sm text-[#56635C] line-clamp-2 leading-relaxed flex-1">
                                        {{ Str::limit(strip_tags($post->isi), 115) }}
                                    </p>

                                    <div class="pt-2 text-xs font-semibold text-[#143D30] inline-flex items-center gap-1.5">
                                        <span>Baca selengkapnya</span>
                                        <x-ti-arrow-right class="size-3.5" />
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <!-- SEKSI PANDUAN KUNJUNGAN & TANDA BAHAYA -->
        <section id="kunjungan" class="py-12 sm:py-16 px-4 sm:px-6 scroll-mt-16">
            <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Jadwal & Dokumen Persiapan Periksa -->
                <div class="lg:col-span-6 bg-white rounded-2xl border border-[#E2DDD1] p-6 sm:p-8 space-y-6">
                    <div class="space-y-1.5">
                        <span class="text-xs font-bold uppercase tracking-widest text-[#1E5B47]">Sebelum Berkunjung</span>
                        <h2 class="font-editorial text-2xl font-semibold text-[#132820]">
                            Jadwal Layanan & Dokumen yang Dibawa
                        </h2>
                    </div>

                    <div class="divide-y divide-[#EAE5DA] border-y border-[#EAE5DA] text-sm">
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-semibold text-[#1C2621]">Poli KIA, USG Dokter & Laboratorium</span>
                            <span class="text-[#4A5952]">Senin – Sabtu, 07.30 – 13.00 WIB</span>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-semibold text-[#143D30]">Kamar Bersalin & UGD PONED</span>
                            <span class="font-semibold text-[#1E5B47]">Buka 24 Jam (Termasuk Hari Libur)</span>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-semibold text-[#1C2621]">Polindes / Poskesdes Desa</span>
                            <span class="text-[#4A5952]">14 Desa Wilayah Kec. Pragaan</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-[#525E58]">Kelengkapan Saat Periksa Kehamilan:</h3>
                        <ul class="space-y-2.5 text-xs sm:text-sm text-[#3E4B45]">
                            <li class="flex items-start gap-2.5">
                                <x-ti-circle-check class="size-4 text-[#1E5B47] shrink-0 mt-0.5" />
                                <span><strong>Buku KIA (Buku Pink)</strong> — belum punya? Ibu hamil baru akan langsung mendapatkan Buku KIA gratis saat periksa pertama.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <x-ti-circle-check class="size-4 text-[#1E5B47] shrink-0 mt-0.5" />
                                <span><strong>KTP atau Kartu Keluarga (KK)</strong> untuk kesesuaian rekam medis & pendampingan bidan desa.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <x-ti-circle-check class="size-4 text-[#1E5B47] shrink-0 mt-0.5" />
                                <span><strong>Kartu BPJS Kesehatan / KIS</strong> bagi peserta Jaminan Kesehatan Nasional.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Peringatan Klinis Tanda Bahaya Kehamilan -->
                <div class="lg:col-span-6 bg-[#FDF4F0] rounded-2xl border border-[#E8C8BC] p-6 sm:p-8 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#F9E3DA] text-[#8A371E] text-xs font-semibold">
                            <x-ti-alert-triangle class="size-4" />
                            <span>Kewaspadaan Ibu & Suami</span>
                        </div>

                        <h2 class="font-editorial text-2xl font-semibold text-[#3D1A10]">
                            Jangan tunda ke PONED 24 Jam bila mendapati tanda bahaya ini
                        </h2>

                        <p class="text-xs sm:text-sm text-[#6B3B2D] leading-relaxed">
                            Segera bawa Ibu ke Kamar Bersalin PONED UPTD Puskesmas Pragaan atau hubungi Bidan Desa terdekat tanpa menunggu jadwal poli esok hari apabila muncul salah satu gejala berikut:
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 text-xs sm:text-sm">
                            <div class="bg-white/85 border border-[#ECD1C7] rounded-xl p-3.5 text-[#4A2216] font-medium">
                                1. Perdarahan pada hamil muda maupun hamil tua
                            </div>
                            <div class="bg-white/85 border border-[#ECD1C7] rounded-xl p-3.5 text-[#4A2216] font-medium">
                                2. Sakit kepala hebat, pandangan kabur, atau kejang
                            </div>
                            <div class="bg-white/85 border border-[#ECD1C7] rounded-xl p-3.5 text-[#4A2216] font-medium">
                                3. Demam tinggi atau muntah terus & tak bisa makan
                            </div>
                            <div class="bg-white/85 border border-[#ECD1C7] rounded-xl p-3.5 text-[#4A2216] font-medium">
                                4. Bengkak pada wajah, tangan, disertai tensi tinggi
                            </div>
                            <div class="bg-white/85 border border-[#ECD1C7] rounded-xl p-3.5 text-[#4A2216] font-medium">
                                5. Gerakan janin berkurang atau tidak terasa bergerak
                            </div>
                            <div class="bg-white/85 border border-[#ECD1C7] rounded-xl p-3.5 text-[#4A2216] font-medium">
                                6. Air ketuban keluar sebelum waktunya bersalin
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#E8C8BC] flex items-center justify-between text-xs text-[#783E2D]">
                        <span class="font-semibold">Layanan Darurat Maternal & Neonatal</span>
                        <span class="px-2.5 py-1 rounded-md bg-[#8A371E] text-white font-semibold">PONED Buka 24 Jam</span>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <!-- Kaki Halaman Institusional -->
    <footer class="bg-[#12352A] text-[#C8DBD2] py-10 px-4 sm:px-6 border-t border-[#1E4B3D]">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2.5 text-white font-bold text-base">
                    <x-ti-hospital class="size-5 text-emerald-300" />
                    <span>UPTD Puskesmas Pragaan — Kabupaten Sumenep</span>
                </div>
                <p class="text-xs text-[#9AB8AA] max-w-xl">
                    Sistem Informasi Pemantauan Kesehatan Ibu Hamil, Persalinan PONED 24 Jam, dan Asuhan Bayi Baru Lahir Terintegrasi Wilayah Kecamatan Pragaan.
                </p>
            </div>

            <div class="text-xs text-[#A9C4B8] space-y-1 md:text-right">
                <div class="inline-flex items-center gap-1.5 text-white font-medium">
                    <x-ti-map-pin class="size-4 text-emerald-300" />
                    <span>Jl. Raya Pragaan, Kec. Pragaan, Sumenep, Jawa Timur 69465</span>
                </div>
                <p>&copy; {{ date('Y') }} UPTD Puskesmas Pragaan. Hak cipta dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- Modal Detail Pamflet Edukasi -->
    <x-modal id="pamflet-modal" title="Edukasi Kesehatan Ibu & Anak" center="sm" size="2xl" scrollable>
        <template x-if="selectedPost">
            <div class="space-y-4">
                <template x-if="selectedPost.gambar">
                    <div class="rounded-lg overflow-hidden bg-slate-100 border border-slate-200/80">
                        <img :src="'/storage/' + selectedPost.gambar" :alt="selectedPost.judul" class="w-full max-h-96 object-contain mx-auto">
                    </div>
                </template>

                <div class="space-y-2">
                    <div class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
                        <x-ti-calendar class="size-3.5 text-emerald-700" />
                        <span x-text="formatDateId(selectedPost.created_at)"></span>
                    </div>
                    <h3 class="font-editorial text-xl font-semibold text-slate-900" x-text="selectedPost.judul"></h3>
                    <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line" x-text="selectedPost.isi || ''"></div>
                </div>
            </div>
        </template>

        <x-slot:footer>
            <x-button color="emerald" outline sm text="Tutup" x-on:click="$tsui.close.modal('pamflet-modal')" />
        </x-slot:footer>
    </x-modal>

    <script>
        function welcomeApp() {
            return {
                mobileMenu: false,
                hphtDate: '',
                selectedPost: null,

                openPost(post) {
                    this.selectedPost = post;
                    $tsui.open.modal('pamflet-modal');
                },

                formatDateId(dateString) {
                    if (!dateString) return '-';
                    return new Date(dateString).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                    });
                },

                get hphtResult() {
                    if (!this.hphtDate) {
                        return {
                            weeks: 0,
                            days: 0,
                            totalDays: 0,
                            progressPercent: 0,
                            trimester: 1,
                            trimesterRange: '-',
                            edd: '-',
                            countdownText: '-',
                            adviceTitle: '',
                            adviceBody: '',
                        };
                    }

                    const [year, month, day] = this.hphtDate.split('-').map(Number);
                    const hpht = new Date(year, month - 1, day);
                    const now = new Date();
                    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

                    const totalDays = Math.max(0, Math.round((today - hpht) / 86400000));
                    const weeks = Math.floor(totalDays / 7);
                    const days = totalDays % 7;
                    const progressPercent = Math.min(100, Math.max(3, Math.round((totalDays / 280) * 100)));

                    let trimester = 1;
                    let trimesterRange = '0–11 Mg';
                    let adviceTitle = 'Rekomendasi Layanan Puskesmas Pragaan (TM 1):';
                    let adviceBody = 'Saat terbaik untuk Kunjungan K1 ke Poli KIA: dapatkan skrining USG Trimester 1 oleh Dokter, pemeriksaan laboratorium lengkap, dan Buku KIA gratis.';

                    if (weeks >= 12 && weeks <= 24) {
                        trimester = 2;
                        trimesterRange = '12–24 Mg';
                        adviceTitle = 'Rekomendasi Layanan Puskesmas Pragaan (TM 2):';
                        adviceBody = 'Lakukan kontrol kehamilan rutin di Poli KIA atau Polindes Desa, pantau tekanan darah & pertumbuhan janin, serta rutin konsumsi Tablet Tambah Darah (TTD).';
                    } else if (weeks >= 25) {
                        trimester = 3;
                        trimesterRange = '≥ 25 Mg';
                        adviceTitle = 'Rekomendasi Layanan Puskesmas Pragaan (TM 3):';
                        adviceBody = 'Pastikan kontrol minimal 3x di Trimester 3 termasuk USG Dokter ke-2 (usia 29–32 minggu) dan mantapkan rencana persalinan aman di Kamar Bersalin PONED 24 Jam.';
                    }

                    const eddDate = new Date(year, month - 1, day + 280);
                    const eddFormatted = eddDate.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                    });

                    const daysLeft = Math.round((eddDate - today) / 86400000);
                    let countdownText = '';
                    if (daysLeft > 0) {
                        countdownText = `Estimasi ${daysLeft} hari lagi menuju taksiran persalinan`;
                    } else if (daysLeft === 0) {
                        countdownText = 'Hari ini adalah Hari Perkiraan Lahir (HPL)';
                    } else {
                        countdownText = `Melewati HPL ${Math.abs(daysLeft)} hari — segera periksakan ke Poli KIA / PONED`;
                    }

                    return {
                        weeks,
                        days,
                        totalDays,
                        progressPercent,
                        trimester,
                        trimesterRange,
                        edd: eddFormatted,
                        countdownText,
                        adviceTitle,
                        adviceBody,
                    };
                },
            };
        }
    </script>

    @livewireScripts
</body>
</html>
