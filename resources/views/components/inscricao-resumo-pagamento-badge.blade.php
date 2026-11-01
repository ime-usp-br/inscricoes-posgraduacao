@props([
    'resumo',
])

@php
    $enum = $resumo instanceof \App\Enums\ResumoPagamentoInscricao
        ? $resumo
        : \App\Enums\ResumoPagamentoInscricao::tryFrom((string) $resumo) ?? \App\Enums\ResumoPagamentoInscricao::NaoAplicavel;

    $classes = match ($enum) {
        \App\Enums\ResumoPagamentoInscricao::Pago => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 ring-green-200 dark:ring-green-800',
        \App\Enums\ResumoPagamentoInscricao::NaoPago => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 ring-amber-200 dark:ring-amber-800',
        \App\Enums\ResumoPagamentoInscricao::AguardandoIsencao => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 ring-blue-200 dark:ring-blue-800',
        \App\Enums\ResumoPagamentoInscricao::Isento => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 ring-emerald-200 dark:ring-emerald-800',
        \App\Enums\ResumoPagamentoInscricao::PagoOuIsento => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 ring-green-200 dark:ring-green-800',
        default => 'bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-500 ring-gray-200 dark:ring-gray-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {$classes}"]) }}>
    {{ $enum->label() }}
</span>
