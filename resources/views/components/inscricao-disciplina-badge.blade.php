@props([
    'codigo',
    'status' => 'pendente',
    'tooltip' => null,
])

@php
    $classes = match ($status) {
        'aprovado' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 ring-green-200 dark:ring-green-800',
        'reprovado' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 ring-red-200 dark:ring-red-800',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 ring-gray-200 dark:ring-gray-600',
    };
@endphp

<span
    title="{{ $tooltip ?? match ($status) {
        'aprovado' => $codigo.' — Aprovado',
        'reprovado' => $codigo.' — Reprovado',
        default => $codigo.' — Pendente',
    } }}"
    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $classes }}"
>
    {{ $codigo }}
</span>