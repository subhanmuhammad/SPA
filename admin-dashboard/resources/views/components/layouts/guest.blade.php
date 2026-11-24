@props(['title' => null])

<x-layouts.base :title="$title">
    <main class="flex min-h-svh items-center justify-center bg-[#242a36] bg-cover bg-center px-4 py-10 sm:px-6 lg:px-8" style="background-image: linear-gradient(rgba(36, 42, 54, 0.72), rgba(36, 42, 54, 0.72)), url('/images/site/hero.jpg');">
        {{ $slot }}
    </main>
</x-layouts.base>
