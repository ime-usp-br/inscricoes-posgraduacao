@props([
    'href',
    'label' => 'Voltar',
])

<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => 'inline-flex items-center gap-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition',
   ]) }}>
    <span aria-hidden="true">←</span>
    {{ $label }}
</a>
