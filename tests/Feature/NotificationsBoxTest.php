<?php

namespace Tests\Feature;

use App\Livewire\Pages\Common\Notifications;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Caja de notificaciones: nunca muestra claves de traducción sin resolver y marca como leídas. */
class NotificationsBoxTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    private function notify(array $data, bool $read = false): string
    {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id, 'type' => 'tmp', 'notifiable_type' => \App\Models\User::class, 'notifiable_id' => $this->student()->id,
            'data' => json_encode($data), 'read_at' => $read ? now() : null, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_untranslated_link_keys_and_html_never_reach_the_screen(): void
    {
        $this->notify(['subject' => 'Reserva', 'content' => '<b>Hola</b> <script>x()</script>mundo', 'has_link' => true, 'link_target' => '/cursos', 'link_text' => 'quiz::quiz.session_booking']);

        Livewire::actingAs($this->student())->test(Notifications::class)
            ->assertDontSee('quiz::quiz.session_booking')
            ->assertSee('Ver detalles')
            ->assertDontSee('<script', false)
            ->assertSee('Hola');
    }

    public function test_mark_as_read_and_mark_all(): void
    {
        $student = $this->student();
        $a = $this->notify(['subject' => 'Una']);
        $this->notify(['subject' => 'Dos']);

        $c = Livewire::actingAs($student)->test(Notifications::class)->assertSee(__('general.mark_all_as_read'));
        $c->call('markAsRead', $a);
        $this->assertNotNull(DB::table('notifications')->where('id', $a)->value('read_at'));

        $c->call('markAllAsRead');
        $this->assertSame(0, $student->unreadNotifications()->count());
    }

    public function test_empty_state_is_friendly(): void
    {
        DB::table('notifications')->where('notifiable_id', $this->student()->id)->delete();

        Livewire::actingAs($this->student())->test(Notifications::class)->assertSee(__('general.all_caught_up'));
    }
}
