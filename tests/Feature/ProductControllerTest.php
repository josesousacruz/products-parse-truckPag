<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;

class ProductControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Product::truncate(); // limpa a collection antes de cada teste
    }

    public function test_index_returns_products()
    {
        Product::create(['code' => '1', 'product_name' => 'Teste', 'status' => 'published']);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_show_existing_product()
    {
        Product::create(['code' => '1', 'product_name' => 'Produto X', 'status' => 'published']);

        $response = $this->getJson('/api/products/1');

        $response->assertStatus(200)
                 ->assertJsonFragment(['product_name' => 'Produto X']);
    }

    public function test_update_product()
    {
        Product::create(['code' => '1', 'product_name' => 'Velho', 'status' => 'published']);

        $response = $this->putJson('/api/products/1', ['product_name' => 'Novo Nome']);

        $response->assertStatus(200)
                 ->assertJsonFragment(['product_name' => 'Novo Nome']);
    }

    public function test_destroy_product_sets_status_to_trash()
    {
        Product::create(['code' => '1', 'product_name' => 'Lixeira', 'status' => 'published']);

        $response = $this->deleteJson('/api/products/1');

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Produto movido para lixeira!']);
    }
}
