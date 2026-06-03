@props([
    'inscricao',
])

@if ($inscricao->ehDuplicataPorEmail())
    <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300 ring-orange-200 dark:ring-orange-800']) }}>
        Duplicata
    </span>
@endif
