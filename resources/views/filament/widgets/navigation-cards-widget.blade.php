<x-filament-widgets::widget>
    <style>
        :root {
            --bg-color: white;
            --border-color: #e5e7eb;
            --text-color: #111827;
            --text-muted: #6b7280;
            --badge-bg: #f3f4f6;
            --badge-text: #374151;
        }
        .dark {
            --bg-color: #1f2937;
            --border-color: #374151;
            --text-color: white;
            --text-muted: #9ca3af;
            --badge-bg: #374151;
            --badge-text: #d1d5db;
        }
    </style>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
        @foreach ($this->getNavigationCards() as $card)
            <a href="{{ $card['url'] }}"
               style="display: block;
                      padding: 24px;
                      background: var(--bg-color, white);
                      border-radius: 12px;
                      border: 2px solid var(--border-color, #e5e7eb);
                      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                      text-decoration: none;
                      transition: all 0.2s;"
               onmouseover="this.style.borderColor='#1094AB'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';"
               onmouseout="this.style.borderColor='var(--border-color, #e5e7eb)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)';">

                {{-- Header --}}
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                    {{-- Icon container --}}
                    <div style="flex-shrink: 0; padding: 10px; border-radius: 8px; background-color: {{ $card['color'] === 'primary' ? 'rgba(16, 148, 171, 0.1)' : ($card['color'] === 'success' ? 'rgba(16, 185, 129, 0.1)' : ($card['color'] === 'warning' ? 'rgba(252, 180, 33, 0.1)' : ($card['color'] === 'gray' ? 'rgba(107, 114, 128, 0.1)' : 'rgba(59, 130, 246, 0.1)'))) }};">
                        <svg style="width: 20px; height: 20px; color: {{ $card['color'] === 'primary' ? '#1094AB' : ($card['color'] === 'success' ? '#10b981' : ($card['color'] === 'warning' ? '#FCB421' : ($card['color'] === 'gray' ? '#6b7280' : '#3b82f6'))) }};"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24"
                             stroke-width="2">
                            @if($card['icon'] === 'heroicon-o-users')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            @elseif($card['icon'] === 'heroicon-o-shield-check')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            @elseif($card['icon'] === 'heroicon-o-key')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            @elseif($card['icon'] === 'heroicon-o-document-text')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            @elseif($card['icon'] === 'heroicon-o-envelope')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            @elseif($card['icon'] === 'heroicon-o-bug-ant')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0112 12.75zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 01-1.152 6.06M12 12.75c-2.883 0-5.647.508-8.208 1.44.125 2.104.52 4.136 1.153 6.06M12 12.75a2.25 2.25 0 002.248-2.354M12 12.75a2.25 2.25 0 01-2.248-2.354M12 8.25c.995 0 1.971-.08 2.922-.236.403-.066.74-.358.795-.762a3.778 3.778 0 00-.399-2.25M12 8.25c-.995 0-1.97-.08-2.922-.236-.402-.066-.74-.358-.795-.762a3.734 3.734 0 01.4-2.253M12 8.25a2.25 2.25 0 00-2.248 2.146M12 8.25a2.25 2.25 0 01 2.248 2.146M8.683 5a6.032 6.032 0 01-1.155-1.002c.07-.63.27-1.222.574-1.747m.581 2.749A3.75 3.75 0 0115.318 5m0 0c.427-.283.815-.62 1.155-.999a4.471 4.471 0 00-.575-1.752M4.921 6a24.048 24.048 0 00-.392 3.314c1.668.546 3.416.914 5.223 1.082M19.08 6c.205 1.08.337 2.187.392 3.314a23.882 23.882 0 01-5.223 1.082"/>
                            @endif
                        </svg>
                    </div>

                    {{-- Badge --}}
                    <div style="flex-shrink: 0; padding: 6px 12px; font-size: 12px; font-weight: bold; border-radius: 999px; background-color: var(--badge-bg, #f3f4f6); color: var(--badge-text, #374151);">
                        {{ $card['stats'] }}
                    </div>
                </div>

                {{-- Title --}}
                <h3 style="font-size: 18px; font-weight: bold; color: var(--text-color, #111827); margin-bottom: 8px;">
                    {{ $card['title'] }}
                </h3>

                {{-- Description --}}
                <p style="font-size: 14px; color: var(--text-muted, #6b7280); margin-bottom: 16px; line-height: 1.5;">
                    {{ $card['description'] }}
                </p>

                {{-- Footer --}}
                <div style="display: flex; align-items: center; font-size: 13px; font-weight: 600; color: {{ $card['color'] === 'primary' ? '#1094AB' : ($card['color'] === 'success' ? '#10b981' : ($card['color'] === 'warning' ? '#FCB421' : ($card['color'] === 'gray' ? '#6b7280' : '#3b82f6'))) }};">
                    <span>Acessar</span>
                    <svg style="width: 14px; height: 14px; margin-left: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </div>
            </a>
        @endforeach
    </div>
</x-filament-widgets::widget>
