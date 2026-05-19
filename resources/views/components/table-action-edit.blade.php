<a {{ $attributes->merge([
    'class' => 'inline-flex items-center rounded-md bg-yellow-500 px-3 py-2 text-xs font-semibold text-gray-900 shadow-sm ring-1 ring-yellow-600/30 hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition',
]) }}>
    {{ $slot->isEmpty() ? 'Editar' : $slot }}
</a>
