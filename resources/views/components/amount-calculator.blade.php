{{-- Kalkulator mini (+ − × ÷) untuk field nominal. Dipakai dengan membungkus field:
     <x-amount-calculator model="expense_amount"><x-currency ... /></x-amount-calculator> --}}
@props(['model'])

<div
    x-data="{
        model: @js($model),
        open: false,
        expr: '',
        result: null,
        mem: null,
        op: '+',
        toggle() { this.open = !this.open; this.expr = ''; this.result = null; this.mem = null; this.op = '+'; },
        display() {
            if (this.result !== null) return Number(this.result).toLocaleString('id-ID');
            const expr = this.expr.replace(/\*/g, ' × ').replace(/\//g, ' ÷ ');
            if (this.mem !== null) {
                return Number(this.mem).toLocaleString('id-ID') + ' ' + (this.op === '-' ? '−' : '+') + ' ' + (expr || '…');
            }
            return expr || '0';
        },
        press(key) {
            if (this.result !== null) { this.expr = ''; this.result = null; }
            const ops = '+-*/';
            if (ops.includes(key)) {
                if (this.mem !== null && this.expr === '') {
                    if (key === '+' || key === '-') this.op = key;
                    return;
                }
                if (this.expr === '') return;
                if (ops.includes(this.expr.slice(-1))) this.expr = this.expr.slice(0, -1);
            }
            this.expr += key;
        },
        backspace() { this.expr = this.expr.slice(0, -1); },
        clear() { this.expr = ''; this.result = null; },
        store() {
            if (this.result === null) return;
            this.mem = this.result;
            this.op = '+';
            this.expr = '';
            this.result = null;
        },
        calculate() {
            if (this.expr === '' || !/^[0-9+\-*/.]+$/.test(this.expr) || /[+\-*/]$/.test(this.expr)) return;
            let value;
            try {
                value = new Function('return (' + this.expr + ')')();
            } catch (error) {
                return;
            }
            if (typeof value !== 'number' || !isFinite(value)) return;
            value = Math.round(value);
            this.result = this.mem === null ? value : Math.round(this.op === '-' ? this.mem - value : this.mem + value);
            this.mem = null;
            this.op = '+';
        },
        insert() {
            if (this.result === null) return;
            const field = this.$refs.field.querySelector('input');
            if (!field) return;
            field.value = String(this.result);
            field.dispatchEvent(new Event('input', { bubbles: true }));
            this.open = false;
            this.expr = '';
            this.result = null;
        },
    }"
    x-on:click.outside="open = false"
    {{ $attributes->merge(['data-calculator' => $model]) }}
    class="relative [&_.rounded-md.ring-1>input]:pr-9"
>
    <div x-ref="field" class="min-w-0">
        {{ $slot }}
    </div>

    <button
        type="button"
        x-on:click="toggle()"
        title="Kalkulator mini"
        aria-label="Buka kalkulator mini"
        class="absolute right-1 top-0.5 z-10 flex size-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-emerald-600"
    >
        <x-ti-calculator class="size-4" />
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute right-0 bottom-full z-50 mb-1.5 w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-lg shadow-slate-900/10"
    >
        <div class="mb-2 min-h-9 break-all rounded-xl bg-slate-50 px-3 py-2 text-right font-mono text-sm text-slate-800" x-text="display()"></div>

        <div x-show="mem !== null" x-cloak class="mb-1.5 flex items-center justify-between rounded-lg bg-emerald-50 px-2.5 py-1 text-xs text-emerald-700">
            <span class="font-medium">
                Tersimpan: <span class="font-semibold tabular-nums" x-text="mem === null ? '' : Number(mem).toLocaleString('id-ID')"></span>
            </span>
            <button type="button" x-on:click="mem = null" title="Buang hasil tersimpan" class="cursor-pointer font-bold text-emerald-700/60 transition-colors hover:text-emerald-900">×</button>
        </div>

        <div class="grid grid-cols-4 gap-1.5">
            <button type="button" x-on:click="press('7')" class="cursor-pointer rounded-lg border border-slate-200 bg-white py-1.5 text-sm font-semibold text-slate-800 tabular-nums transition-colors hover:bg-slate-100">7</button>
            <button type="button" x-on:click="press('8')" class="cursor-pointer rounded-lg border border-slate-200 bg-white py-1.5 text-sm font-semibold text-slate-800 tabular-nums transition-colors hover:bg-slate-100">8</button>
            <button type="button" x-on:click="press('9')" class="cursor-pointer rounded-lg border border-slate-200 bg-white py-1.5 text-sm font-semibold text-slate-800 tabular-nums transition-colors hover:bg-slate-100">9</button>
            <button type="button" x-on:click="press('/')" class="cursor-pointer rounded-lg bg-slate-100 py-1.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-200">÷</button>

            <button type="button" x-on:click="press('4')" class="cursor-pointer rounded-lg border border-slate-200 bg-white py-1.5 text-sm font-semibold text-slate-800 tabular-nums transition-colors hover:bg-slate-100">4</button>
            <button type="button" x-on:click="press('5')" class="cursor-pointer rounded-lg border border-slate-200 bg-white py-1.5 text-sm font-semibold text-slate-800 tabular-nums transition-colors hover:bg-slate-100">5</button>
            <button type="button" x-on:click="press('6')" class="cursor-pointer rounded-lg border border-slate-200 bg-white py-1.5 text-sm font-semibold text-slate-800 tabular-nums transition-colors hover:bg-slate-100">6</button>
            <button type="button" x-on:click="press('*')" class="cursor-pointer rounded-lg bg-slate-100 py-1.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-200">×</button>

            <button type="button" x-on:click="press('1')" class="cursor-pointer rounded-lg border border-slate-200 bg-white py-1.5 text-sm font-semibold text-slate-800 tabular-nums transition-colors hover:bg-slate-100">1</button>
            <button type="button" x-on:click="press('2')" class="cursor-pointer rounded-lg border border-slate-200 bg-white py-1.5 text-sm font-semibold text-slate-800 tabular-nums transition-colors hover:bg-slate-100">2</button>
            <button type="button" x-on:click="press('3')" class="cursor-pointer rounded-lg border border-slate-200 bg-white py-1.5 text-sm font-semibold text-slate-800 tabular-nums transition-colors hover:bg-slate-100">3</button>
            <button type="button" x-on:click="press('-')" class="cursor-pointer rounded-lg bg-slate-100 py-1.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-200">−</button>

            <button type="button" x-on:click="clear()" title="Bersihkan" class="cursor-pointer rounded-lg bg-rose-50 py-1.5 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100">C</button>
            <button type="button" x-on:click="press('0')" class="cursor-pointer rounded-lg border border-slate-200 bg-white py-1.5 text-sm font-semibold text-slate-800 tabular-nums transition-colors hover:bg-slate-100">0</button>
            <button type="button" x-on:click="backspace()" title="Hapus satu angka" class="cursor-pointer rounded-lg bg-slate-100 py-1.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-200">⌫</button>
            <button type="button" x-on:click="press('+')" class="cursor-pointer rounded-lg bg-slate-100 py-1.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-200">+</button>
        </div>

        <div class="mt-1.5 flex gap-1.5">
            <button
                type="button"
                x-on:click="store()"
                :disabled="result === null"
                title="Simpan hasil untuk dijumlahkan dengan hitungan berikutnya"
                class="w-16 shrink-0 cursor-pointer rounded-xl bg-slate-100 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
            >
                M+
            </button>
            <button
                type="button"
                x-on:click="calculate()"
                title="Hitung hasil"
                class="flex-1 cursor-pointer rounded-xl bg-slate-100 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-200"
            >
                = Hitung
            </button>
            <button
                type="button"
                x-on:click="insert()"
                :disabled="result === null"
                title="Masukkan hasil ke nominal"
                class="flex-1 cursor-pointer rounded-xl bg-emerald-600 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Masukkan
            </button>
        </div>
    </div>
</div>
