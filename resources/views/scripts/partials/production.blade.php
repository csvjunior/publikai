{{--
    Jornada de produção do roteiro (Sprint 5.6.4).
    Uso: @include('scripts.partials.production', ['script' => $script, 'flow' => $flow])
    Uma única ação principal (recommended_action); resto é link secundário.
--}}
<x-ui.card title="Produção" description="Acompanhe o roteiro até o vídeo final. Montagem é opcional.">
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-lg border border-border p-4">
            <h4 class="t-card-title">Visual</h4>
            @if ($flow['visual']['status'] === 'ready')
                <div class="mt-2 flex items-center gap-3">
                    @if ($flow['visual']['asset']?->url())
                        <img src="{{ $flow['visual']['asset']->url() }}" alt="Imagem principal" loading="lazy" class="h-16 w-12 rounded object-cover">
                    @endif
                    <p class="t-body">Imagem pronta</p>
                </div>
                <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1">
                    @if ($flow['recommended_action'] === 'create_video' && ! $flow['blocked_by_processing'])
                        <x-ui.button :href="route('scripts.videos.create', [$script, 'source' => $flow['recommended_image_id']])" variant="ai">Criar vídeo</x-ui.button>
                    @endif
                    <a href="#materiais-imagens" class="text-xs font-medium text-primary hover:text-primary-hover">Ver imagens</a>
                    <a href="{{ route('scripts.images.edit', [$script, $flow['visual']['asset']]) }}" class="text-xs font-medium text-primary hover:text-primary-hover">Criar variação</a>
                </div>
            @elseif ($flow['visual']['status'] === 'processing')
                <p class="t-body mt-2">Processando… Atualize a página para acompanhar.</p>
            @elseif ($flow['visual']['status'] === 'failed')
                <p class="t-body mt-2">Não foi possível concluir.</p>
                <a href="{{ $flow['visual']['retry_url'] }}" class="mt-2 inline-block text-xs font-medium text-primary hover:text-primary-hover">Tentar novamente</a>
            @else
                <p class="t-body mt-2">Nenhuma imagem criada.</p>
                @if ($flow['recommended_action'] === 'generate_image' && ! $flow['blocked_by_processing'])
                    <x-ui.button :href="route('scripts.images.create', $script)" variant="ai" class="mt-3">Gerar imagem</x-ui.button>
                @endif
            @endif
        </div>

        <div class="rounded-lg border border-border p-4">
            <h4 class="t-card-title">Vídeo</h4>
            @if ($flow['video']['status'] === 'ready')
                <p class="t-body mt-2">Vídeo pronto</p>
                <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1">
                    <a href="#materiais-videos" class="text-xs font-medium text-primary hover:text-primary-hover">Ver vídeos</a>
                </div>
                <p class="t-small mt-3">Opcional: <a href="{{ route('scripts.compositions.create', $script) }}" class="font-medium text-primary hover:text-primary-hover">Montar vídeo</a> para combinar vários clipes e imagens.</p>
            @elseif ($flow['video']['status'] === 'processing')
                <p class="t-body mt-2">Processando… Atualize a página para acompanhar.</p>
            @elseif ($flow['video']['status'] === 'failed')
                <p class="t-body mt-2">Não foi possível concluir.</p>
                <a href="{{ $flow['video']['retry_url'] }}" class="mt-2 inline-block text-xs font-medium text-primary hover:text-primary-hover">Tentar novamente</a>
            @elseif ($flow['visual']['status'] === 'ready')
                <p class="t-body mt-2">Aguardando visual.</p>
            @else
                <p class="t-body mt-2">Nenhum vídeo criado.</p>
            @endif
        </div>

        <div class="rounded-lg border border-border p-4">
            <h4 class="t-card-title">Narração</h4>
            @if ($flow['narration']['status'] === 'ready')
                <div class="mt-2 flex items-center gap-3">
                    @if ($flow['narration']['asset']?->url())
                        <audio src="{{ $flow['narration']['asset']->url() }}" controls preload="metadata" class="h-9 w-full max-w-44"></audio>
                    @endif
                    <p class="t-body">{{ $flow['narration']['asset']?->duration_seconds ? $flow['narration']['asset']->duration_seconds.'s' : 'Narração pronta' }}</p>
                </div>
                <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1">
                    <a href="{{ route('scripts.audio.create', $script) }}" class="text-xs font-medium text-primary hover:text-primary-hover">Gerar outra narração</a>
                </div>
            @elseif ($flow['narration']['status'] === 'processing')
                <p class="t-body mt-2">Processando… Atualize a página para acompanhar.</p>
            @elseif ($flow['narration']['status'] === 'failed')
                <p class="t-body mt-2">Não foi possível concluir.</p>
                <a href="{{ $flow['narration']['retry_url'] }}" class="mt-2 inline-block text-xs font-medium text-primary hover:text-primary-hover">Tentar novamente</a>
            @else
                <p class="t-body mt-2">Nenhuma narração. Pode ser criada a partir do roteiro.</p>
                @if ($flow['recommended_action'] === 'generate_audio' && ! $flow['blocked_by_processing'])
                    <x-ui.button :href="route('scripts.audio.create', $script)" variant="ai" class="mt-3">Gerar narração</x-ui.button>
                @endif
            @endif
        </div>

        <div class="rounded-lg border border-border p-4">
            <h4 class="t-card-title">Finalização</h4>
            @if ($flow['final']['status'] === 'ready')
                <p class="t-body mt-2">Vídeo final pronto</p>
                <div class="mt-2">
                    @if ($flow['final']['asset']?->url())
                        <video src="{{ $flow['final']['asset']->url() }}" controls preload="metadata" class="w-full bg-black {{ $flow['final']['asset']->orientationClass() }}"></video>
                    @endif
                    <div class="mt-2 flex flex-wrap items-center gap-1">
                        <x-ui.badge variant="ai">Final</x-ui.badge>
                        @if ($flow['final']['asset'])
                            <span class="t-small">{{ $flow['final']['asset']->duration_seconds }}s · 9:16</span>
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1">
                    @if ($flow['final']['asset']?->url())
                        <a href="{{ $flow['final']['asset']->url() }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:text-primary-hover">Abrir vídeo</a>
                    @endif
                    <a href="{{ route('scripts.merges.create', $script) }}" class="text-xs font-medium text-primary hover:text-primary-hover">Criar nova versão</a>
                    <a href="#materiais" class="text-xs font-medium text-primary hover:text-primary-hover">Ver materiais</a>
                </div>
            @elseif ($flow['video']['status'] === 'ready' && $flow['narration']['status'] === 'ready')
                <p class="t-body mt-2">Vídeo e narração estão prontos.</p>
                @if ($flow['recommended_action'] === 'finalize_video' && ! $flow['blocked_by_processing'])
                    <x-ui.button :href="route('scripts.merges.create', $script)" variant="ai" class="mt-3">Finalizar vídeo</x-ui.button>
                    <p class="t-small mt-2">Combine o vídeo e a narração em uma versão final.</p>
                @endif
            @else
                <p class="t-body mt-2">Aguardando vídeo e narração.</p>
            @endif
        </div>
    </div>
</x-ui.card>
