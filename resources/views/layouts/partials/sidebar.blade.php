{{-- Sidebar compartilhada entre desktop e mobile (Sprint 0.1). --}}
{{-- Nesta Sprint somente Dashboard é funcional; demais itens são futuros/desabilitados. --}}
<div class="flex items-center gap-2 px-5 pb-4 pt-5">
    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500 text-sm font-bold text-white">P</div>
    <div>
        <p class="text-base font-bold text-white">Publikai</p>
        <p class="text-[11px] text-slate-400">Jaguartec · interno</p>
    </div>
</div>

<nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-6 text-sm">
    <div>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-2 font-medium text-white">
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
    @endphp

    @foreach ($sections as $section => $items)
        <div>
            <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $section }}</p>
            <ul class="space-y-1">
                @foreach ($items as $item)
                    <li>
                        <span class="flex cursor-not-allowed items-center justify-between rounded-lg px-3 py-2 text-slate-400 opacity-70" title="Disponível em sprints futuras">
                            <span>{{ $item }}</span>
                            <span class="rounded-full bg-slate-800 px-2 py-0.5 text-[10px] font-medium text-slate-400">Em breve</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>

<div class="border-t border-slate-800 px-5 py-4 text-[11px] text-slate-500">
    <p>Fase inicial · Sprint 0.1</p>
</div>
