<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inscrições
            </h2>
            <x-back-link :href="route('home')" label="Voltar ao início" />
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center text-gray-900 dark:text-gray-100">
                    <p class="text-lg font-medium">
                        Fora do Periodo de inscrições
                    </p>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        No momento não há período de inscrições ativo. Volte mais tarde.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
