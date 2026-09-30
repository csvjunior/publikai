@extends('layouts.app')

@section('title', $profile->name)
@section('header', 'Referências')
@section('content')
<x-ui.page-header
    :title="$profile->name"
    :description="$profile->username ? '@'.$profile->username : null"
    :breadcrumbs="[['label' => 'Referências', 'url' => route('references.index')], ['label' => $profile->name]]"
>
    <x-slot:actions>
        <x-ui.button :href="route('references.edit', $profile)" variant="outline">Editar</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if (session('analysis_notice'))
    <x-ui.alert variant="warning" class="mb-6">{{ session('analysis_notice') }}</x-ui.alert>
@endif

@if (session('analysis_error'))
    <x-ui.alert variant="warning" class="mb-6">{{ session('analysis_error') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Perfil">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :variant="$profile->platform->badgeVariant()">{{ $profile->platform->label() }}</x-ui.badge>
            <x-ui.badge :variant="$profile->status->badgeVariant()">{{ $profile->status->label() }}</x-ui.badge>
            @if ($profile->market)
                <x-ui.badge variant="info">{{ $profile->market }}</x-ui.badge>
            @endif
            @if ($profile->language)
                <x-ui.badge variant="neutral">{{ config('locale-options.languages')[$profile->language] ?? $profile->language }}</x-ui.badge>
            @endif
        </div>

        <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Username</dt>
                <dd class="t-body mt-0.5 font-medium text-ink">{{ $profile->username ? '@'.$profile->username : '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Nicho</dt>
                <dd class="t-body mt-0.5">{{ $profile->niche ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">URL do perfil</dt>
                <dd class="t-body mt-0.5 min-w-0">
                    <span class="block truncate" title="{{ $profile->profile_url }}">{{ $profile->profile_url }}</span>
                    <a href="{{ $profile->profile_url }}" target="_blank" rel="noopener" class="font-medium text-primary hover:text-primary-hover">Abrir perfil</a>
                </dd>
            </div>
            @if ($profile->reason)
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Por que é referência</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $profile->reason }}</dd>
                </div>
            @endif
            @if ($profile->notes)
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Observações</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $profile->notes }}</dd>
                </div>
            @endif
        </dl>
    </x-ui.card>

    <x-ui.card title="Conteúdos de referência" description="Observações manuais de padrões. Só a URL basta para começar.">
        @if ($profile->referenceContents->isEmpty())
            <x-ui.empty-state
                title="Nenhum conteúdo ainda"
                description="Adicione a URL de um conteúdo deste perfil no formulário abaixo."
            />
        @else
            <ul class="space-y-4">
                @foreach ($profile->referenceContents as $content)
                    <li class="rounded-lg border border-border p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="t-card-title min-w-0 flex-1">
                                @if ($content->title)
                                    {{ $content->title }}
                                @else
                                    <span class="block truncate font-normal" title="{{ $content->url }}">{{ $content->url }}</span>
                                @endif
                            </p>
                            @if ($content->content_type)
                                <x-ui.badge variant="info">{{ config('references.content_types')[$content->content_type] ?? $content->content_type }}</x-ui.badge>
                            @endif
                            <x-ui.badge :variant="$content->status->badgeVariant()">{{ $content->status->label() }}</x-ui.badge>
                        </div>

                        <p class="t-body mt-1 min-w-0">
                            <span class="block truncate" title="{{ $content->url }}">{{ $content->url }}</span>
                            <a href="{{ $content->url }}" target="_blank" rel="noopener" class="font-medium text-primary hover:text-primary-hover">Abrir conteúdo</a>
                        </p>

                        @if ($content->observed_hook || $content->durationLabel())
                            <p class="t-small mt-1">
                                @if ($content->observed_hook)Hook: {{ $content->observed_hook }}@endif
                                @if ($content->observed_hook && $content->durationLabel()) · @endif
                                @if ($content->durationLabel()){{ $content->durationLabel() }}@endif
                            </p>
                        @endif

                        <div class="mt-3">
                            <details class="w-full">
                                <summary class="cursor-pointer text-sm font-medium text-primary hover:text-primary-hover">Ver análise e editar</summary>
                                <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                                    <div>
                                        <dt class="t-small font-medium uppercase tracking-wide">Estrutura</dt>
                                        <dd class="t-body mt-0.5 whitespace-pre-line">{{ $content->observed_structure ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="t-small font-medium uppercase tracking-wide">CTA</dt>
                                        <dd class="t-body mt-0.5">{{ $content->observed_cta ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="t-small font-medium uppercase tracking-wide">Estilo</dt>
                                        <dd class="t-body mt-0.5">{{ $content->observed_style ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="t-small font-medium uppercase tracking-wide">Performance</dt>
                                        <dd class="t-body mt-0.5 whitespace-pre-line">{{ $content->performance_notes ?? '—' }}</dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="t-small font-medium uppercase tracking-wide">Por que funciona</dt>
                                        <dd class="t-body mt-0.5 whitespace-pre-line">{{ $content->why_it_works ?? '—' }}</dd>
                                    </div>
                                </dl>
                                <form method="POST" action="{{ route('reference-contents.update', [$profile, $content]) }}" class="mt-3 space-y-3 border-t border-border pt-3" novalidate>
                                    @csrf
                                    @method('PUT')
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <x-ui.input label="URL" name="url" type="url" required :value="old('url', $content->url)" />
                                        <x-ui.input label="Título" name="title" type="text" :value="old('title', $content->title)" />
                                        <x-ui.select label="Tipo" name="content_type" :options="config('references.content_types')" placeholder="Selecionar…" :value="old('content_type', $content->content_type)" />
                                        <x-ui.input label="Duração (segundos)" name="duration_seconds" type="number" min="0" :value="old('duration_seconds', $content->duration_seconds)" />
                                        <x-ui.input label="Hook observado" name="observed_hook" type="text" :value="old('observed_hook', $content->observed_hook)" />
                                        <x-ui.input label="CTA observado" name="observed_cta" type="text" :value="old('observed_cta', $content->observed_cta)" />
                                        <x-ui.input label="Estilo observado" name="observed_style" type="text" :value="old('observed_style', $content->observed_style)" />
                                        @php
                                            $contentLocked = $content->isArchived()
                                                && \Illuminate\Support\Facades\Gate::denies('updateStatus', [$content, \App\Enums\ReferenceContentStatus::Active->value]);
                                            $contentStatusOptions = [];
                                            if (! $contentLocked) {
                                                foreach (\App\Enums\ReferenceContentStatus::cases() as $statusCase) {
                                                    if (\Illuminate\Support\Facades\Gate::allows('updateStatus', [$content, $statusCase->value])) {
                                                        $contentStatusOptions[$statusCase->value] = $statusCase->label();
                                                    }
                                                }
                                            }
                                        @endphp
                                        @if ($contentLocked)
                                            <input type="hidden" name="status" value="archived">
                                            <p class="t-small">Status arquivado — somente um administrador pode reativar.</p>
                                        @else
                                            <x-ui.select label="Status" name="status" :options="$contentStatusOptions" required :value="old('status', $content->status->value)" />
                                        @endif
                                    </div>
                                    <x-ui.input label="Estrutura observada" name="observed_structure" type="text" :value="old('observed_structure', $content->observed_structure)" />
                                    <x-ui.input label="Performance" name="performance_notes" type="text" :value="old('performance_notes', $content->performance_notes)" />
                                    <x-ui.input label="Por que funciona" name="why_it_works" type="text" :value="old('why_it_works', $content->why_it_works)" />
                                    <x-ui.button variant="secondary" size="sm">Salvar conteúdo</x-ui.button>
                                </form>
                            </details>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-6 border-t border-border pt-5">
            <h3 class="t-section-title">Novo conteúdo de referência</h3>
            <p class="t-small mt-1">Só a URL basta para começar; a análise pode ser preenchida depois.</p>
            <form method="POST" action="{{ route('reference-contents.store', $profile) }}" class="mt-3 space-y-3" novalidate>
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-ui.input label="URL" name="url" type="url" required />
                    </div>
                    <x-ui.input label="Título" name="title" type="text" />
                    <x-ui.select label="Tipo" name="content_type" :options="config('references.content_types')" placeholder="Selecionar…" />
                    <x-ui.input label="Duração (segundos)" name="duration_seconds" type="number" min="0" />
                    <x-ui.input label="Hook observado" name="observed_hook" type="text" />
                    <x-ui.input label="Estrutura observada" name="observed_structure" type="text" />
                    <x-ui.input label="CTA observado" name="observed_cta" type="text" />
                    <x-ui.input label="Estilo observado" name="observed_style" type="text" />
                    <div class="sm:col-span-2">
                        <x-ui.select label="Status" name="status" :options="['active' => 'Ativo', 'paused' => 'Pausado']" required value="active" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui.input label="Performance" name="performance_notes" type="text" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui.input label="Por que funciona" name="why_it_works" type="text" />
                    </div>
                </div>
                <x-ui.button variant="primary" size="sm">Adicionar conteúdo</x-ui.button>
            </form>
        </div>
    </x-ui.card>

    <x-ui.card title="Inteligência" description="Analisa os padrões registrados nos conteúdos desta referência.">
        @if ($featuredAnalysis)
            @include('references._analysis', ['analysis' => $featuredAnalysis])
        @else
            <x-ui.empty-state
                title="Nenhuma análise por IA realizada"
                description="As análises utilizam os padrões e observações cadastrados nos conteúdos de referência."
            />
        @endif

        <div class="mt-5 border-t border-border pt-4">
            @if ($aiConfigured)
                <form method="POST" action="{{ route('references.analyses.store', $profile) }}">
                    @csrf
                    <x-ui.button variant="ai" type="submit">Analisar com IA</x-ui.button>
                </form>
            @else
                <x-ui.button variant="ai" type="button" disabled>Analisar com IA</x-ui.button>
                <p class="t-small mt-2">Configure a IA em Sistema → IA para executar análises.</p>
            @endif
        </div>

        @if ($analyses->count() > 1 || ($analyses->count() === 1 && ! $featuredAnalysis?->is($analyses->first())))
            <div class="mt-5 border-t border-border pt-4">
                <h4 class="t-section-title">Histórico de análises</h4>
                <ul class="mt-2 space-y-2">
                    @foreach ($analyses as $item)
                        <li class="flex flex-wrap items-center gap-2 text-sm">
                            <x-ui.badge :variant="$item->status->badgeVariant()">{{ $item->status->label() }}</x-ui.badge>
                            <span class="text-ink-secondary">{{ $item->created_at?->display() }} · {{ $item->provider ?? '—' }} / {{ $item->model ?? '—' }}</span>
                            @if ($item->isSuccess() && ! is_null($item->confidence))
                                <span class="text-ink-secondary">{{ number_format($item->confidence * 100, 0) }}%</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
