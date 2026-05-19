@props([
    'action',
    'confirm' => 'Tem certeza que deseja excluir?',
])

<form action="{{ $action }}" method="POST"
      class="inline"
      onsubmit="return confirm(@js($confirm));">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge([
        'class' => 'inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm ring-1 ring-red-700/30 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition',
    ]) }}>
        {{ $slot->isEmpty() ? 'Excluir' : $slot }}
    </button>
</form>
