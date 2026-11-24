@props(['title'])

@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'super' => false],
        ['label' => 'Booking', 'route' => 'admin.bookings.index', 'active' => 'admin.bookings.*', 'icon' => 'calendar-check', 'super' => false],
        ['label' => 'Transaksi', 'route' => 'admin.payments.index', 'active' => 'admin.payments.*', 'icon' => 'wallet', 'super' => false],
        ['label' => 'Laporan', 'route' => 'admin.reports', 'active' => 'admin.reports', 'icon' => 'file-text', 'super' => false],
        ['label' => 'Business Intelligence', 'route' => 'admin.insights', 'active' => 'admin.insights', 'icon' => 'chart-column', 'super' => true],
        ['label' => 'Pelanggan', 'route' => 'admin.customers.index', 'active' => 'admin.customers.*', 'icon' => 'contact', 'super' => false],
        ['label' => 'Home Care', 'route' => 'admin.home-care.index', 'active' => 'admin.home-care.*', 'icon' => 'house', 'super' => false],
        ['label' => 'Terapis', 'route' => 'admin.therapists.index', 'active' => 'admin.therapists.*', 'icon' => 'users', 'super' => false],
        ['label' => 'Layanan & Paket', 'route' => 'admin.treatments.index', 'active' => 'admin.treatments.*', 'icon' => 'sparkles', 'super' => false],
        ['label' => 'Cabang', 'route' => 'admin.branches.index', 'active' => 'admin.branches.*', 'icon' => 'building-2', 'super' => true],
        ['label' => 'Akun Admin', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'user-cog', 'super' => true],
        ['label' => 'Audit Log', 'route' => 'admin.audit-logs.index', 'active' => 'admin.audit-logs.*', 'icon' => 'shield-check', 'super' => true],
        ['label' => 'Metode Pembayaran', 'route' => 'admin.payment-methods.index', 'active' => 'admin.payment-methods.*', 'icon' => 'credit-card', 'super' => true],
    ];
@endphp

<x-layouts.base :title="$title">
    <div class="flex min-h-svh">
        <button type="button" data-sidebar-toggle aria-label="Tutup menu"
            class="fixed inset-0 z-40 hidden bg-black/50 in-data-sidebar-open:block lg:in-data-sidebar-open:hidden"></button>

        <aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-transform in-data-sidebar-open:translate-x-0 lg:sticky lg:top-0 lg:h-svh lg:translate-x-0">
            <div class="flex h-14 items-center justify-between gap-2 px-4">
                <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2 font-semibold">
                    <img src="/images/logo.jpg" alt="" class="size-8 shrink-0 rounded-md">
                    <span class="truncate">{{ config('app.name') }}</span>
                </a>
                <x-ui.button variant="ghost" size="icon" type="button" data-sidebar-toggle class="lg:hidden" aria-label="Tutup menu">
                    <i data-lucide="x"></i>
                </x-ui.button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-2" aria-label="Menu utama">
                @foreach ($navigation as $item)
                    @continue($item['super'] && ! auth()->user()->isSuperAdmin())
                    @php($active = request()->routeIs($item['active']))
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                        @class([
                            'flex h-8 items-center gap-2 rounded-md p-2 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                            'bg-primary/5 font-medium text-sidebar-accent-foreground' => $active,
                        ])>
                        <i data-lucide="{{ $item['icon'] }}"></i>
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 border-b bg-card">
                <div class="mx-auto flex h-14 max-w-360 items-center justify-between gap-4 px-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <x-ui.button variant="ghost" size="icon" type="button" data-sidebar-toggle class="lg:hidden" aria-label="Buka menu">
                            <i data-lucide="menu" class="size-5"></i>
                        </x-ui.button>
                        <h1 class="text-sm font-medium">{{ $title }}</h1>
                    </div>

                    <details class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted [&::-webkit-details-marker]:hidden">
                            <span class="flex size-8 items-center justify-center rounded-full bg-muted font-medium uppercase">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                            <span class="hidden text-left sm:block">
                                <span class="block leading-tight font-medium">{{ auth()->user()->name }}</span>
                                <span class="block text-xs text-muted-foreground">{{ auth()->user()->role->label() }}</span>
                            </span>
                            <i data-lucide="chevron-down" class="text-muted-foreground"></i>
                        </summary>
                        <div class="absolute right-0 z-50 mt-2 w-56 rounded-lg bg-popover p-1 text-popover-foreground shadow-md ring-1 ring-foreground/10">
                            <div class="px-2 py-1.5 text-xs text-muted-foreground">{{ auth()->user()->email }}</div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm text-destructive hover:bg-destructive/10">
                                    <i data-lucide="log-out"></i> Keluar
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <main class="mx-auto w-full max-w-360 flex-1 px-4 py-6 sm:px-6">
                @if (session('status'))
                    <div role="status" class="mb-6 rounded-lg border bg-card px-4 py-3 text-sm">{{ session('status') }}</div>
                @endif
                {{ $slot }}
            </main>

            <footer class="mx-auto w-full max-w-360 px-4 py-3 text-sm text-muted-foreground sm:px-6">
                &copy; {{ now()->year }} {{ config('app.name') }}
            </footer>
        </div>
    </div>
</x-layouts.base>
