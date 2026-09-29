@extends('layouts.app')

@section('title', 'Referências')
@section('header', 'Referências')
@section('content')
<x-ui.page-header
    title="Referências"
    description="Perfis e conteúdos utilizados como referência para orientar a produção do Publikai."
>
    <x-slot:actions>
        <x-ui.button :href="route('references.create')" variant="primary">Nova referência</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if ($profiles->isEmpty())
    <x-ui.card>
        <x-ui.empty-state
            title="Nenhuma referência cadastrada"
            description="Cadastre perfis que estejam produzindo conteúdos relevantes para o seu nicho ou produto."
            action-label="Cadastrar referência"
            :action-href="route('references.create')"
        />
    </x-ui.card>
@else
    <x-ui.table :headers="['Perfil', 'Plataforma', 'Mercado', 'Idioma', 'Nicho', 'Conteúdos', 'Status', 'Ações']">
        <tbody>
            @foreach ($profiles as $profile)
                <tr>
                    <td>
                        <a href="{{ route('references.show', $profile) }}" class="font-medium text-primary hover:text-primary-hover">{{ $profile->name }}</a>
                        @if ($profile->username)
                            <p class="t-small mt-0.5">{{ '@'.$profile->username }}</p>
                        @endif
                    </td>
                    <td><x-ui.badge :variant="$profile->platform->badgeVariant()">{{ $profile->platform->label() }}</x-ui.badge></td>
                    <td>{{ $profile->market ?? '—' }}</td>
                    <td>{{ $profile->language ?? '—' }}</td>
                    <td>{{ $profile->niche ? \Illuminate\Support\Str::limit($profile->niche, 40) : '—' }}</td>
                    <td>{{ $profile->reference_contents_count }}</td>
                    <td><x-ui.badge :variant="$profile->status->badgeVariant()">{{ $profile->status->label() }}</x-ui.badge></td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('references.show', $profile) }}" class="font-medium text-primary hover:text-primary-hover">Ver</a>
                        <span class="mx-1 text-border" aria-hidden="true">·</span>
                        <a href="{{ route('references.edit', $profile) }}" class="font-medium text-primary hover:text-primary-hover">Editar</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>

    @if ($profiles->hasPages())
        <div class="mt-4">{{ $profiles->links() }}</div>
    @endif
@endif
@endsection
