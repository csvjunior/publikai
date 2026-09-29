<?php

namespace App\Http\Controllers;

use App\Enums\AvatarStatus;
use App\Enums\PersonaStatus;
use App\Http\Requests\StoreSocialAccountRequest;
use App\Http\Requests\UpdateSocialAccountRequest;
use App\Models\Avatar;
use App\Models\Persona;
use App\Models\SocialAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SocialAccountController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', SocialAccount::class);

        $accounts = SocialAccount::latest()->paginate(15);

        return view('social-accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        $this->authorize('create', SocialAccount::class);

        return view('social-accounts.create', $this->identityOptions());
    }

    public function store(StoreSocialAccountRequest $request): RedirectResponse
    {
        $this->authorize('create', SocialAccount::class);

        $data = $request->validated();

        $this->authorize('updateStatus', [new SocialAccount, $data['status']]);

        $account = SocialAccount::create($data);

        return redirect()->route('social-accounts.show', $account)->with('status', 'Conta cadastrada.');
    }

    public function show(SocialAccount $socialAccount): View
    {
        $this->authorize('view', $socialAccount);

        $socialAccount->load(['defaultPersona', 'defaultAvatar']);

        return view('social-accounts.show', ['account' => $socialAccount]);
    }

    public function edit(SocialAccount $socialAccount): View
    {
        $this->authorize('update', $socialAccount);

        return view('social-accounts.edit', ['account' => $socialAccount] + $this->identityOptions());
    }

    public function update(UpdateSocialAccountRequest $request, SocialAccount $socialAccount): RedirectResponse
    {
        $this->authorize('update', $socialAccount);

        $data = $request->validated();

        if (($data['status'] ?? null) !== $socialAccount->status->value) {
            $this->authorize('updateStatus', [$socialAccount, $data['status']]);
        }

        $socialAccount->update($data);

        return redirect()->route('social-accounts.show', $socialAccount)->with('status', 'Conta atualizada.');
    }

    /**
     * Opções de identidade padrão: apenas registros ativos/pausados.
     * Arquivados seguem referenciáveis historicamente, mas não selecionáveis.
     *
     * @return array<string, array<int, string>>
     */
    protected function identityOptions(): array
    {
        return [
            'personaOptions' => Persona::where('status', '!=', PersonaStatus::Archived->value)
                ->orderBy('name')->pluck('name', 'id')->all(),
            'avatarOptions' => Avatar::where('status', '!=', AvatarStatus::Archived->value)
                ->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }
}
