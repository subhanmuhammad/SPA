@props(['amount'])
{{ \Illuminate\Support\Number::currency($amount, in: 'IDR', locale: 'id', precision: 0) }}
