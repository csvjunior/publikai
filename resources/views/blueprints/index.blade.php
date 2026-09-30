@extends('layouts.app')

@section('title', 'Blueprints')
@section('header', 'Blueprints')
@section('content')
<x-ui.page-header
    title="Blueprints"
    description="Estruturas reutilizáveis para orientar a criação de conteúdos."
>
    <x-slot:actions>
        <x-ui.button :href="route('blueprints.create')" variant="primary">Novo Blueprint</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if ($blueprints->isEmpty())
    <x-ui.card>
        <x-ui.empty-state
            title="Nenhum Blueprint cadastrado"
            description="Crie estruturas reutilizáveis de conteúdo para acelerar a produção de roteiros e variações."
            action-label="Criar Blueprint"
            :action-href="route('blueprints.create')"
        />
    </x-ui.card>
@else
    <x-ui.table :headers="['Nome', 'Tipo', 'Objetivo', 'Idioma/Mercado', 'Origem', 'Status', 'Ações']">
        <tbody>
            @foreach ($blueprints as $blueprint)
                <tr>
                    <td>
                        <a href="{{ route('blueprints.show', $blueprint) }}" class="font-medium text-primary hover:text-primary-hover">{{ $blueprint->name }}</a>
                    </td>
                    <td>{{ $blueprint->content_type ? (config('references.content_types')[$blueprint->content_type] ?? $blueprint->content_type) : '—' }}</td>
                    <td>{{ $blueprint->objective ? \Illuminate\Support\Str::limit($blueprint->objective, 50) : '—' }}</td>
                    <td class="whitespace-nowrap">{{ $blueprint->language ?? '—' }} · {{ $blueprint->market ?? '—' }}</td>
                    <td>
                        @if ($blueprint->source_type === \App\Enums\ContentBlueprintSourceType::AiAssisted)
                            <x-ui.badge variant="ai">Assistido por IA</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral">Manual</x-ui.badge>
                        @endif
                    </td>
                    <td><x-ui.badge :variant="$blueprint->status->badgeVariant()">{{ $blueprint->status->label() }}</x-ui.badge></td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('blueprints.show', $blueprint) }}" class="font-medium text-primary hover:text-primary-hover">Ver</a>
                        <span class="mx-1 text-border" aria-hidden="true">·</span>
                        <a href="{{ route('blueprints.edit', $blueprint) }}" class="font-medium text-primary hover:text-primary-hover">Editar</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>

    @if ($blueprints->hasPages())
        <div class="mt-4">{{ $blueprints->links() }}</div>
    @endif
@endif
@endsection
