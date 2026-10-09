@extends('layouts.app')

@section('title', $script->title)
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    :title="$script->title"
    :description="$script->objective"
    :breadcrumbs="[['label' => 'Roteiros', 'url' => route('scripts.index')], ['label' => $script->title]]"
>
    <x-slot:actions>
        @if ($script->isEditable())
            <x-ui.button :href="route('scripts.edit', $script)" variant="outline">Editar</x-ui.button>
        @endif
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if (session('script_notice'))
    <x-ui.alert variant="warning" class="mb-6">{{ session('script_notice') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Contexto">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :variant="$script->status->badgeVariant()">{{ $script->status->label() }}</x-ui.badge>
            @if ($script->generation_source === \App\Enums\ContentScriptSource::Ai)
                <x-ui.badge variant="ai">IA</x-ui.badge>
            @else
                <x-ui.badge variant="neutral">Manual</x-ui.badge>
            @endif
            @if ($script->language)
                <x-ui.badge variant="neutral">{{ $script->language }}</x-ui.badge>
            @endif
            @if ($script->market)
                <x-ui.badge variant="info">{{ $script->market }}</x-ui.badge>
            @endif
        </div>

        <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Produto</dt>
                <dd class="t-body mt-0.5">
                    @if ($script->product)
                        <a href="{{ route('products.show', $script->product) }}" class="font-medium text-primary hover:text-primary-hover">{{ $script->product->name }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Blueprint</dt>
                <dd class="t-body mt-0.5">
                    @if ($script->blueprint)
                        <a href="{{ route('blueprints.show', $script->blueprint) }}" class="font-medium text-primary hover:text-primary-hover">{{ $script->blueprint->name }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Persona</dt>
                <dd class="t-body mt-0.5">
                    @if ($script->persona)
                        <a href="{{ route('personas.show', $script->persona) }}" class="font-medium text-primary hover:text-primary-hover">{{ $script->persona->name }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Avatar</dt>
                <dd class="t-body mt-0.5">
                    @if ($script->avatar)
                        <a href="{{ route('avatars.show', $script->avatar) }}" class="font-medium text-primary hover:text-primary-hover">{{ $script->avatar->name }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
        </dl>

        <div class="mt-4 flex flex-col gap-2 border-t border-border pt-4 sm:flex-row">
            @if ($script->status === \App\Enums\ContentScriptStatus::Draft)
                <form method="POST" action="{{ route('scripts.ready', $script) }}" class="sm:w-auto">
                    @csrf
                    <x-ui.button variant="secondary" type="submit" full>Marcar como pronto</x-ui.button>
                </form>
            @endif
            @if ($script->isReady())
                <form method="POST" action="{{ route('scripts.approve', $script) }}" class="sm:w-auto">
                    @csrf
                    <x-ui.button variant="primary" type="submit" full>Aprovar roteiro</x-ui.button>
                </form>
            @endif
            @if ($script->isFailed())
                <x-ui.button :href="route('scripts.create')" variant="outline">Tentar novamente (novo roteiro)</x-ui.button>
            @endif
        </div>
    </x-ui.card>

    @if ($script->isFailed())
        <x-ui.card title="Falha na geração">
            <p class="t-card-title">Não foi possível gerar o roteiro</p>
            <p class="t-body mt-1">Tente novamente mais tarde criando um novo roteiro.</p>
        </x-ui.card>
    @else
        <x-ui.card title="Roteiro">
            <div class="space-y-4">
                <div>
                    <h4 class="t-section-title">Hook</h4>
                    <p class="t-body mt-1 whitespace-pre-line">{{ $script->hook }}</p>
                </div>
                @if ($script->opening)
                    <div>
                        <h4 class="t-section-title">Abertura</h4>
                        <p class="t-body mt-1 whitespace-pre-line">{{ $script->opening }}</p>
                    </div>
                @endif
                <div>
                    <h4 class="t-section-title">Corpo</h4>
                    <p class="t-body mt-1 whitespace-pre-line">{{ $script->body }}</p>
                </div>
                <div>
                    <h4 class="t-section-title">CTA</h4>
                    <p class="t-body mt-1 whitespace-pre-line">{{ $script->cta }}</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Direção de produção">
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Texto na tela</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $script->on_screen_text ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Direção visual</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $script->visual_direction ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Direção de voz</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $script->voice_direction ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Duração</dt>
                    <dd class="t-body mt-0.5">{{ $script->duration_seconds ? $script->duration_seconds.'s' : '—' }}</dd>
                </div>
                @if ($script->generation_source === \App\Enums\ContentScriptSource::Ai)
                    <div>
                        <dt class="t-small font-medium uppercase tracking-wide">Origem IA</dt>
                        <dd class="t-body mt-0.5">{{ $script->provider ?? '—' }} / {{ $script->model ?? '—' }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>
    @endif

    @include('scripts.partials.production', ['script' => $script, 'flow' => $flow])

    <h3 id="materiais" class="t-section-title mt-2">Materiais</h3>

    <x-ui.card title="Imagens" description="Assets visuais gerados a partir deste roteiro." id="materiais-imagens">
        @if ($script->isReady() || $script->isApproved())
            <div class="mb-4">
                <x-ui.button :href="route('scripts.images.create', $script)" variant="ai">Gerar imagem</x-ui.button>
            </div>
        @else
            <p class="t-small mb-4">Marque o roteiro como pronto antes de gerar imagens.</p>
        @endif

        @if ($script->mediaAssets->isEmpty())
            <x-ui.empty-state
                title="Nenhuma imagem gerada"
                description="Gere um asset visual a partir do roteiro, produto e identidade selecionados."
            />
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($script->mediaAssets as $asset)
                    @php($purposeLabel = \App\Enums\ContentScriptAssetPurpose::tryFrom((string) ($asset->pivot->purpose ?? ''))?->label() ?? $asset->pivot->purpose)
                    <div class="overflow-hidden rounded-card border border-border bg-surface shadow-card">
                        <a href="{{ $asset->url() }}" target="_blank" rel="noopener" class="block">
                            @if ($asset->type->value === 'video')
                                <video src="{{ $asset->url() }}" preload="metadata" class="aspect-[9/16] w-full object-cover"></video>
                            @else
                                <img src="{{ $asset->url() }}" alt="Imagem do roteiro" loading="lazy" class="aspect-[9/16] w-full object-cover">
                            @endif
                        </a>
                        <div class="space-y-1 p-2.5">
                            <div class="flex flex-wrap items-center gap-1">
                                @if ($asset->pivot->is_primary)
                                    <x-ui.badge variant="ai">Principal</x-ui.badge>
                                @endif
                                @if ($asset->parent_media_asset_id)
                                    <x-ui.badge variant="neutral">Variação</x-ui.badge>
                                @endif
                                <span class="t-small font-medium">{{ $purposeLabel }}</span>
                            </div>
                            <p class="t-muted">{{ $asset->width }} × {{ $asset->height }}</p>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-0.5">
                                @if ($asset->type->value === 'video')
                                    <a href="{{ $asset->url() }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:text-primary-hover">Abrir vídeo</a>
                                @else
                                    <a href="{{ $asset->url() }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:text-primary-hover">Abrir imagem</a>
                                    <a href="{{ route('scripts.images.edit', [$script, $asset]) }}" class="text-xs font-medium text-primary hover:text-primary-hover">Criar variação</a>
                                    <a href="{{ route('scripts.videos.create', [$script, 'source' => $asset->id]) }}" class="text-xs font-medium text-primary hover:text-primary-hover">Criar vídeo</a>
                                @endif
                                @if (! $asset->pivot->is_primary)
                                    <form method="POST" action="{{ route('scripts.images.primary', [$script, $asset]) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-ink-secondary hover:text-ink">Definir como principal</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @php($pending = $imageRequests->filter(fn ($r) => in_array($r->status->value, ['pending', 'processing'], true)))
        @if ($pending->isNotEmpty())
            <div class="mt-5">
                <h4 class="t-section-title">Gerações em andamento</h4>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($pending as $item)
                        <li class="flex flex-wrap items-center gap-2 text-sm">
                            <x-ui.badge :variant="$item->status->value === 'processing' ? 'info' : 'neutral'">{{ $item->status->value === 'processing' ? 'Processando' : 'Pendente' }}</x-ui.badge>
                            <span class="t-small">{{ $item->created_at?->display() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php($failed = $imageRequests->filter(fn ($r) => $r->status->value === 'failed')->take(3))
        @if ($failed->isNotEmpty())
            <div class="mt-5">
                <h4 class="t-section-title">Falhas recentes</h4>
                <ul class="t-small mt-2 space-y-1.5">
                    @foreach ($failed as $item)
                        <li>
                            <span class="text-ink-muted">{{ $item->created_at?->display() }}</span>
                            <span>{{ match ($item->error_code) { 'timeout' => 'A geração excedeu o tempo esperado.', 'rate_limited' => 'O limite temporário do serviço foi atingido.', 'service_unavailable' => 'O serviço está temporariamente indisponível.', 'invalid_image' => 'O provider não retornou uma imagem válida.', 'source_missing' => 'A imagem base não está mais disponível.', 'source_invalid' => 'A imagem base não é mais válida.', 'reference_missing' => 'Uma das imagens de referência não está mais disponível.', 'reference_invalid' => 'Uma das imagens de referência não é mais válida.', default => 'Não foi possível gerar a imagem.' } }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Vídeos" description="Clipes image-to-video gerados a partir deste roteiro." id="materiais-videos">
        <div class="mb-4 flex flex-wrap gap-2">
            <x-ui.button :href="route('scripts.compositions.create', $script)" variant="outline">Montar vídeo</x-ui.button>
            <x-ui.button :href="route('scripts.merges.create', $script)" variant="outline">Adicionar narração ao vídeo</x-ui.button>
        </div>
        @if ($videoRequests->isEmpty() && $compositions->isEmpty())
            <x-ui.empty-state
                title="Nenhum vídeo gerado"
                description="Crie um clipe curto a partir de uma imagem deste roteiro."
            />
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($videoRequests as $videoRequest)
                    <div class="overflow-hidden rounded-card border border-border bg-surface shadow-card">
                        @if ($videoRequest->status->value === 'success' && $videoRequest->mediaAsset?->url())
                            <video src="{{ $videoRequest->mediaAsset->url() }}" controls preload="metadata" class="w-full bg-black {{ $videoRequest->mediaAsset->orientationClass() }}"></video>
                        @else
                            <div class="flex aspect-video w-full items-center justify-center bg-surface-muted">
                                <x-ui.badge :variant="$videoRequest->status->value === 'failed' ? 'danger' : 'info'">{{ $videoRequest->status->label() }}</x-ui.badge>
                            </div>
                        @endif
                        <div class="space-y-1 p-2.5">
                            @if ($videoRequest->status->value === 'success' && $videoRequest->mediaAsset)
                                <p class="t-muted">{{ $videoRequest->mediaAsset->duration_seconds }}s · {{ $videoRequest->aspect_ratio }}@if ($videoRequest->mediaAsset->mime_type) · {{ $videoRequest->mediaAsset->mime_type }}@endif</p>
                            @else
                                <p class="t-muted">Clipe curto · {{ $videoRequest->aspect_ratio }}</p>
                            @endif
                            @if ($videoRequest->status->value === 'failed')
                                <p class="t-small">{{ match ($videoRequest->error_code) { 'timeout' => 'A geração excedeu o tempo esperado.', 'rate_limited' => 'O limite temporário foi atingido.', 'service_unavailable' => 'O serviço está temporariamente indisponível.', 'source_missing', 'source_invalid' => 'A imagem base não está mais disponível.', 'download_failed' => 'Falha ao baixar o vídeo gerado.', 'invalid_video' => 'O provider não retornou um vídeo válido.', 'provider_failed' => 'A geração falhou no provider.', default => 'Não foi possível gerar o vídeo.' } }}</p>
                            @elseif ($videoRequest->mediaAsset?->url())
                                <a href="{{ $videoRequest->mediaAsset->url() }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:text-primary-hover">Abrir vídeo</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($compositions->isNotEmpty())
            <h4 class="t-section-title mt-5">Montagens</h4>
            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($compositions as $composition)
                    <div class="overflow-hidden rounded-card border border-border bg-surface shadow-card">
                        @if ($composition->status->value === 'success' && $composition->output?->url())
                            <video src="{{ $composition->output->url() }}" controls preload="metadata" class="w-full bg-black {{ $composition->output->orientationClass() }}"></video>
                        @else
                            <div class="flex aspect-video w-full items-center justify-center bg-surface-muted">
                                <x-ui.badge :variant="$composition->status->value === 'failed' ? 'danger' : 'info'">{{ $composition->status->label() }}</x-ui.badge>
                            </div>
                        @endif
                        <div class="space-y-1 p-2.5">
                            <div class="flex flex-wrap items-center gap-1">
                                <x-ui.badge variant="neutral">Montagem</x-ui.badge>
                                <span class="t-small font-medium">{{ $composition->inputs->count() }} itens</span>
                            </div>
                            @if ($composition->status->value === 'failed')
                                <p class="t-small">{{ match ($composition->error_code) { 'source_missing' => 'Um dos itens não está mais disponível.', 'source_invalid' => 'Um dos itens não é mais válido.', 'ffmpeg_failed' => 'Falha ao compor o vídeo.', 'invalid_output' => 'A composição não gerou um vídeo válido.', 'timeout' => 'A composição excedeu o tempo esperado.', default => 'Não foi possível compor o vídeo.' } }}</p>
                            @elseif ($composition->output?->url())
                                <p class="t-muted">{{ $composition->output->duration_seconds }}s · 9:16 · {{ $composition->output->mime_type }}</p>
                                <a href="{{ $composition->output->url() }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:text-primary-hover">Abrir vídeo</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($merges->isNotEmpty())
            <h4 class="t-section-title mt-5">Vídeos com narração</h4>
            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($merges as $merge)
                    <div class="overflow-hidden rounded-card border border-border bg-surface shadow-card">
                        @if ($merge->status->value === 'success' && $merge->output?->url())
                            <video src="{{ $merge->output->url() }}" controls preload="metadata" class="w-full bg-black {{ $merge->output->orientationClass() }}"></video>
                        @else
                            <div class="flex aspect-video w-full items-center justify-center bg-surface-muted">
                                <x-ui.badge :variant="$merge->status->value === 'failed' ? 'danger' : 'info'">{{ $merge->status->label() }}</x-ui.badge>
                            </div>
                        @endif
                        <div class="space-y-1 p-2.5">
                            <div class="flex flex-wrap items-center gap-1">
                                <x-ui.badge variant="ai">Com narração</x-ui.badge>
                            </div>
                            @if ($merge->status->value === 'failed')
                                <p class="t-small">{{ match ($merge->error_code) { 'video_missing', 'audio_missing' => 'Um dos itens não está mais disponível.', 'video_invalid', 'audio_invalid' => 'Um dos itens não é mais válido.', 'ffmpeg_failed' => 'Falha ao combinar vídeo e narração.', 'invalid_output' => 'O merge não gerou um vídeo válido com narração.', 'timeout' => 'O merge excedeu o tempo esperado.', default => 'Não foi possível gerar o vídeo com narração.' } }}</p>
                            @elseif ($merge->output?->url())
                                <p class="t-muted">{{ $merge->output->duration_seconds }}s · 9:16 · {{ $merge->output->mime_type }}</p>
                                <a href="{{ $merge->output->url() }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:text-primary-hover">Abrir vídeo</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Narrações" description="Vozes geradas a partir deste roteiro.">
        @if ($script->isReady() || $script->isApproved())
            <div class="mb-4">
                <x-ui.button :href="route('scripts.audio.create', $script)" variant="ai">Gerar narração</x-ui.button>
            </div>
        @endif

        @if ($audioRequests->isEmpty())
            <x-ui.empty-state
                title="Nenhuma narração gerada"
                description="Gere uma voz a partir do texto deste roteiro."
            />
        @else
            <ul class="space-y-2">
                @foreach ($audioRequests as $audioRequest)
                    <li class="flex flex-wrap items-center gap-2 text-sm">
                        <x-ui.badge :variant="$audioRequest->status->value === 'failed' ? 'danger' : ($audioRequest->status->value === 'success' ? 'success' : 'info')">{{ $audioRequest->status->label() }}</x-ui.badge>
                        <span class="t-small font-medium">{{ $audioRequest->voice ?? '—' }}</span>
                        @if ($audioRequest->status->value === 'success' && $audioRequest->mediaAsset?->url())
                            <audio src="{{ $audioRequest->mediaAsset->url() }}" controls preload="metadata" class="h-9 w-full max-w-64"></audio>
                            <span class="t-muted">{{ $audioRequest->mediaAsset->duration_seconds }}s · {{ $audioRequest->mediaAsset->mime_type }}</span>
                            <a href="{{ $audioRequest->mediaAsset->url() }}" target="_blank" rel="noopener" class="font-medium text-primary hover:text-primary-hover">Abrir áudio</a>
                        @endif
                        @if ($audioRequest->status->value === 'failed')
                            <span class="t-small">{{ match ($audioRequest->error_code) { 'timeout' => 'A geração excedeu o tempo esperado.', 'rate_limited' => 'O limite temporário foi atingido.', 'service_unavailable' => 'O serviço está temporariamente indisponível.', 'invalid_request' => 'Solicitação inválida para o provider.', 'invalid_audio' => 'O provider não retornou um áudio válido.', default => 'Não foi possível gerar a narração.' } }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
</div>
@endsection
