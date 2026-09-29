<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSocialAccountRequest;
use App\Http\Requests\UpdateSocialAccountRequest;
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

        return view('social-accounts.create');
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

        return view('social-accounts.show', ['account' => $socialAccount]);
    }

    public function edit(SocialAccount $socialAccount): View
    {
        $this->authorize('update', $socialAccount);

        return view('social-accounts.edit', ['account' => $socialAccount]);
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
}
