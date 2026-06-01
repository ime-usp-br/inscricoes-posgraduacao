<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Pagamento da inscrição
            </h2>
            <x-back-link :href="route('home')" label="Voltar ao início" />
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('info'))
                <div class="rounded-md bg-blue-50 dark:bg-blue-900/30 p-4 text-sm text-blue-800 dark:text-blue-200">
                    {{ session('info') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-6">
                    <div>
                        <p class="text-lg font-semibold text-amber-700 dark:text-amber-400">
                            Pagamento via Pix
                        </p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Período: <strong>{{ $periodo->ano }}/{{ $periodo->semestre }}</strong>
                            — {{ $inscricao->nome_completo }} ({{ $inscricao->email }})
                        </p>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-600 p-4 space-y-3">
                        <p class="text-sm font-medium">Disciplinas com taxa de inscrição (MAC / MAE)</p>
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            @foreach ($disciplinasPendentes as $item)
                                <li>
                                    {{ $item['disciplina']->codigo_completo }} — {{ $item['disciplina']->nome }}
                                </li>
                            @endforeach
                        </ul>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm pt-2 border-t border-gray-200 dark:border-gray-600">
                            <div>
                                <dt class="text-gray-600 dark:text-gray-400">Valor por disciplina</dt>
                                <dd class="font-medium">R$ {{ number_format($valorUnitario, 2, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-600 dark:text-gray-400">Quantidade</dt>
                                <dd class="font-medium">{{ $quantidade }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-gray-600 dark:text-gray-400">Total a pagar (pagamento único)</dt>
                                <dd class="text-xl font-semibold text-amber-700 dark:text-amber-400">
                                    R$ {{ number_format($valorTotal, 2, ',', '.') }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    @if ($erroPix)
                        <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                            Não foi possível gerar o Pix: {{ $erroPix }}
                        </div>
                    @elseif (is_array($pixDados))
                        @php
                            $qrBase64 = $pixDados['qrCodeImgBase64'] ?? null;
                            $copiaCola = $inscricao->pix_qr_code ?? ($pixDados['qrCode'] ?? null);
                        @endphp

                        @if (is_string($qrBase64) && $qrBase64 !== '')
                            <div class="flex flex-col items-center gap-2">
                                <p class="text-sm font-medium">Escaneie o QR Code</p>
                                <img
                                    src="data:image/png;base64,{{ $qrBase64 }}"
                                    alt="QR Code Pix"
                                    class="w-56 h-56 border border-gray-200 dark:border-gray-600 rounded-lg bg-white p-2"
                                />
                            </div>
                        @endif

                        @if (is_string($copiaCola) && $copiaCola !== '')
                            <div class="space-y-2">
                                <label for="pix-copia-cola" class="block text-sm font-medium">Pix copia e cola</label>
                                <textarea
                                    id="pix-copia-cola"
                                    readonly
                                    rows="4"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm font-mono"
                                >{{ $copiaCola }}</textarea>
                                <button
                                    type="button"
                                    onclick="navigator.clipboard.writeText(document.getElementById('pix-copia-cola').value)"
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                                >
                                    Copiar código
                                </button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('inscricao.pagamento.verificar') }}" class="pt-2">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Já paguei — verificar pagamento
                            </button>
                        </form>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Após o pagamento, a confirmação pode levar alguns instantes. Use o botão acima ou aguarde a atualização automática.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
