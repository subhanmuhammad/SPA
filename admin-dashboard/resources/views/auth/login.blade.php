<x-layouts.guest title="Masuk">
    <div class="w-full rounded-xl bg-card py-6 text-card-foreground shadow-xs ring-1 ring-foreground/10 sm:max-w-md">
        <div class="space-y-6 px-6">
            <img src="/images/logo.jpg" alt="{{ config('app.name') }}" class="mx-auto size-20 rounded-lg">

            <h1 class="text-center text-2xl font-semibold">Masuk ke dashboard</h1>

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf

                <div class="grid gap-2">
                    <x-ui.label for="email">Email</x-ui.label>
                    <x-ui.input name="email" type="email" :value="old('email')" required autofocus autocomplete="username" />
                </div>

                <div class="grid gap-2">
                    <x-ui.label for="password">Password</x-ui.label>
                    <x-ui.input name="password" type="password" required autocomplete="current-password" />
                </div>

                <label class="flex items-center gap-2 text-sm text-muted-foreground">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-input accent-primary">
                    Ingat saya
                </label>

                <x-ui.button class="w-full">Masuk</x-ui.button>
            </form>
        </div>
    </div>
</x-layouts.guest>
