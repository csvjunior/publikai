@extends('layouts.app')

@section('title', 'Personas')
@section('header', 'Personas')
@section('content')
<x-ui.page-header
    title="Personas"
    description="Identidades de comunicação utilizadas na produção de conteúdo."
>
    <x-slot:actions>
        <x-ui.button :href="route('personas.create')" variant="primary">Nova persona</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if ($personas->isEmpty())
    <x-ui.card>
        <x-ui.empty-state
            title="Nenhuma persona cadastrada"
            description="Crie uma identidade de comunicação para manter linguagem, tom e comportamento consistentes nos conteúdos."
            action-label="Criar persona"
            :action-href="route('personas.create')"
        />
    </x-ui.card>
@else
    <x-ui.table :headers="['Persona', 'Idioma', 'Mercado', 'Público', 'Status', 'Atualização', 'Ações']">
        <tbody>
            @foreach ($personas as $persona)
                <tr>
                    <td>
                        <a href="{{ route('personas.show', $persona) }}" class="font-medium text-primary hover:text-primary-hover">{{ $persona->name }}</a>
                    </td>
                    <td>{{ $persona->language ?? '—' }}</td>
                    <td>{{ $persona->market ?? '—' }}</td>
                    <td>{{ $persona->audience ? \Illuminate\Support\Str::limit($persona->audience, 60) : '—' }}</td>
                    <td><x-ui.badge :variant="$persona->status->badgeVariant()">{{ $persona->status->label() }}</x-ui.badge></td>
                    <td class="whitespace-nowrap">{{ $persona->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('personas.show', $persona) }}" class="font-medium text-primary hover:text-primary-hover">Ver</a>
                        <span class="mx-1 text-border" aria-hidden="true">·</span>
                        <a href="{{ route('personas.edit', $persona) }}" class="font-medium text-primary hover:text-primary-hover">Editar</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>

    @if ($personas->hasPages())
        <div class="mt-4">{{ $personas->links() }}</div>
    @endif
@endif
@endsection
