<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\Invoices\Invoices;
use App\Models\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Facturas del administrador: se entiende qué se compró, los cursos gratis no parecen un error y el detalle usa el formato de moneda del sitio. */
class AdminInvoicesViewTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function order(float $amount, string $title, array $extra = [], bool $courseExists = true): Order
    {
        $course = Course::first();
        $order = Order::create($extra + [
            'user_id' => $this->student()->id, 'amount' => $amount, 'currency' => 'MXN', 'first_name' => 'Ana', 'last_name' => 'Pagado', 'email' => 'ana@ejemplo.test',
            'company' => '', 'country' => '', 'state' => '', 'postal_code' => '', 'city' => '', 'phone' => '', 'description' => '', 'payment_method' => 'mercadopago', 'status' => 'complete', 'unique_payment_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $order->id, 'title' => $title, 'quantity' => 1, 'price' => $amount, 'total' => $amount, 'platform_fee' => 0,
            'orderable_type' => Course::class, 'orderable_id' => $courseExists ? $course->id : 99999999, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $order;
    }

    public function test_the_list_shows_what_was_bought_even_if_the_course_no_longer_exists(): void
    {
        $this->order(0, 'Taller que ya no existe', [], false);

        Livewire::actingAs($this->admin())->test(Invoices::class)->assertSee('Taller que ya no existe')->assertSee('Curso');
    }

    public function test_free_orders_say_free_and_without_payment_instead_of_zero_dollars(): void
    {
        $this->order(0, 'Curso gratis de prueba');
        $this->order(899.1, 'Curso de pago de prueba', ['coupon_code' => 'BIENVENIDA10', 'discount_amount' => 99.9]);

        Livewire::actingAs($this->admin())->test(Invoices::class)
            ->assertSee('Gratis')->assertSee('Sin pago')->assertSee('Curso de pago de prueba')->assertSee('Cupón BIENVENIDA10')->assertSee('Ver detalle')->assertDontSee('>Preview<', false);
    }

    public function test_the_detail_shows_folio_coupon_total_and_no_made_up_address(): void
    {
        $order = $this->order(899.1, 'Respiración consciente', ['coupon_code' => 'BIENVENIDA10', 'discount_amount' => 99.9]);

        Livewire::actingAs($this->admin())->test(Invoices::class)->call('viewInvoice', $order->id)
            ->assertSee('Folio')->assertSee('#' . $order->id)->assertSee('Cupón BIENVENIDA10')->assertSee('Respiración consciente')->assertDontSee('Bangladesh');
    }
}
