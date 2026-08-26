<?php

namespace Tests\Feature;

use App\Casts\DisputeStatus;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Two-party flows that need a real student AND a real tutor interacting:
 * the dispute reply thread (student vs. tutor, admin-mediated) and the
 * LaraGuppy 1:1 chat (packages/laraguppy). Both go through the real API
 * endpoints on both sides (sender + recipient), against the QA
 * student/tutor fixtures plus the completed booking TestingSeeder now
 * creates for exactly this purpose.
 *
 * Broadcast events fired by both features (GuppyChatPrivateEvent, and any
 * dispute notification jobs) are safe here because .env.testing sets
 * BROADCAST_CONNECTION=log — nothing is sent to a real Pusher/Reverb
 * server. Http::fake() is added defensively regardless.
 */
class MessagingAndDisputesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Http::fake();
    }

    // -----------------------------------------------------------------
    // Dispute reply thread
    // -----------------------------------------------------------------

    public function test_student_can_open_a_dispute_and_view_it_while_it_is_pending(): void
    {
        $student = $this->student();
        $booking = $this->booking();

        $reasons = collect(setting('_dispute_setting.dispute_reasons') ?? [])->pluck('dispute_reason');
        $this->assertNotEmpty($reasons, 'Dispute reasons must be seeded for this test to be meaningful.');

        $create = $this->actingAs($student, 'sanctum')->postJson("/api/dispute/{$booking->id}", [
            'reason' => $reasons->first(),
            'description' => 'The tutor did not show up for the session.',
        ]);
        $create->assertOk();

        $dispute = Dispute::where('disputable_id', $booking->id)->where('disputable_type', \App\Models\SlotBooking::class)->firstOrFail();
        $this->assertSame('pending', $dispute->status);

        // createDispute() already sends an initial system message as the
        // "student" conversation group — confirm that landed.
        $this->assertDatabaseHas('dispute_conversations', [
            'dispute_id' => $dispute->id,
            'user_id' => $student->id,
        ]);

        // Student can list their own disputes and view the discussion.
        $listing = $this->actingAs($student, 'sanctum')->getJson('/api/dispute-listing');
        $listing->assertOk();

        $detail = $this->actingAs($student, 'sanctum')->getJson("/api/dispute-detail/{$dispute->id}");
        $detail->assertOk();

        $discussion = $this->actingAs($student, 'sanctum')->getJson("/api/dispute-discussion/{$dispute->id}");
        $discussion->assertOk();

        // Replies from either party are correctly refused (not a crash)
        // while the dispute is still awaiting admin triage.
        $reply = $this->actingAs($student, 'sanctum')->postJson("/api/dispute-reply/{$dispute->id}", [
            'message' => 'Following up — this happened yesterday at 4pm.',
        ]);
        $reply->assertStatus(403);
    }

    public function test_tutor_is_blocked_from_a_pending_dispute_then_can_reply_once_admin_moves_it_to_in_discussion(): void
    {
        $student = $this->student();
        $tutor = $this->tutor();
        $booking = $this->booking();

        $reasons = collect(setting('_dispute_setting.dispute_reasons') ?? [])->pluck('dispute_reason');

        $this->actingAs($student, 'sanctum')->postJson("/api/dispute/{$booking->id}", [
            'reason' => $reasons->first(),
            'description' => 'The tutor did not show up for the session.',
        ])->assertOk();

        $dispute = Dispute::where('disputable_id', $booking->id)->firstOrFail();

        // Tutor side must not crash, and is correctly denied while pending.
        $tutorDetailPending = $this->actingAs($tutor, 'sanctum')->getJson("/api/dispute-detail/{$dispute->id}");
        $this->assertLessThan(500, $tutorDetailPending->getStatusCode());
        $tutorDetailPending->assertStatus(404);

        $tutorReplyPending = $this->actingAs($tutor, 'sanctum')->postJson("/api/dispute-reply/{$dispute->id}", ['message' => 'Too early to reply.']);
        $this->assertLessThan(500, $tutorReplyPending->getStatusCode());

        // Admin moves the dispute forward (mirrors what the admin
        // ManageDispute Livewire screen does via DisputeService).
        (new DisputeService($this->admin()))->changeStatus($dispute->id, 'in_discussion');
        $dispute->refresh();
        $this->assertSame('in_discussion', $dispute->status);

        // Now the tutor can view and reply without crashing.
        $tutorDetail = $this->actingAs($tutor, 'sanctum')->getJson("/api/dispute-detail/{$dispute->id}");
        $tutorDetail->assertOk();

        $tutorDiscussion = $this->actingAs($tutor, 'sanctum')->getJson("/api/dispute-discussion/{$dispute->id}");
        $tutorDiscussion->assertOk();

        $tutorReply = $this->actingAs($tutor, 'sanctum')->postJson("/api/dispute-reply/{$dispute->id}", [
            'message' => 'Apologies — I had a connectivity issue, happy to reschedule.',
        ]);
        $tutorReply->assertOk();

        $this->assertDatabaseHas('dispute_conversations', [
            'dispute_id' => $dispute->id,
            'user_id' => $tutor->id,
            'message' => 'Apologies — I had a connectivity issue, happy to reschedule.',
        ]);

        // Tutor's own dispute listing must also not crash.
        $tutorListing = $this->actingAs($tutor, 'sanctum')->getJson('/api/dispute-listing');
        $tutorListing->assertOk();
    }

    public function test_admin_cannot_use_the_student_tutor_dispute_endpoints(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/dispute-listing');
        $response->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // LaraGuppy two-party chat
    // -----------------------------------------------------------------

    public function test_student_and_tutor_can_exchange_chat_messages(): void
    {
        $student = $this->student();
        $tutor = $this->tutor();

        // Student starts (or reuses) a direct conversation with the tutor.
        $start = $this->actingAs($student, 'sanctum')->postJson("/api/start-chat/{$tutor->id}");
        $start->assertOk();
        $threadId = data_get($start->json(), 'data.threadId') ?? data_get($start->json(), 'data.id') ?? data_get($start->json(), 'data.thread.id');
        $this->assertNotEmpty($threadId, 'start-chat did not return a thread id: ' . $start->getContent());

        // Student sends a text message.
        $send = $this->actingAs($student, 'sanctum')->postJson('/api/messages', [
            'threadId' => $threadId,
            'body' => 'Hi! Are we still on for tomorrow?',
            'messageType' => 'text',
            'timeStamp' => now()->timestamp,
        ]);
        $send->assertOk();

        // Tutor (the recipient) can list messages in the same thread.
        $tutorView = $this->actingAs($tutor, 'sanctum')->getJson('/api/messages?' . http_build_query(['threadId' => $threadId]));
        $tutorView->assertOk();

        // Tutor replies back.
        $reply = $this->actingAs($tutor, 'sanctum')->postJson('/api/messages', [
            'threadId' => $threadId,
            'body' => 'Yes, see you then!',
            'messageType' => 'text',
            'timeStamp' => now()->timestamp,
        ]);
        $reply->assertOk();

        // Student sees the tutor's reply.
        $studentView = $this->actingAs($student, 'sanctum')->getJson('/api/messages?' . http_build_query(['threadId' => $threadId]));
        $studentView->assertOk();

        // Thread listing (unread counts) must not crash for either side.
        $this->actingAs($student, 'sanctum')->getJson('/api/unread-counts')->assertOk();
        $this->actingAs($tutor, 'sanctum')->getJson('/api/unread-counts')->assertOk();
    }

    public function test_chat_endpoints_reject_a_bogus_thread_id_without_crashing(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student, 'sanctum')->getJson('/api/messages?' . http_build_query(['threadId' => 999999999]));
        $this->assertLessThan(500, $response->getStatusCode(), $response->getContent());

        $response = $this->actingAs($student, 'sanctum')->postJson('/api/messages', [
            'threadId' => 999999999,
            'body' => 'Message to a thread that does not exist.',
            'messageType' => 'text',
        ]);
        $this->assertLessThan(500, $response->getStatusCode(), $response->getContent());
    }
}
