<?php

namespace App\Livewire\Pages\Common\Messenger;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Mensajes: lista de conversaciones a la izquierda y el hilo abierto a la derecha. Se actualiza solo cada pocos segundos. */
class Messenger extends Component
{
    use WithFileUploads;

    #[Url(as: 'con')]
    public ?int $with = null;                 // abrir (o empezar) la conversación con esta persona

    #[Locked]
    public ?int $activeId = null;

    public string $body = '';
    public $upload = null;
    public string $search = '';
    public bool $newOpen = false;
    public string $contactSearch = '';
    public int $limit = 40;
    public ?string $error = null;

    public function mount(): void
    {
        abort_unless(ChatService::enabled(), 404);
        abort_unless(Auth::user()?->hasAnyRole(['student', 'tutor', 'admin', 'sub_admin']), 403);
        if ($this->with) {
            $this->startWith($this->with, silent: true);
        }
    }

    private function me(): User
    {
        return Auth::user();
    }

    private function service(): ChatService
    {
        return app(ChatService::class);
    }

    private function conversation(?int $id = null): ?ChatConversation
    {
        $c = ChatConversation::find($id ?? $this->activeId);

        return $c && $c->includes($this->me()) ? $c : null;
    }

    public function open(int $id): void
    {
        $c = $this->conversation($id) ?? abort(404);
        $this->activeId = $c->id;
        $this->limit = 40;
        $this->reset('body', 'upload', 'error', 'newOpen');
        $this->service()->markRead($c, $this->me());
        $this->dispatch('chat-scroll');
    }

    public function close(): void
    {
        $this->reset('activeId', 'with', 'body', 'upload', 'error');
    }

    public function startWith(int $userId, bool $silent = false): void
    {
        $other = User::find($userId);
        if (!$other || !$this->service()->canMessage($this->me(), $other)) {
            if (!$silent) {
                abort(403);
            }
            $this->with = null;

            return;
        }
        $c = $this->service()->conversationWith($this->me(), $other);
        $this->with = null;
        $this->open($c->id);
    }

    public function openNew(): void
    {
        $this->reset('contactSearch');
        $this->newOpen = true;
    }

    public function closeNew(): void
    {
        $this->newOpen = false;
    }

    public function updatedUpload(): void
    {
        $this->error = null;
        try {
            $this->validateOnly('upload', ['upload' => 'file|max:' . ChatService::MAX_FILE_KB . '|mimes:' . implode(',', ChatService::MIMES)], ['upload.max' => 'El archivo pesa más de 5 MB.', 'upload.mimes' => 'Tipo de archivo no permitido (imágenes, PDF, Word, Excel, PowerPoint o texto).', 'upload.file' => 'No se pudo subir el archivo.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error = $e->validator->errors()->first('upload');
            $this->upload = null;
        }
    }

    public function removeUpload(): void
    {
        $this->reset('upload', 'error');
    }

    public function send(): void
    {
        $c = $this->conversation() ?? abort(404);
        $this->error = null;
        $result = $this->service()->send($this->me(), $c, $this->body, $this->upload ?: null);
        if (!$result['ok']) {
            $this->error = $result['error'];

            return;
        }
        $this->reset('body', 'upload');
        $this->dispatch('chat-scroll');
    }

    public function deleteMessage(int $id): void
    {
        $m = ChatMessage::where('conversation_id', $this->conversation()?->id)->findOrFail($id);
        $this->service()->deleteOwn($this->me(), $m);
    }

    public function earlier(): void
    {
        $this->limit = min($this->limit + 40, 400);
    }

    /** Sondeo: el hilo abierto marca como leído lo que llegue mientras se mira. */
    public function refresh(): void
    {
        if ($c = $this->conversation()) {
            $this->service()->markRead($c, $this->me());
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $me = $this->me();
        $svc = $this->service();
        $conversations = $svc->conversations($me);
        $others = User::with('profile')->whereIn('id', $conversations->map(fn ($c) => $c->otherId($me)))->get()->keyBy('id');
        $unread = $svc->unreadByConversation($me);

        $term = mb_strtolower(trim($this->search));
        $list = $conversations->filter(fn ($c) => $term === '' || str_contains(mb_strtolower(ChatService::displayName($others[$c->otherId($me)] ?? null)), $term))->values();

        $active = $this->conversation();
        $data = ['me' => $me, 'list' => $list, 'others' => $others, 'unread' => $unread, 'active' => $active, 'svc' => $svc];
        if ($active) {
            $total = ChatMessage::where('conversation_id', $active->id)->count();
            $data['otherUser'] = $others[$active->otherId($me)] ?? User::with('profile')->find($active->otherId($me));
            $data['messages'] = ChatMessage::where('conversation_id', $active->id)->latest('id')->limit($this->limit)->get()->reverse()->values();
            $data['hasMore'] = $total > $data['messages']->count();
            $data['canReply'] = $svc->canMessage($me, $data['otherUser']);
        }
        $data['contacts'] = $this->newOpen ? $svc->contacts($me, $this->contactSearch) : collect();

        return view('livewire.pages.common.messenger.messenger', $data);
    }
}
