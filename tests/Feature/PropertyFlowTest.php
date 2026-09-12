<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Database\Seeders\FeatureSeeder;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

    public function test_formulario_de_alta_carga_con_el_catalogo_agrupado(): void
    {
        $this->actingAs($this->agent())
            ->get('/properties/create')
            ->assertOk()
            ->assertSee('Amenidades')
            ->assertSee('Recreación')
            ->assertSee('Cancha de pádel');
    }

    public function test_guarda_los_campos_nuevos_de_la_ficha(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)->post('/properties', $this->propertyPayload([
            'condition' => 'excelente',
            'orientation' => 'poniente',
            'position' => 'exterior',
            'floors' => 3,
            'floor_number' => 5,
            'property_tax_estimate' => 12000,
            'services_estimate' => 1500,
            'is_exclusive' => '1',
            'rent_commission' => 'one_month',
            'sale_commission_percent' => 4.5,
        ]))->assertRedirect();

        $this->assertDatabaseHas('properties', [
            'user_id' => $agent->id,
            'condition' => 'excelente',
            'orientation' => 'poniente',
            'position' => 'exterior',
            'floors' => 3,
            'floor_number' => 5,
            'is_exclusive' => true,
            'rent_commission' => 'one_month',
            'sale_commission_percent' => 4.50,
        ]);
    }

    public function test_sin_permiso_las_comisiones_no_se_guardan(): void
    {
        $agent = $this->agent();
        $agent->removeRole('Agent');
        $agent->givePermissionTo('properties.view', 'properties.create', 'properties.edit');

        $this->actingAs($agent)->post('/properties', $this->propertyPayload([
            'rent_commission' => 'two_three_months',
            'sale_commission_percent' => 6,
        ]))->assertRedirect();

        $property = Property::where('user_id', $agent->id)->firstOrFail();

        $this->assertNull($property->rent_commission);
        $this->assertNull($property->sale_commission_percent);
    }

    public function test_condicion_invalida_se_rechaza(): void
    {
        $this->actingAs($this->agent())
            ->post('/properties', $this->propertyPayload(['condition' => 'seminueva']))
            ->assertSessionHasErrors('condition');
    }

    public function test_catalogo_publico_filtra_por_amenidad(): void
    {
        $agent = $this->agent();
        $alberca = Feature::where('slug', 'alberca')->firstOrFail();

        $conAlberca = Property::factory()->create([
            'user_id' => $agent->id,
            'title' => 'Casa con alberca',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $conAlberca->features()->attach($alberca);

        Property::factory()->create([
            'user_id' => $agent->id,
            'title' => 'Casa sin nada',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/propiedades?features[]='.$alberca->id)
            ->assertOk()
            ->assertSee('Casa con alberca')
            ->assertDontSee('Casa sin nada');
    }

    public function test_ficha_publica_muestra_datos_nuevos_y_mapa(): void
    {
        $agent = $this->agent();

        $property = Property::factory()->create([
            'user_id' => $agent->id,
            'title' => 'Departamento con vista',
            'status' => 'published',
            'published_at' => now(),
            'condition' => 'excelente',
            'orientation' => 'poniente',
            'position' => 'exterior',
            'floor_number' => 7,
            'latitude' => 19.4326,
            'longitude' => -99.1332,
            'maintenance_fee' => 2000,
            'services_estimate' => 800,
            'property_tax_estimate' => 6000,
            'sale_commission_percent' => 5,
        ]);

        $response = $this->get("/propiedades/{$property->slug}");

        $response->assertOk()
            ->assertSee('Ficha técnica')
            ->assertSee('Excelente')
            ->assertSee('Poniente (oeste)')
            ->assertSee('propertyMap', escape: false)
            ->assertSee('data-map-expand', escape: false)
            // Las comisiones jamás salen al catálogo público.
            ->assertDontSee('Comisión');
    }

    public function test_panel_muestra_la_propiedad_con_sus_datos_comerciales(): void
    {
        $agent = $this->agent();

        $property = Property::factory()->create([
            'user_id' => $agent->id,
            'is_exclusive' => true,
            'rent_commission' => 'one_month',
            'condition' => 'buena',
        ]);

        $this->actingAs($agent)
            ->get("/properties/{$property->id}")
            ->assertOk()
            ->assertSee('En exclusiva')
            ->assertSee('1 mes de renta')
            ->assertSee('Buena');
    }

    public function test_geocodificacion_traduce_una_direccion_a_coordenadas(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([[
                'lat' => '19.4326',
                'lon' => '-99.1332',
                'display_name' => 'Av. Paseo de la Reforma, Ciudad de México',
                'address' => ['postcode' => '06500'],
            ]]),
        ]);

        $this->actingAs($this->agent())
            ->postJson('/geocoding/search', ['address' => 'Paseo de la Reforma 100, Ciudad de México'])
            ->assertOk()
            ->assertJson(['results' => [[
                'latitude' => 19.4326,
                'longitude' => -99.1332,
                'postal_code' => '06500',
            ]]]);
    }

    public function test_el_buscador_del_mapa_devuelve_varios_candidatos(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '19.4', 'lon' => '-99.1', 'display_name' => 'Reforma, CDMX', 'address' => []],
                ['lat' => '20.6', 'lon' => '-103.3', 'display_name' => 'Reforma, Guadalajara', 'address' => []],
            ]),
        ]);

        $response = $this->actingAs($this->agent())
            ->postJson('/geocoding/search', ['address' => 'Avenida Reforma', 'limit' => 5])
            ->assertOk()
            ->assertJsonCount(2, 'results');

        $this->assertSame('Reforma, CDMX', $response->json('results.0.display_name'));

        Http::assertSent(fn ($request) => $request['limit'] === 5);
    }

    public function test_el_buscador_del_mapa_limita_los_candidatos_pedidos(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->postJson('/geocoding/search', ['address' => 'Avenida Reforma', 'limit' => 50])
            ->assertJsonValidationErrors('limit');
    }

    public function test_geocodificacion_requiere_sesion(): void
    {
        Http::fake();

        $this->post('/geocoding/search', ['address' => 'Paseo de la Reforma 100'])
            ->assertRedirect('/login');

        Http::assertNothingSent();
    }

    public function test_ficha_en_pdf_se_descarga_con_las_fotos(): void
    {
        Storage::fake('public');

        $agent = $this->agent();
        $property = Property::factory()->create([
            'user_id' => $agent->id,
            'status' => 'published',
            'published_at' => now(),
            'condition' => 'buena',
        ]);

        $this->actingAs($agent)->post("/properties/{$property->id}/images", [
            'images' => [UploadedFile::fake()->image('fachada.jpg', 1200, 800)],
        ]);

        $response = $this->get("/propiedades/{$property->slug}/ficha.pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString("{$property->slug}.pdf", $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        // DCTDecode = hay un JPEG incrustado; si dompdf hubiera descartado la
        // foto (no sabe leer WebP) el PDF saldría igual de válido pero sin ella.
        $this->assertStringContainsString('DCTDecode', $response->getContent());
    }

    /** @return Property */
    private function publicada(array $attributes = [])
    {
        return Property::factory()->create(array_merge([
            'user_id' => $this->agent('agente'.uniqid().'@test.com')->id,
            'status' => 'published',
            'published_at' => now(),
        ], $attributes));
    }

    public function test_la_portada_lleva_el_hero_y_el_catalogo_los_resultados(): void
    {
        $this->publicada(['title' => 'Casa publicada']);

        $this->get('/')->assertOk()->assertSee('Cada casa que ves');

        $this->get('/propiedades')
            ->assertOk()
            ->assertDontSee('Cada casa que ves')
            ->assertSee('Casa publicada')
            ->assertSee('resultados');
    }

    public function test_el_catalogo_ordena_por_precio(): void
    {
        $this->publicada(['title' => 'La barata', 'price' => 1_000_000]);
        $this->publicada(['title' => 'La cara', 'price' => 9_000_000]);

        $this->get('/propiedades?sort=price_desc')
            ->assertOk()
            ->assertSeeInOrder(['La cara', 'La barata']);

        $this->get('/propiedades?sort=price_asc')
            ->assertOk()
            ->assertSeeInOrder(['La barata', 'La cara']);
    }

    public function test_el_catalogo_filtra_por_la_zona_visible_del_mapa(): void
    {
        $this->publicada(['title' => 'Dentro del mapa', 'latitude' => 19.40, 'longitude' => -99.15]);
        $this->publicada(['title' => 'Fuera del mapa', 'latitude' => 25.68, 'longitude' => -100.31]);

        $this->get('/propiedades?bounds=19.0,-99.5,19.9,-98.9')
            ->assertOk()
            ->assertSee('Dentro del mapa')
            ->assertDontSee('Fuera del mapa')
            ->assertSee('En la zona del mapa');
    }

    public function test_un_orden_inventado_no_rompe_el_catalogo(): void
    {
        $this->publicada(['title' => 'Sigue apareciendo']);

        $this->get('/propiedades?sort='.urlencode('price; drop table properties'))
            ->assertOk()
            ->assertSee('Sigue apareciendo');
    }

    public function test_la_ficha_publica_ofrece_compartir_y_descargar(): void
    {
        $property = Property::factory()->create([
            'user_id' => $this->agent()->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get("/propiedades/{$property->slug}")
            ->assertOk()
            ->assertSee(route('public.properties.pdf', $property->slug))
            ->assertSee('wa.me', escape: false)
            ->assertSee('facebook.com/sharer', escape: false)
            ->assertSee('data-share-copy', escape: false);
    }

    public function test_la_ficha_en_pdf_de_un_borrador_no_existe(): void
    {
        $property = Property::factory()->create([
            'user_id' => $this->agent()->id,
            'status' => 'draft',
        ]);

        $this->get("/propiedades/{$property->slug}/ficha.pdf")->assertNotFound();
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
