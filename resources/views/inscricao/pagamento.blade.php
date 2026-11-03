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
            @if (session('success'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('info'))
                <div class="rounded-md bg-blue-50 dark:bg-blue-900/30 p-4 text-sm text-blue-800 dark:text-blue-200">
                    {{ session('info') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200 space-y-1">
                    @foreach ($errors->all() as $erro)
                        <p>{{ $erro }}</p>
                    @endforeach
                </div>
            @endif

            @if ($temIsencaoPendente)
                <div class="rounded-md bg-amber-50 dark:bg-amber-900/30 p-4 text-sm text-amber-800 dark:text-amber-200 space-y-2">
                    <p class="font-semibold">Solicitação de isenção em análise.</p>
                    <p>O pagamento via Pix ficará disponível apenas após a decisão da secretaria.</p>
                    <a href="{{ route('inscricao.isencao.show') }}" class="font-medium underline">
                        Acompanhar solicitação de isenção
                    </a>
                </div>
            @elseif ($podeSolicitarIsencao)
                <div class="rounded-md bg-blue-50 dark:bg-blue-900/30 p-4 text-sm text-blue-800 dark:text-blue-200 space-y-2">
                    <p class="font-semibold">Precisa de isenção da taxa?</p>
                    <p>
                        Você pode solicitar isenção das disciplinas do MAC/MAE antes de gerar a cobrança Pix.
                        A solicitação é analisada pela secretaria e, enquanto estiver pendente, o pagamento fica indisponível.
                    </p>
                    <a href="{{ route('inscricao.isencao.show') }}" class="font-medium underline">
                        Solicitar isenção da taxa
                    </a>
                </div>
            @elseif ($solicitacaoIsencao)
                <div class="rounded-md bg-gray-50 dark:bg-gray-900/30 p-4 text-sm text-gray-700 dark:text-gray-300 space-y-2">
                    <p>
                        Sua solicitação de isenção está
                        <strong>{{ $solicitacaoIsencao->status->label() }}</strong>.
                    </p>
                    <a href="{{ route('inscricao.isencao.show') }}" class="font-medium underline">
                        Ver detalhes da solicitação
                    </a>
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

                    @if (! $temIsencaoPendente)
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
                    @endif

                    @if ($erroPix)
                        <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                            {{ $erroPix }}
                        </div>
                    @endif

                    @if (! $temIsencaoPendente && ! $inscricao->possuiPixGerado())
                        <div class="rounded-lg border border-gray-200 dark:border-gray-600 p-4 space-y-3">
                            <p class="text-sm font-medium">Cobrança Pix</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                A cobrança não é gerada automaticamente. Clique no botão abaixo quando estiver pronto para pagar.
                            </p>
                            <form method="POST" action="{{ route('inscricao.pagamento.gerar') }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    Gerar cobrança Pix
                                </button>
                            </form>
                        </div>
                    @elseif (! $temIsencaoPendente && (is_array($pixDados) || $inscricao->pix_qr_code))
                        @php
                            $qrBase64 = is_array($pixDados) ? ($pixDados['qrCodeImgBase64'] ?? null) : null;
                            $copiaCola = $inscricao->pix_qr_code ?? (is_array($pixDados) ? ($pixDados['qrCode'] ?? null) : null);
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
