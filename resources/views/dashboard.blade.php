@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Publikai · ferramenta interna</p>
        <h1 class="mt-1 text-2xl font-bold">Olá, {{ auth()->user()->name }}!</h1>
        <p class="mt-2 text-sm text-slate-600">
            O sistema está em fase inicial (Sprint 0.1). A fundação técnica — autenticação interna,
            shell administrativo e documentação — está pronta. Os módulos de operação serão construídos nas próximas sprints.
        </p>
        <p class="mt-2 text-xs text-slate-500">Seu perfil: <span class="font-semibold">{{ auth()->user()->role->value }}</span></p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (['Conteúdos' => 'Roteiros, ideias e peças ainda não existem.', 'Publicações' => 'Nenhum agendamento realizado até o momento.', 'Métricas' => 'Sem dados de desempenho coletados.'] as $title => $desc)
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-semibold">{{ $title }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $desc }}</p>
                <p class="mt-3 inline-block rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200">Ainda sem dados</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
