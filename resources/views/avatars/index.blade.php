@extends('layouts.app')

@section('title', 'Avatares')
@section('header', 'Avatares')
@section('content')
<x-ui.page-header
    title="Avatares"
    description="Identidades visuais utilizadas na criação consistente de conteúdo."
>
    <x-slot:actions>
        <x-ui.button :href="route('avatars.create')" variant="primary">Novo avatar</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if ($avatars->isEmpty())
    <x-ui.card>
        <x-ui.empty-state
            title="Nenhum avatar cadastrado"
            description="Cadastre a identidade visual que será usada futuramente na geração dos conteúdos."
            action-label="Criar avatar"
            :action-href="route('avatars.create')"
        />
    </x-ui.card>
@else
    <x-ui.table :headers="['Avatar', 'Idade aparente', 'Idioma', 'Mercado', 'Estilo', 'Status', 'Atualização', 'Ações']">
        <tbody>
            @foreach ($avatars as $avatar)
                <tr>
                    <td>
                        <a href="{{ route('avatars.show', $avatar) }}" class="font-medium text-primary hover:text-primary-hover">{{ $avatar->name }}</a>
                    </td>
                    <td>{{ $avatar->apparent_age ?? '—' }}</td>
                    <td>{{ $avatar->language ?? '—' }}</td>
                    <td>{{ $avatar->market ?? '—' }}</td>
                    <td>{{ $avatar->visual_style ? \Illuminate\Support\Str::limit($avatar->visual_style, 40) : '—' }}</td>
                    <td><x-ui.badge :variant="$avatar->status->badgeVariant()">{{ $avatar->status->label() }}</x-ui.badge></td>
                    <td class="whitespace-nowrap">{{ $avatar->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('avatars.show', $avatar) }}" class="font-medium text-primary hover:text-primary-hover">Ver</a>
                        <span class="mx-1 text-border" aria-hidden="true">·</span>
                        <a href="{{ route('avatars.edit', $avatar) }}" class="font-medium text-primary hover:text-primary-hover">Editar</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>

    @if ($avatars->hasPages())
        <div class="mt-4">{{ $avatars->links() }}</div>
    @endif
@endif
@endsection
