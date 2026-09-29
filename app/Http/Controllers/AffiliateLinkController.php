<?php

namespace App\Http\Controllers;

use App\Http\Requests\AffiliateLinkRequest;
use App\Models\AffiliateLink;
use App\Models\Product;
use App\Services\AffiliateLinkService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class AffiliateLinkController extends Controller
{
    use AuthorizesRequests;

    public function store(AffiliateLinkRequest $request, Product $product, AffiliateLinkService $service): RedirectResponse
    {
        $this->authorize('create', AffiliateLink::class);

        $data = $request->validated();
        $data['is_primary'] = $request->boolean('is_primary');

        $service->create($product, $data);

        return back()->with('status', 'Link de afiliado adicionado.');
    }

    public function update(AffiliateLinkRequest $request, Product $product, AffiliateLink $affiliateLink, AffiliateLinkService $service): RedirectResponse
    {
        $this->authorize('update', $affiliateLink);

        $data = $request->validated();
        $data['is_primary'] = $request->boolean('is_primary');

        $service->update($affiliateLink, $data);

        return back()->with('status', 'Link de afiliado atualizado.');
    }

    public function destroy(Product $product, AffiliateLink $affiliateLink): RedirectResponse
    {
        $this->authorize('delete', $affiliateLink);

        $affiliateLink->delete();

        return back()->with('status', 'Link de afiliado excluído.');
    }
}
