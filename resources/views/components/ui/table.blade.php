{{--
    Tabela global (Design System — implementada na Sprint 1, primeiro caso real: produtos).
    Uso:
    <x-ui.table :headers="['Produto', 'Status']">
        <tbody>
            <tr><td>...</td></tr>
        </tbody>
    </x-ui.table>
    Células herdam o padrão via .pk-table (design-system.css). Em telas menores,
    o scroll horizontal fica apenas neste wrapper, nunca no body.
--}}
@props(['headers' => []])

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-card border border-border bg-surface shadow-card']) }}>
    <table class="pk-table w-full min-w-[40rem] text-left">
        @if (count($headers) > 0)
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        {{ $slot }}
    </table>
</div>
