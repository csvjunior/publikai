@extends('layouts.app')

@section('title', 'Roteiros')
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    title="Roteiros"
    description="Roteiros estruturados para produção de conteúdos."
>
    <x-slot:actions>
        <x-ui.button :href="route('scripts.create')" variant="primary">Novo roteiro</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if ($scripts->isEmpty())
    <x-ui.card>
        <x-ui.empty-state
            title="Nenhum roteiro criado"
            description="Crie ou gere roteiros a partir de produtos, Blueprints e identidades aprovadas."
            action-label="Novo roteiro"
            :action-href="route('scripts.create')"
        />
    </x-ui.card>
@else
    <x-ui.table :headers="['Título', 'Produto', 'Blueprint', 'Persona', 'Idioma/Mercado', 'Origem', 'Status', 'Ações']">
        <tbody>
            @foreach ($scripts as $script)
                <tr>
                    <td>
                        <a href="{{ route('scripts.show', $script) }}" class="font-medium text-primary hover:text-primary-hover">{{ $script->title }}</a>
                    </td>
                    <td>{{ $script->product->name ?? '—' }}</td>
                    <td>{{ $script->blueprint->name ?? '—' }}</td>
                    <td>{{ $script->persona->name ?? '—' }}</td>
                    <td class="whitespace-nowrap">{{ $script->language ?? '—' }} · {{ $script->market ?? '—' }}</td>
                    <td>
                        @if ($script->generation_source === \App\Enums\ContentScriptSource::Ai)
                            <x-ui.badge variant="ai">IA</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral">Manual</x-ui.badge>
                        @endif
                    </td>
                    <td><x-ui.badge :variant="$script->status->badgeVariant()">{{ $script->status->label() }}</x-ui.badge></td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('scripts.show', $script) }}" class="font-medium text-primary hover:text-primary-hover">Ver</a>
                        @if ($script->isEditable())
                            <span class="mx-1 text-border" aria-hidden="true">·</span>
                            <a href="{{ route('scripts.edit', $script) }}" class="font-medium text-primary hover:text-primary-hover">Editar</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>

    @if ($scripts->hasPages())
        <div class="mt-4">{{ $scripts->links() }}</div>
    @endif
@endif
@endsection
