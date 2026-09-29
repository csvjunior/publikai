<?php

namespace Tests\Feature\Products;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Produto X US',
            'description' => 'Oferta para o mercado americano.',
            'category' => 'Suplementos',
            'product_url' => 'https://example.com/produto-x',
            'price' => '99.90',
            'currency' => 'USD',
            'market' => 'US',
            'language' => 'en-US',
            'affiliate_network' => 'Rede X',
            'commission_type' => 'percent',
            'commission_value' => '15.00',
            'status' => ProductStatus::Active->value,
            'notes' => 'Observação interna.',
        ], $overrides);
    }

    public function test_usuario_autenticado_acessa_lista(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)->get('/products')
            ->assertOk()
            ->assertSee('Produtos')
            ->assertSee('Nenhum produto cadastrado', false);
    }

    public function test_guest_nao_acessa_produtos(): void
    {
        $this->get('/products')->assertRedirect('/login');
        $this->post('/products', $this->validData())->assertRedirect('/login');
    }

    public function test_produto_pode_ser_criado(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->post('/products', $this->validData());

        $product = Product::where('name', 'Produto X US')->firstOrFail();
        $this->assertSame('produto-x-us', $product->slug);
        $response->assertRedirect(route('products.show', $product));
        $this->assertDatabaseHas('products', ['slug' => 'produto-x-us', 'market' => 'US']);
    }

    public function test_dados_invalidos_sao_rejeitados(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/products', $this->validData([
            'name' => '',
            'product_url' => 'nao-e-url',
            'price' => '-5',
            'currency' => 'XXX',
            'market' => 'XX',
            'language' => 'xx',
            'status' => 'invalido',
        ]));

        $response->assertSessionHasErrors(['name', 'product_url', 'price', 'currency', 'market', 'language', 'status']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_url_invalida_e_rejeitada(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/products', $this->validData(['product_url' => 'nota-url']))
            ->assertSessionHasErrors('product_url');
    }

    public function test_produto_pode_ser_atualizado(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Nome Antigo', 'slug' => 'nome-antigo']);

        $response = $this->actingAs($user)->put(
            route('products.update', $product),
            $this->validData(['name' => 'Nome Novo'])
        );

        $response->assertRedirect(route('products.show', $product));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Nome Novo', 'slug' => 'nome-novo']);
    }

    public function test_admin_pode_arquivar_produto(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $product = Product::factory()->create(['status' => ProductStatus::Active]);

        $this->actingAs($admin)->put(
            route('products.update', $product),
            $this->validData(['status' => ProductStatus::Archived->value])
        )->assertRedirect(route('products.show', $product));

        $this->assertTrue($product->fresh()->isArchived());
    }

    public function test_operator_nao_pode_arquivar_produto(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $product = Product::factory()->create(['status' => ProductStatus::Active]);

        $this->actingAs($operator)->put(
            route('products.update', $product),
            $this->validData(['status' => ProductStatus::Archived->value])
        )->assertForbidden();

        $this->assertSame(ProductStatus::Active, $product->fresh()->status);
    }

    public function test_operator_nao_pode_arquivar_produto_pausado(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $product = Product::factory()->create(['status' => ProductStatus::Paused]);

        $this->actingAs($operator)->put(
            route('products.update', $product),
            $this->validData(['status' => ProductStatus::Archived->value])
        )->assertForbidden();

        $this->assertSame(ProductStatus::Paused, $product->fresh()->status);
    }

    public function test_operator_nao_pode_desarquivar_para_ativo(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $product = Product::factory()->create(['status' => ProductStatus::Archived]);

        $this->actingAs($operator)->put(
            route('products.update', $product),
            $this->validData(['status' => ProductStatus::Active->value])
        )->assertForbidden();

        $this->assertTrue($product->fresh()->isArchived());
    }

    public function test_operator_nao_pode_desarquivar_para_pausado(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $product = Product::factory()->create(['status' => ProductStatus::Archived]);

        $this->actingAs($operator)->put(
            route('products.update', $product),
            $this->validData(['status' => ProductStatus::Paused->value])
        )->assertForbidden();

        $this->assertTrue($product->fresh()->isArchived());
    }

    public function test_operator_pode_alternar_entre_ativo_e_pausado(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $active = Product::factory()->create(['status' => ProductStatus::Active]);
        $paused = Product::factory()->create(['status' => ProductStatus::Paused]);

        $this->actingAs($operator)->put(
            route('products.update', $active),
            $this->validData(['status' => ProductStatus::Paused->value])
        )->assertRedirect();

        $this->actingAs($operator)->put(
            route('products.update', $paused),
            $this->validData(['status' => ProductStatus::Active->value])
        )->assertRedirect();

        $this->assertSame(ProductStatus::Paused, $active->fresh()->status);
        $this->assertSame(ProductStatus::Active, $paused->fresh()->status);
    }

    public function test_admin_pode_desarquivar_produto(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $toActive = Product::factory()->create(['status' => ProductStatus::Archived]);
        $toPaused = Product::factory()->create(['status' => ProductStatus::Archived]);

        $this->actingAs($admin)->put(
            route('products.update', $toActive),
            $this->validData(['status' => ProductStatus::Active->value])
        )->assertRedirect();

        $this->actingAs($admin)->put(
            route('products.update', $toPaused),
            $this->validData(['status' => ProductStatus::Paused->value])
        )->assertRedirect();

        $this->assertSame(ProductStatus::Active, $toActive->fresh()->status);
        $this->assertSame(ProductStatus::Paused, $toPaused->fresh()->status);
    }
}
