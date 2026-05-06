<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Phone Scanner</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900">
    <main class="mx-auto flex min-h-screen w-full max-w-md flex-col px-4 py-5">
        <div class="mb-4 rounded-3xl bg-slate-900 px-5 py-5 text-white shadow-xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">Broker Transaction</p>
            <h1 class="mt-2 text-2xl font-bold">Phone Scanner</h1>
            <p class="mt-2 text-sm text-white/75">Scan fish box QR codes here. They will appear on the laptop transaction page.</p>
        </div>

        @if(!$isSessionActive)
            <section class="rounded-3xl border border-red-200 bg-white p-6 text-center shadow-sm">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-slate-900">Session expired</h2>
                <p class="mt-2 text-sm text-slate-500">Please create a new phone scanner session from the laptop transaction page.</p>
            </section>
        @else
            <section class="flex-1 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950">
                    <video id="remotePhoneScannerVideo" class="block w-full" style="height: min(65vh, 32rem); object-fit: cover;" autoplay muted playsinline></video>
                    <div class="pointer-events-none absolute inset-0">
                        <div class="absolute left-[18%] top-[14%] h-10 w-10 rounded-tl-2xl border-l-4 border-t-4 border-yellow-400"></div>
                        <div class="absolute right-[18%] top-[14%] h-10 w-10 rounded-tr-2xl border-r-4 border-t-4 border-yellow-400"></div>
                        <div class="absolute bottom-[14%] left-[18%] h-10 w-10 rounded-bl-2xl border-b-4 border-l-4 border-yellow-400"></div>
                        <div class="absolute bottom-[14%] right-[18%] h-10 w-10 rounded-br-2xl border-b-4 border-r-4 border-yellow-400"></div>
                    </div>
                </div>

                <div data-phone-scanner-status class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-medium text-slate-700">
                    Preparing scanner...
                </div>

                <button type="button" data-phone-scanner-start class="mt-4 h-12 w-full rounded-2xl bg-slate-900 px-4 text-sm font-semibold text-white shadow-sm">
                    Restart Camera
                </button>
            </section>

            <script>
                window.remoteSalesPhoneScannerConfig = {
                    scanUrl: @json(route('broker.sales.scan-sessions.scan', $session->token, false)),
                };
            </script>
            <script src="{{ asset('js/qr-scanner-legacy.min.js') }}" defer></script>
            <script src="{{ asset('js/remote-sales-phone-scanner.js') }}?v={{ filemtime(public_path('js/remote-sales-phone-scanner.js')) }}" defer></script>
        @endif
    </main>
</body>
</html>
