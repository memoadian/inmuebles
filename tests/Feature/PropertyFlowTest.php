<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Database\Seeders\FeatureSeeder;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            PropertyTypeSeeder::class,
            FeatureSeeder::class,
            StateSeeder::class,
        ]);
    }

    private function agent(string $email = 'agente@test.com'): User
    {
        $user = User::factory()->create(['email' => $email, 'is_active' => true]);
        $user->assignRole('Agent');

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create(['email' => 'admin@test.com', 'is_active' => true]);
        $user->assignRole('Admin');

        return $user;
    }

    private function propertyPayload(array $overrides = []): array
    {
        return array_merge([
            'property_type_id' => PropertyType::first()->id,
            'title' => 'Casa en Del Valle con jardín',
            'description' => 'Amplia casa con jardín.',
            'operation' => 'sale',
            'price' => 4500000,
            'currency' => 'MXN',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'status' => 'draft',
        ], $overrides);
    }

    public function test_login_funciona_con_credenciales_validas(): void
    {
        $user = $this->agent();

        $response = $this->post('/dologin', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rechaza_cuenta_desactivada(): void
    {
        $user = $this->agent();
        $user->update(['is_active' => false]);

        $this->post('/dologin', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registro_publico_asigna_rol_client(): void
    {
        $this->post('/register', [
            'name' => 'Nuevo Cliente',
            'email' => 'cliente@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'cliente@test.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Client'));
    }

    public function test_agente_puede_crear_propiedad(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)
            ->post('/properties', $this->propertyPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('properties', [
            'title' => 'Casa en Del Valle con jardín',
            'slug' => 'casa-en-del-valle-con-jardin',
            'user_id' => $agent->id,
        ]);
    }

    public function test_agente_solo_ve_sus_propias_propiedades(): void
    {
        $mine = $this->agent('mio@test.com');
        $other = $this->agent('otro@test.com');

        Property::factory()->create(['user_id' => $mine->id, 'title' => 'Mi casa']);
        Property::factory()->create(['user_id' => $other->id, 'title' => 'Casa ajena']);

        $this->actingAs($mine)
            ->get('/properties')
            ->assertOk()
            ->assertSee('Mi casa')
            ->assertDontSee('Casa ajena');
    }

    public function test_agente_no_puede_editar_propiedad_ajena(): void
    {
        $mine = $this->agent('mio@test.com');
        $other = $this->agent('otro@test.com');

        $property = Property::factory()->create(['user_id' => $other->id]);

        $this->actingAs($mine)
            ->get("/properties/{$property->id}/edit")
            ->assertForbidden();
    }

    public function test_admin_puede_editar_propiedad_ajena(): void
    {
        $admin = $this->admin();
        $agent = $this->agent();

        $property = Property::factory()->create(['user_id' => $agent->id]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/edit")
            ->assertOk();
    }

    public function test_subir_foto_genera_original_y_thumbnail(): void
    {
        Storage::fake('public');

        $agent = $this->agent();
        $property = Property::factory()->create(['user_id' => $agent->id]);

        $this->actingAs($agent)
            ->post("/properties/{$property->id}/images", [
                'images' => [UploadedFile::fake()->image('fachada.jpg', 2400, 1600)],
            ])
            ->assertRedirect();

        $image = $property->images()->first();

        $this->assertNotNull($image);
        $this->assertTrue($image->is_cover, 'La primera foto debe quedar como portada');
        $this->assertSame('image/webp', $image->mime);
        $this->assertLessThanOrEqual(1920, $image->width, 'Se debe redimensionar el lado largo');

        Storage::disk('public')->assertExists($image->path);
        Storage::disk('public')->assertExists($image->thumb_path);
    }

    public function test_borrar_portada_promueve_la_siguiente_foto(): void
    {
        Storage::fake('public');

        $agent = $this->agent();
        $property = Property::factory()->create(['user_id' => $agent->id]);

        $this->actingAs($agent)->post("/properties/{$property->id}/images", [
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ]);

        $cover = $property->images()->where('is_cover', true)->first();

        $this->actingAs($agent)
            ->delete("/properties/{$property->id}/images/{$cover->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('property_images', ['id' => $cover->id]);
        $this->assertSame(1, $property->images()->where('is_cover', true)->count());
    }

    public function test_catalogo_publico_solo_muestra_publicadas(): void
    {
        $agent = $this->agent();

        Property::factory()->create([
            'user_id' => $agent->id,
            'title' => 'Publicada visible',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Property::factory()->create([
            'user_id' => $agent->id,
            'title' => 'Borrador oculto',
            'status' => 'draft',
        ]);

        $this->get('/propiedades')
            ->assertOk()
            ->assertSee('Publicada visible')
            ->assertDontSee('Borrador oculto');
    }

    public function test_cliente_no_puede_entrar_al_modulo_de_propiedades(): void
    {
        $client = User::factory()->create(['is_active' => true]);
        $client->assignRole('Client');

        $this->actingAs($client)
            ->get('/properties')
            ->assertForbidden();
    }
}
