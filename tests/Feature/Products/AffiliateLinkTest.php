<?php

namespace Tests\Feature\Products;

use App\Enums\UserRole;
use App\Models\AffiliateLink;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateLinkTest extends TestCase
{
    use RefreshDatabase;

    private function validLink(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Principal US',
            'url' => 'https://example.com/afiliado/principal',
            'network' => 'Rede X',
            'market' => 'US',
            'notes' => null,
        ], $overrides);
    }

    public function test_affiliate_link_pode_ser_criado(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->post(
            route('affiliate-links.store', $product),
            $this->validLink(['is_primary' => true])
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('affiliate_links', [
            'product_id' => $product->id,
            'label' => 'Principal US',
            'is_primary' => true,
        ]);
        $this->assertTrue($product->fresh()->primaryLink()->exists());
    }

    public function test_affiliate_link_invalido_e_rejeitado(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->post(
            route('affiliate-links.store', $product),
            $this->validLink(['label' => '', 'url' => 'nao-e-url', 'market' => 'XX'])
        );

        $response->assertSessionHasErrors(['label', 'url', 'market']);
        $this->assertDatabaseCount('affiliate_links', 0);
    }

    public function test_apenas_um_link_principal_por_produto(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $first = AffiliateLink::factory()->for($product)->create(['is_primary' => true]);

        $this->actingAs($user)->post(
            route('affiliate-links.store', $product),
            $this->validLink(['label' => 'Instagram US', 'is_primary' => true])
        )->assertRedirect();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertSame(1, $product->affiliateLinks()->where('is_primary', true)->count());
    }

    public function test_marcar_novo_principal_remove_anterior(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $first = AffiliateLink::factory()->for($product)->create(['is_primary' => true]);
        $second = AffiliateLink::factory()->for($product)->create(['is_primary' => false]);

        $this->actingAs($user)->put(
            route('affiliate-links.update', [$product, $second]),
            $this->validLink(['label' => $second->label, 'is_primary' => true])
        )->assertRedirect();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_operator_nao_pode_excluir_affiliate_link(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $product = Product::factory()->create();
        $link = AffiliateLink::factory()->for($product)->create();

        $this->actingAs($operator)->delete(
            route('affiliate-links.destroy', [$product, $link])
        )->assertForbidden();

        $this->assertDatabaseHas('affiliate_links', ['id' => $link->id]);
    }

    public function test_admin_pode_excluir_affiliate_link(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $product = Product::factory()->create();
        $link = AffiliateLink::factory()->for($product)->create();

        $this->actingAs($admin)->delete(
            route('affiliate-links.destroy', [$product, $link])
        )->assertRedirect();

        $this->assertDatabaseMissing('affiliate_links', ['id' => $link->id]);
    }

    public function test_link_de_outro_produto_nao_e_acessivel(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $other = Product::factory()->create();
        $link = AffiliateLink::factory()->for($other)->create();

        $this->actingAs($user)->put(
            route('affiliate-links.update', [$product, $link]),
            $this->validLink()
        )->assertNotFound();
    }
}
