@extends("layouts.app")

@section("title", "Donate")

@section("content")
    <section class="bg-white px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <a href="{{ route("donate") }}" class="inline-flex items-center gap-2 text-sm font-semibold text-green-700 hover:underline">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                Back to donation options
            </a>

            <div class="mt-4 rounded-lg bg-green-900 px-6 py-10 text-white shadow">
                <p class="text-sm font-semibold uppercase tracking-wide text-green-100">Secure giving</p>
                <h1 class="mt-2 text-3xl font-bold md:text-4xl">Complete your donation</h1>
                <p class="mt-3 max-w-2xl leading-7 text-green-50">
                    Choose an amount and how often you would like to give, then request payment details from the
                    National Children's Commission team. Every contribution supports child protection work in Malawi.
                </p>
            </div>

            <div
                class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start"
                x-data="{
                    amount: 2000,
                    customAmount: '',
                    frequency: 'one-off',
                    get chosenAmount() {
                        return this.customAmount !== '' ? Number(this.customAmount) : this.amount;
                    },
                    get summary() {
                        return 'Mkw ' + this.chosenAmount.toLocaleString('en-MW') + ' (' + this.frequency + ')';
                    },
                    get requestHref() {
                        const subject = encodeURIComponent('Donation request: ' + this.summary);
                        const body = encodeURIComponent(
                            'Hello NCC,\n\nI would like to donate ' + this.summary + '.\n' +
                            'Please send me the payment details (Airtel Money, TNM Mpamba, bank transfer, or PayPal).\n\n' +
                            'Name:\nContact number:\n'
                        );

                        return 'mailto:nccmalawi@gmail.com?subject=' + subject + '&body=' + body;
                    }
                }"
            >
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xl font-bold text-slate-950">1. Choose your gift</h2>

                    <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Donation frequency">
                        <button type="button" @click="frequency = 'one-off'"
                            :class="frequency === 'one-off' ? 'bg-green-700 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:border-green-700'"
                            class="rounded-md px-4 py-2 text-sm font-semibold transition">
                            One-off
                        </button>
                        <button type="button" @click="frequency = 'monthly'"
                            :class="frequency === 'monthly' ? 'bg-green-700 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:border-green-700'"
                            class="rounded-md px-4 py-2 text-sm font-semibold transition">
                            Monthly
                        </button>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        @foreach ([2000, 5000, 10000] as $amount)
                            <button type="button"
                                @click="amount = {{ $amount }}; customAmount = ''"
                                :class="customAmount === '' && amount === {{ $amount }} ? 'border-green-700 bg-green-50 text-green-800 ring-1 ring-green-700' : 'border-slate-300 bg-white text-slate-700 hover:border-green-700'"
                                class="rounded-lg border px-4 py-4 text-left transition">
                                <span class="block text-lg font-bold">Mkw {{ number_format($amount) }}</span>
                                <span class="mt-1 block text-xs text-slate-500">
                                    {{ $amount === 2000 ? 'Blanket and essentials' : ($amount === 5000 ? 'School meals for a week' : 'Child protection outreach') }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-5">
                        <label for="custom_amount" class="block text-sm font-semibold text-slate-800">Or enter another amount (Mkw)</label>
                        <input id="custom_amount" type="number" min="100" step="100" inputmode="numeric"
                            x-model="customAmount"
                            placeholder="e.g. 1500"
                            class="mt-1 w-full rounded-md border border-slate-300 p-3 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                    </div>

                    <div class="mt-6 rounded-md bg-slate-50 p-4">
                        <p class="text-sm text-slate-600">Your selected gift</p>
                        <p class="mt-1 text-xl font-bold text-green-800" x-text="summary"></p>
                    </div>
                </div>

                <aside class="rounded-lg border border-slate-200 bg-slate-50 p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-950">2. Request payment details</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Online card processing is not enabled on this site yet. Request the official NCC payment
                        details and a receipt by email, or call the team directly.
                    </p>

                    <a :href="requestHref"
                        class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-green-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-green-800">
                        Email my donation request
                    </a>

                    <a href="tel:+265880268418"
                        class="mt-3 inline-flex w-full items-center justify-center rounded-md border border-green-700 px-5 py-3 text-sm font-semibold text-green-700 transition hover:bg-green-50">
                        Call +265 (0) 880 268 418
                    </a>

                    <dl class="mt-6 space-y-4 text-sm">
                        <div>
                            <dt class="font-semibold text-slate-900">Airtel Money / TNM Mpamba</dt>
                            <dd class="text-slate-600">Payment details are shared by the NCC finance team.</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-900">Bank transfer</dt>
                            <dd class="text-slate-600">Request the official NCC account details before transferring.</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-900">PayPal</dt>
                            <dd class="text-slate-600">Available on request for international supporters.</dd>
                        </div>
                    </dl>

                    <p class="mt-6 rounded-md border border-yellow-200 bg-yellow-50 p-3 text-xs leading-5 text-yellow-900">
                        NCC will never ask for your PIN, password, or a payment to a personal number. Always confirm
                        details through the official contacts above.
                    </p>
                </aside>
            </div>
        </div>
    </section>
@endsection
