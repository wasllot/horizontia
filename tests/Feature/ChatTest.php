<?php

namespace Tests\Feature;

use App\Livewire\Pages\Common\Messenger\Messenger;
use App\Mail\ChatMessageEmail;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Mensajes: solo entre personas que comparten un curso (o con soporte), con adjuntos privados y lectura. */
class ChatTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.chat' => true]);
        Mail::fake();
        Storage::fake('local');
        Storage::fake('public');
        foreach ([$this->student(), $this->tutor(), $this->admin(), $this->demoStudent()] as $u) {
            RateLimiter::clear('chat:' . $u->id);
            Cache::flush();
        }
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function as($user)
    {
        return Livewire::actingAs($user)->test(Messenger::class);
    }

    private function say($from, $to, string $text)
    {
        $svc = app(ChatService::class);

        return $svc->send($from, $svc->conversationWith($from, $to), $text);
    }

    public function test_who_can_write_to_whom(): void
    {
        $svc = app(ChatService::class);
        $outsider = $this->demoStudent();
        Enrollment::where('student_id', $outsider->id)->whereIn('course_id', \Modules\Courses\Models\Course::where('instructor_id', $this->tutor()->id)->pluck('id'))->delete();

        $this->assertTrue($svc->canMessage($this->student(), $this->tutor()), 'student ↔ their instructor');
        $this->assertTrue($svc->canMessage($this->tutor(), $this->student()));
        $this->assertTrue($svc->canMessage($this->student(), $this->admin()), 'anyone ↔ support');
        $this->assertTrue($svc->canMessage($this->admin(), $outsider));
        $this->assertFalse($svc->canMessage($this->student(), $outsider), 'student ↔ student');
        $this->assertFalse($svc->canMessage($outsider, $this->tutor()), 'no shared course');
        $this->assertFalse($svc->canMessage($this->student(), $this->student()));
    }

    public function test_the_contact_list_only_offers_allowed_people(): void
    {
        $svc = app(ChatService::class);
        $ids = $svc->contacts($this->student())->pluck('id');

        $this->assertTrue($ids->contains($this->tutor()->id));
        $this->assertTrue($ids->contains($this->admin()->id));
        $this->assertFalse($ids->contains($this->demoStudent()->id));
        $this->assertFalse($ids->contains($this->student()->id));
    }

    public function test_sending_creates_one_conversation_per_pair_and_the_other_person_sees_it_unread(): void
    {
        $this->say($this->student(), $this->tutor(), 'Hola, tengo una duda');
        $this->say($this->tutor(), $this->student(), 'Dime');
        $this->say($this->student(), $this->tutor(), 'Gracias');

        $this->assertSame(1, ChatConversation::count());
        $svc = app(ChatService::class);
        $this->assertSame(2, $svc->unreadCount($this->tutor()), 'the tutor has not opened the thread: both student messages are unread');
        $this->assertSame(1, $svc->unreadCount($this->student()), 'and the student has the tutor\'s reply unread');
    }

    public function test_opening_a_thread_marks_the_messages_as_read_and_shows_the_ticks_to_the_sender(): void
    {
        $this->say($this->student(), $this->tutor(), 'Mensaje uno');
        $this->say($this->student(), $this->tutor(), 'Mensaje dos');
        $svc = app(ChatService::class);
        $this->assertSame(2, $svc->unreadCount($this->tutor()));

        $c = ChatConversation::firstOrFail();
        $this->as($this->tutor())->call('open', $c->id)->assertSee('Mensaje uno')->assertSee('Mensaje dos');

        $this->assertSame(0, $svc->unreadCount($this->tutor()));
        $this->as($this->student())->call('open', $c->id)->assertSee('✓✓');
    }

    public function test_the_app_can_start_a_conversation_from_a_link_and_refuses_forbidden_ones(): void
    {
        $this->as($this->student())->set('with', $this->tutor()->id)->assertSet('with', $this->tutor()->id);
        Livewire::actingAs($this->student())->withQueryParams(['con' => $this->tutor()->id])->test(Messenger::class)->assertSee('chat-thread', false);

        $outsider = $this->demoStudent();
        Livewire::actingAs($this->student())->withQueryParams(['con' => $outsider->id])->test(Messenger::class)->assertDontSee('chat-thread', false);
        $this->as($this->student())->call('startWith', $outsider->id)->assertForbidden();
    }

    public function test_the_composer_sends_text_trims_it_and_strips_html(): void
    {
        $c = app(ChatService::class)->conversationWith($this->student(), $this->tutor());
        $page = $this->as($this->student())->call('open', $c->id);

        $page->set('body', '  <b>Hola</b> <script>alert(1)</script>profe  ')->call('send')->assertSet('body', '');
        $m = ChatMessage::latest('id')->first();
        $this->assertSame('Hola alert(1)profe', $m->body);

        $page->set('body', '   ')->call('send')->assertSee('Escribe un mensaje');
        $page->set('body', str_repeat('a', 2500))->call('send');
        $this->assertSame(2000, mb_strlen(ChatMessage::latest('id')->value('body')));
    }

    public function test_messages_are_escaped_and_links_are_clickable_but_safe(): void
    {
        $html = ChatService::render("<img src=x onerror=alert(1)>\nVisita https://horizontia.test/cursos?a=1&b=2 y javascript:alert(1)");

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('<br', $html);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $html);
        $this->assertStringContainsString('href="https://horizontia.test/cursos?a=1&amp;b=2"', $html);
        $this->assertStringNotContainsString('href="javascript', $html);
    }

    public function test_nobody_reads_or_writes_in_someone_elses_conversation(): void
    {
        $c = app(ChatService::class)->conversationWith($this->student(), $this->tutor());
        $this->say($this->student(), $this->tutor(), 'Privado');

        $this->as($this->admin())->call('open', $c->id)->assertNotFound();
        $this->as($this->demoStudent())->call('open', $c->id)->assertNotFound();
        $this->assertSame(1, ChatMessage::count());
    }

    public function test_attachments_are_private_and_only_the_two_people_can_download_them(): void
    {
        $c = app(ChatService::class)->conversationWith($this->student(), $this->tutor());
        $this->as($this->student())->call('open', $c->id)->set('upload', UploadedFile::fake()->create('tarea.pdf', 200, 'application/pdf'))->set('body', 'Adjunto mi tarea')->call('send')->assertHasNoErrors();

        $m = ChatMessage::latest('id')->firstOrFail();
        $this->assertNotNull($m->file_path);
        $this->assertStringContainsString('tarea.pdf', $m->file_name);
        Storage::disk(ChatService::disk())->assertExists($m->file_path);
        Storage::disk('public')->assertMissing($m->file_path);

        $this->actingAs($this->tutor())->get(route('chat.file', $m->id))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->student())->get(route('chat.file', $m->id))->assertOk();
        $this->actingAs($this->demoStudent())->get(route('chat.file', $m->id))->assertNotFound();
        $this->actingAs($this->admin())->get(route('chat.file', $m->id))->assertNotFound();
    }

    public function test_dangerous_or_huge_files_are_rejected(): void
    {
        $c = app(ChatService::class)->conversationWith($this->student(), $this->tutor());
        $page = $this->as($this->student())->call('open', $c->id);

        $page->set('upload', UploadedFile::fake()->create('virus.php', 10, 'text/x-php'))->assertSet('upload', null)->assertSee('no permitido');
        $page->set('upload', UploadedFile::fake()->create('grande.pdf', 6000, 'application/pdf'))->assertSet('upload', null)->assertSee('5 MB');
        $this->assertSame(0, ChatMessage::count());
    }

    public function test_images_are_shown_inline_and_other_files_are_forced_to_download(): void
    {
        $c = app(ChatService::class)->conversationWith($this->student(), $this->tutor());
        $page = $this->as($this->student())->call('open', $c->id);
        $page->set('upload', UploadedFile::fake()->image('foto.png'))->call('send');
        $page->set('upload', UploadedFile::fake()->create('notas.docx', 20))->call('send');
        [$img, $doc] = ChatMessage::orderBy('id')->get()->all();

        $this->actingAs($this->tutor())->get(route('chat.file', $img->id))->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('inline', $this->actingAs($this->tutor())->get(route('chat.file', $img->id))->headers->get('Content-Disposition'));
        $res = $this->actingAs($this->tutor())->get(route('chat.file', $doc->id));
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
        $this->assertSame('application/octet-stream', $res->headers->get('Content-Type'));
    }

    public function test_only_the_sender_can_delete_and_the_file_goes_with_it(): void
    {
        $c = app(ChatService::class)->conversationWith($this->student(), $this->tutor());
        $this->as($this->student())->call('open', $c->id)->set('upload', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))->set('body', 'x')->call('send');
        $m = ChatMessage::firstOrFail();

        $this->as($this->tutor())->call('open', $c->id)->call('deleteMessage', $m->id)->assertForbidden();
        $this->assertNotNull(ChatMessage::find($m->id));

        $this->as($this->student())->call('open', $c->id)->call('deleteMessage', $m->id);
        $this->assertNull(ChatMessage::find($m->id));
        Storage::disk(ChatService::disk())->assertMissing($m->file_path);
        $this->assertNull($c->fresh()->last_message_at, 'an empty conversation leaves the list');
    }

    public function test_the_other_person_gets_one_email_per_hour_per_conversation(): void
    {
        $this->say($this->student(), $this->tutor(), 'Uno');
        $this->say($this->student(), $this->tutor(), 'Dos');
        $this->say($this->student(), $this->tutor(), 'Tres');

        Mail::assertQueued(ChatMessageEmail::class, 1);
        Mail::assertQueued(ChatMessageEmail::class, fn ($m) => $m->hasTo($this->tutor()->email));
    }

    public function test_sending_is_rate_limited(): void
    {
        $svc = app(ChatService::class);
        $c = $svc->conversationWith($this->student(), $this->tutor());
        for ($i = 0; $i < ChatService::PER_MINUTE; $i++) {
            $this->assertTrue($svc->send($this->student(), $c, "m$i")['ok']);
        }

        $this->assertFalse($svc->send($this->student(), $c, 'uno más')['ok']);
    }

    public function test_the_list_shows_unread_badges_previews_and_search_filters(): void
    {
        $this->say($this->tutor(), $this->student(), 'Revisa la lección 3');
        $this->say($this->admin(), $this->student(), 'Soporte te saluda');

        $page = $this->as($this->student());
        $page->assertSee('Revisa la lección 3')->assertSee('Soporte te saluda')->assertSee('hz-msg__badge', false);

        $name = ChatService::displayName($this->admin());
        $page->set('search', mb_substr($name, 0, 3))->assertSee('Soporte te saluda');
        $page->set('search', 'zzzxxx')->assertDontSee('Revisa la lección 3')->assertSee('No hay conversaciones');
    }

    public function test_the_header_badge_counts_unread_messages_and_the_chat_can_be_turned_off(): void
    {
        $this->say($this->tutor(), $this->student(), 'Hola');
        $this->actingAs($this->student())->getJson('/panel/pulso')->assertOk()->assertJsonPath('messages', 1);

        config(['academy.chat' => false]);
        Livewire::actingAs($this->student())->test(Messenger::class)->assertNotFound();
    }

    public function test_the_page_loads_for_every_role_and_requires_login(): void
    {
        foreach ([$this->student(), $this->tutor(), $this->admin()] as $u) {
            $this->actingAs($u)->get('/messenger')->assertOk()->assertSee('Mensajes');
        }
        auth()->logout();
        $this->get('/messenger')->assertRedirect();
    }
}
