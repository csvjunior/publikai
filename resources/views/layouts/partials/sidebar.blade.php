{{-- Sidebar global do Publikai (Sprint 0.2). Compartilhada entre desktop e drawer mobile. --}}
{{-- Somente Dashboard é funcional; demais itens são futuros/desabilitados (sem rotas, sem controllers vazios). --}}
{{-- Recebe $drawer (bool) via @include. --}}
@php
    $drawer = $drawer ?? false;
@endphp

<div class="flex items-center gap-2 px-5 pb-4 pt-5">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary text-sm font-bold text-white" aria-hidden="true">P</div>
    <div class="min-w-0 flex-1">
        <p class="text-base font-bold text-white">Publikai</p>
        <p class="text-[11px] text-sidebar-muted">Jaguartec · interno</p>
    </div>
    @if ($drawer)
        <button id="menu-close" type="button" class="rounded-lg p-2 text-sidebar-muted hover:bg-sidebar-hover hover:text-white" aria-label="Fechar menu">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>

<nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-6 text-sm" aria-label="Seções">
    <div>
        <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif
            class="flex items-center gap-2 rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('dashboard') ? 'bg-sidebar-hover text-white' : 'text-sidebar-muted hover:bg-sidebar-hover hover:text-white' }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            <span>Dashboard</span>
        </a>
    </div>

    @php
        $sections = [
            'Operação' => ['Produtos', 'Campanhas', 'Conteúdos'],
            'Creative Studio' => ['Ideias', 'Roteiros', 'Imagens', 'Vídeos'],
            'Inteligência' => ['Referências', 'Blueprints'],
            'Distribuição' => ['Contas', 'Calendário', 'Publicações'],
            'Performance' => ['Métricas', 'Conversões'],
            'Sistema' => ['IA', 'Custos', 'Integrações', 'Configurações'],
        ];
        $activeLinks = [
            'Produtos' => ['route' => 'products.index', 'active' => request()->routeIs('products.*')],
        ];
    @endphp

    @foreach ($sections as $section => $items)
        <div>
            <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-sidebar-muted">{{ $section }}</p>
            <ul class="space-y-1">
                @foreach ($items as $item)
                    <li>
                        @if (isset($activeLinks[$item]))
                            <a href="{{ route($activeLinks[$item]['route']) }}" @if($activeLinks[$item]['active']) aria-current="page" @endif
                                class="flex items-center justify-between rounded-lg px-3 py-2 transition {{ $activeLinks[$item]['active'] ? 'bg-sidebar-hover font-medium text-white' : 'text-sidebar-muted hover:bg-sidebar-hover hover:text-white' }}">
                                <span>{{ $item }}</span>
                            </a>
                        @else
                            <span class="flex cursor-not-allowed items-center justify-between rounded-lg px-3 py-2 text-sidebar-muted opacity-70" title="Disponível em sprints futuras">
                                <span>{{ $item }}</span>
                                <x-ui.badge variant="neutral">Em breve</x-ui.badge>
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>

<div class="border-t border-sidebar-hover px-4 py-4">
    <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white" aria-hidden="true">
            {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
            <p class="text-[11px] text-sidebar-muted">{{ auth()->user()->role->value }}</p>
        </div>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-sidebar-hover px-3 py-2 text-sm font-medium text-sidebar-ink transition hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
            Sair
        </button>
    </form>
    <p class="mt-3 text-[11px] text-sidebar-muted">Fase inicial · Sprint 0.2</p>
</div>
