{{-- Resultado de análise por IA (Sprint 5.1). Recebe $analysis. Nunca exibe JSON cru. --}}
@php
    $sections = [
        'Padrões de Hooks' => $analysis->dominant_hooks,
        'Estruturas de Conteúdo' => $analysis->content_structures,
        'Padrões de CTA' => $analysis->cta_patterns,
        'Padrões Visuais' => $analysis->visual_patterns,
        'Padrões de Comunicação' => $analysis->communication_patterns,
        'Sinais de Audiência' => $analysis->audience_signals,
        'Ângulos de Conteúdo' => $analysis->content_angles,
        'Padrões Recorrentes' => $analysis->repeated_patterns,
        'Riscos' => $analysis->risks,
        'Recomendações' => $analysis->recommendations,
    ];
@endphp

@if ($analysis->isSuccess())
    <div class="flex flex-wrap items-center gap-2">
        <x-ui.badge variant="success">Concluída</x-ui.badge>
        @if (! is_null($analysis->confidence))
            <x-ui.badge variant="info">{{ number_format($analysis->confidence * 100, 0) }}%</x-ui.badge>
        @endif
        <span class="t-small">Analisada em {{ $analysis->completed_at?->display() }} · {{ $analysis->provider }} / {{ $analysis->model }}</span>
    </div>

    <p class="t-muted mt-1">Confiança da IA na consistência dos padrões identificados.</p>

    @if ($analysis->summary)
        <div class="mt-4">
            <h4 class="t-section-title">Resumo</h4>
            <p class="t-body mt-1 whitespace-pre-line">{{ $analysis->summary }}</p>
        </div>
    @endif

    <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
        @foreach ($sections as $title => $items)
            @if (! empty($items))
                <div @if($loop->last) class="sm:col-span-2" @endif>
                    <dt class="t-small font-medium uppercase tracking-wide">{{ $title }}</dt>
                    <dd class="mt-1">
                        <ul class="list-disc space-y-1 pl-5 text-sm text-ink-secondary">
                            @foreach ($items as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </dd>
                </div>
            @endif
        @endforeach
    </dl>
@else
    <div class="flex flex-wrap items-center gap-2">
        <x-ui.badge variant="danger">Falhou</x-ui.badge>
        <span class="t-small">{{ $analysis->completed_at?->display() }}</span>
    </div>
    <p class="t-card-title mt-3">Falha na análise</p>
    <p class="t-body mt-1">Não foi possível concluir a análise agora.</p>
    @if ($analysis->error_code === 'service_unavailable')
        <p class="t-body mt-1">O serviço de IA está temporariamente indisponível. Tente novamente mais tarde.</p>
    @endif
    <p class="t-small mt-2">Use "Analisar com IA" acima para tentar novamente (nova análise, sem alterar o histórico).</p>
@endif
