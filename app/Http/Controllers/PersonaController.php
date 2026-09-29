<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;
use App\Models\Persona;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PersonaController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Persona::class);

        $personas = Persona::latest()->paginate(15);

        return view('personas.index', compact('personas'));
    }

    public function create(): View
    {
        $this->authorize('create', Persona::class);

        return view('personas.create');
    }

    public function store(StorePersonaRequest $request): RedirectResponse
    {
        $this->authorize('create', Persona::class);

        $data = $request->validated();

        $this->authorize('updateStatus', [new Persona, $data['status']]);

        $persona = Persona::create($data);

        return redirect()->route('personas.show', $persona)->with('status', 'Persona cadastrada.');
    }

    public function show(Persona $persona): View
    {
        $this->authorize('view', $persona);

        return view('personas.show', compact('persona'));
    }

    public function edit(Persona $persona): View
    {
        $this->authorize('update', $persona);

        return view('personas.edit', compact('persona'));
    }

    public function update(UpdatePersonaRequest $request, Persona $persona): RedirectResponse
    {
        $this->authorize('update', $persona);

        $data = $request->validated();

        if (($data['status'] ?? null) !== $persona->status->value) {
            $this->authorize('updateStatus', [$persona, $data['status']]);
        }

        $persona->update($data);

        return redirect()->route('personas.show', $persona)->with('status', 'Persona atualizada.');
    }
}
