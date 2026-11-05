let activeUtterance = null;

Alpine.data('dashboardGreeting', () => ({
    greeting: 'Selamat Datang',

    init() {
        const hour = new Date().getHours();
        this.greeting = hour < 11
            ? 'Selamat Pagi'
            : (hour < 15 ? 'Selamat Siang' : (hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
    },
}));

Alpine.data('dashboardClock', () => ({
    timeStr: '',
    dateStr: '',
    timerId: null,

    init() {
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        const update = () => {
            const d = new Date();
            this.timeStr = String(d.getHours()).padStart(2, '0')
                + ':' + String(d.getMinutes()).padStart(2, '0')
                + ':' + String(d.getSeconds()).padStart(2, '0');
            this.dateStr = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        };

        update();
        this.timerId = setInterval(update, 1000);
    },

    destroy() {
        if (this.timerId) {
            clearInterval(this.timerId);
            this.timerId = null;
        }
    },
}));

Alpine.data('dashboardVoiceAnnouncer', () => ({
    supported: typeof window !== 'undefined' && 'speechSynthesis' in window,
    destroyed: false,
    loopActive: false,
    speaking: false,
    paused: true,
    pausedMidSpeech: false,
    pausedAt: 0,
    waitingNext: false,
    countdown: 5,
    hasSpoken: false,
    navHandler: null,
    speakTimer: null,
    repeatTimer: null,
    countdownTimer: null,

    init() {
        if (!this.supported) return;

        this.destroyed = false;
        this.navHandler = () => this.stopAll();
        document.addEventListener('livewire:navigating', this.navHandler);
        document.addEventListener('livewire:navigate', this.navHandler);
        window.addEventListener('beforeunload', this.navHandler);
        window.addEventListener('pagehide', this.navHandler);

        // Pancing pemuatan daftar suara browser di latar belakang tanpa memutar suara
        window.speechSynthesis.getVoices();

        // Default suara tidak menyala (mati / tidak otomatis)
        this.loopActive = false;
        this.paused = true;
    },

    destroy() {
        if (this.navHandler) {
            document.removeEventListener('livewire:navigating', this.navHandler);
            document.removeEventListener('livewire:navigate', this.navHandler);
            window.removeEventListener('beforeunload', this.navHandler);
            window.removeEventListener('pagehide', this.navHandler);
            this.navHandler = null;
        }
        this.stopAll();
    },

    get isRunning() {
        return this.loopActive && !this.paused;
    },

    get buttonLabel() {
        if (this.isRunning) {
            return this.waitingNext ? `Jeda (${this.countdown}d)` : 'Jeda Suara';
        }
        return this.hasSpoken ? 'Lanjutkan Suara' : 'Putar Suara';
    },

    pickIndonesianVoice(preferLocal = false) {
        const voices = window.speechSynthesis.getVoices() || [];
        const idVoices = voices.filter((v) => /^id([-_]|$)/i.test(v.lang));
        if (idVoices.length === 0) return null;

        if (preferLocal) {
            return idVoices.find((v) => v.localService) || idVoices[0];
        }

        return (
            idVoices.find((v) => /google/i.test(v.name)) ||
            idVoices.find((v) => v.localService && /gadis|ardi|damayanti|indonesia/i.test(v.name)) ||
            idVoices.find((v) => /natural|online/i.test(v.name)) ||
            idVoices[0]
        );
    },

    waitForVoicesAndSpeak() {
        if (!this.supported || this.destroyed || this.paused) return;

        const voices = window.speechSynthesis.getVoices() || [];
        if (voices.length > 0) {
            this.speak();
            return;
        }

        let settled = false;
        const proceed = () => {
            if (settled || this.destroyed) return;
            settled = true;
            window.speechSynthesis.removeEventListener('voiceschanged', proceed);
            if (!this.paused) {
                this.speak();
            }
        };

        window.speechSynthesis.addEventListener('voiceschanged', proceed);
        setTimeout(proceed, 350);
    },

    speak(useFallbackVoice = false) {
        if (!this.supported || this.destroyed || this.paused) return;

        const text = this.$el.dataset.announcement || '';
        if (!text) return;

        this.clearRepeatTimers();
        if (this.speakTimer) {
            clearTimeout(this.speakTimer);
            this.speakTimer = null;
        }

        this.pausedMidSpeech = false;

        if (window.speechSynthesis.paused) {
            window.speechSynthesis.resume();
        }

        const dispatchUtterance = () => {
            if (this.destroyed || this.paused) return;

            const utterance = new SpeechSynthesisUtterance(text);
            activeUtterance = utterance;
            utterance.lang = 'id-ID';
            utterance.rate = 0.98;
            utterance.pitch = 1.0;

            const voice = useFallbackVoice
                ? this.pickIndonesianVoice(true)
                : this.pickIndonesianVoice(false);

            if (voice && !(useFallbackVoice && !voice.localService)) {
                utterance.voice = voice;
            }

            utterance.onstart = () => {
                this.speaking = true;
                this.hasSpoken = true;
                this.pausedMidSpeech = false;
            };

            utterance.onend = () => {
                if (activeUtterance === utterance) {
                    activeUtterance = null;
                }
                this.speaking = false;
                this.pausedMidSpeech = false;

                // Jadwalkan pengulangan berikutnya dengan jeda 5 detik bila loop masih aktif dan tidak dijeda
                if (!this.destroyed && this.loopActive && !this.paused) {
                    this.scheduleNextRepeat();
                }
            };

            utterance.onerror = (event) => {
                if (activeUtterance === utterance) {
                    activeUtterance = null;
                }
                this.speaking = false;

                if (event.error === 'canceled' || event.error === 'interrupted') {
                    return;
                }

                // Fallback ke suara lokal apabila suara cloud/online gagal dimuat
                if (
                    !useFallbackVoice &&
                    ['synthesis-failed', 'network', 'voice-unavailable', 'language-unavailable'].includes(event.error)
                ) {
                    this.speak(true);
                    return;
                }

                // Jika terjadi error ringan di tengah loop setelah pernah berhasil bicara, coba ulangi 5 detik lagi
                if (!this.destroyed && this.loopActive && !this.paused && this.hasSpoken) {
                    this.scheduleNextRepeat();
                }
            };

            window.speechSynthesis.speak(utterance);
        };

        if (window.speechSynthesis.speaking) {
            window.speechSynthesis.cancel();
            this.speakTimer = setTimeout(dispatchUtterance, 60);
        } else {
            dispatchUtterance();
        }
    },

    scheduleNextRepeat() {
        this.clearRepeatTimers();

        if (this.destroyed || !this.loopActive || this.paused) {
            return;
        }

        this.waitingNext = true;
        this.countdown = 5;

        this.countdownTimer = setInterval(() => {
            if (this.destroyed || !this.loopActive || this.paused) {
                this.clearRepeatTimers();
                return;
            }
            if (this.countdown > 1) {
                this.countdown -= 1;
            }
        }, 1000);

        this.repeatTimer = setTimeout(() => {
            this.clearRepeatTimers();
            if (!this.destroyed && this.loopActive && !this.paused) {
                this.speak();
            }
        }, 5000);
    },

    clearRepeatTimers() {
        if (this.repeatTimer) {
            clearTimeout(this.repeatTimer);
            this.repeatTimer = null;
        }
        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
            this.countdownTimer = null;
        }
        this.waitingNext = false;
    },

    pause() {
        this.paused = true;
        this.clearRepeatTimers();

        if (this.speakTimer) {
            clearTimeout(this.speakTimer);
            this.speakTimer = null;
        }

        if (this.supported && this.speaking && window.speechSynthesis.speaking) {
            window.speechSynthesis.pause();
            this.pausedMidSpeech = true;
            this.pausedAt = Date.now();
        } else {
            this.pausedMidSpeech = false;
        }

        this.speaking = false;
    },

    resumeOrPlay() {
        this.loopActive = true;
        this.paused = false;

        // Lanjutkan kalimat yang terjeda bila jeda belum melebihi batas buffer browser (12 detik)
        if (
            this.supported &&
            this.pausedMidSpeech &&
            window.speechSynthesis.paused &&
            window.speechSynthesis.speaking &&
            (Date.now() - this.pausedAt < 12000)
        ) {
            this.pausedMidSpeech = false;
            this.speaking = true;
            window.speechSynthesis.resume();
            return;
        }

        this.pausedMidSpeech = false;
        this.waitForVoicesAndSpeak();
    },

    stopAll() {
        this.destroyed = true;
        this.loopActive = false;
        this.paused = true;
        this.pausedMidSpeech = false;
        this.clearRepeatTimers();

        if (this.speakTimer) {
            clearTimeout(this.speakTimer);
            this.speakTimer = null;
        }

        if (activeUtterance) {
            activeUtterance.onstart = null;
            activeUtterance.onend = null;
            activeUtterance.onerror = null;
            activeUtterance = null;
        }

        if (this.supported) {
            window.speechSynthesis.cancel();
        }
        this.speaking = false;
    },

    togglePlayPause() {
        if (this.isRunning) {
            this.pause();
        } else {
            this.resumeOrPlay();
        }
    },
}));
