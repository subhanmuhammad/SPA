@props(['analytics', 'branches'])

<form method="GET" class="mb-6 flex flex-wrap items-end gap-2">
    <div class="grid gap-1">
        <x-ui.label for="from">Dari</x-ui.label>
        <x-ui.input name="from" type="date" :value="$analytics->from->toDateString()" class="max-w-44" />
    </div>
    <div class="grid gap-1">
        <x-ui.label for="to">Sampai</x-ui.label>
        <x-ui.input name="to" type="date" :value="$analytics->to->toDateString()" class="max-w-44" />
    </div>
    @if ($branches->count() > 1)
        <div class="grid gap-1">
            <x-ui.label for="branch">Cabang</x-ui.label>
            <x-ui.select name="branch" :options="['' => 'Semua cabang'] + $branches->all()" :selected="request('branch')" class="max-w-52" />
        </div>
    @endif
    <x-ui.button variant="outline">Tampilkan</x-ui.button>
</form>
@error('to') <p class="-mt-4 mb-4 text-sm text-destructive">{{ $message }}</p> @enderror
