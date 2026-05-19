@props([
    'resumo',
    'etapa' => 'secretaria',
])

@php
    $enum = $resumo instanceof \App\Enums\ResumoAprovacaoInscricao
        ? $resumo
        : \App\Enums\ResumoAprovacaoInscricao::tryFrom((string) $resumo) ?? \App\Enums\ResumoAprovacaoInscricao::Pendente;

    $classes = match ($enum) {
        \App\Enums\ResumoAprovacaoInscricao::Aprovada => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 ring-green-200 dark:ring-green-800',
        \App\Enums\ResumoAprovacaoInscricao::Reprovada => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 ring-red-200 dark:ring-red-800',
        \App\Enums\ResumoAprovacaoInscricao::NaoAplicavel => 'bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-500 ring-gray-200 dark:ring-gray-700',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 ring-gray-200 dark:ring-gray-600',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {$classes}"]) }}>
    {{ $enum->label($etapa) }}
</span>
