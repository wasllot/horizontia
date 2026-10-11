<?php

namespace App\Livewire\Frontend;

use App\Models\CommunityPost;
use App\Services\CommunityService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;

/**
 * Comunidad del curso: preguntas y respuestas entre quienes tienen acceso al curso y su instructor.
 * Todo se vuelve a comprobar en el servidor en cada acción (acceso, autoría, permisos de moderación).
 */
class CourseCommunity extends Component
{
    #[Locked]
    public int $courseId;

    public string $mode = 'list';          // list | new | thread
    public string $filter = 'all';         // all | unanswered | solved
    public string $search = '';
    public int $limit = 10;

    #[Locked]
    public ?int $openId = null;

    public string $title = '';
    public string $body = '';
    public ?int $lessonId = null;
    public string $replyBody = '';

    public function mount(int $courseId): void
    {
        $this->courseId = $courseId;
        $this->course();   // 403 si no tiene acceso
    }

    private function service(): CommunityService
    {
        return app(CommunityService::class);
    }

    private function course(): Course
    {
        $course = Course::findOrFail($this->courseId);
        abort_unless($this->service()->canJoin(Auth::user(), $course), 403);

        return $course;
    }

    private function moderator(): bool
    {
        return $this->service()->canModerate(Auth::user(), Course::findOrFail($this->courseId));
    }

    /** Publicación de este curso (visible) o 404. */
    private function post(int $id): CommunityPost
    {
        return CommunityPost::visible()->where('course_id', $this->courseId)->findOrFail($id);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'unanswered', 'solved'], true) ? $filter : 'all';
        $this->limit = 10;
    }

    public function updatedSearch(): void
    {
        $this->limit = 10;
    }

    public function more(): void
    {
        $this->limit = min($this->limit + 10, 200);
    }

    public function startNew(): void
    {
        $this->course();
        $this->resetValidation();
        $this->mode = 'new';
    }

    public function back(): void
    {
        $this->reset('openId', 'replyBody');
        $this->resetValidation();
        $this->mode = 'list';
    }

    public function open(int $id): void
    {
        $this->course();
        $this->openId = $this->post($id)->parent_id ? $this->post($id)->parent_id : $id;
        $this->mode = 'thread';
    }

    public function ask(): void
    {
        $course = $this->course();
        $this->validate([
            'title' => 'required|string|min:5|max:140',
            'body' => 'required|string|min:10|max:3000',
            'lessonId' => 'nullable|integer',
        ], ['title.required' => 'Escribe el título de tu pregunta.', 'title.min' => 'El título es muy corto.', 'body.required' => 'Cuéntanos tu duda.', 'body.min' => 'Explica un poco más tu duda (mínimo 10 caracteres).']);

        if ($msg = $this->service()->throttle(Auth::user())) {
            $this->addError('body', $msg);

            return;
        }
        $lesson = $this->lessonId ? Curriculum::whereHas('section', fn ($q) => $q->where('course_id', $course->id))->find($this->lessonId) : null;
        $post = $this->service()->ask(Auth::user(), $course, $this->title, $this->body, $lesson?->id);

        $this->reset('title', 'body', 'lessonId');
        $this->openId = $post->id;
        $this->mode = 'thread';
    }

    public function reply(): void
    {
        $course = $this->course();
        $root = $this->post((int) $this->openId);
        abort_if($root->parent_id, 404);
        $this->validate(['replyBody' => 'required|string|min:2|max:3000'], ['replyBody.required' => 'Escribe tu respuesta.', 'replyBody.min' => 'La respuesta es muy corta.']);

        if ($msg = $this->service()->throttle(Auth::user())) {
            $this->addError('replyBody', $msg);

            return;
        }
        $this->service()->reply(Auth::user(), $course, $root, $this->replyBody);
        $this->reset('replyBody');
    }

    public function like(int $id): void
    {
        $this->course();
        $post = $this->post($id);
        if ((int) $post->user_id !== (int) Auth::id()) {
            $this->service()->toggleLike(Auth::user(), $post);
        }
    }

    public function report(int $id): void
    {
        $this->course();
        $post = $this->post($id);
        if ((int) $post->user_id !== (int) Auth::id()) {
            $this->service()->report(Auth::user(), $post);
            $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Gracias, lo revisaremos.');
        }
    }

    public function delete(int $id): void
    {
        $this->course();
        $post = $this->post($id);
        abort_unless((int) $post->user_id === (int) Auth::id() || $this->moderator(), 403);

        CommunityPost::where('parent_id', $post->id)->delete();
        $post->delete();
        if ($post->parent_id === null) {
            $this->back();
        }
    }

    public function pin(int $id): void
    {
        $this->course();
        abort_unless($this->moderator(), 403);
        $post = $this->post($id);
        abort_if($post->parent_id, 404);
        $post->update(['pinned' => !$post->pinned]);
    }

    /** Marca (o desmarca) una respuesta como la solución; solo quien preguntó o quien modera. */
    public function markAnswer(int $id): void
    {
        $this->course();
        $reply = $this->post($id);
        $root = $reply->parent_id ? $this->post($reply->parent_id) : null;
        abort_unless($root, 404);
        abort_unless((int) $root->user_id === (int) Auth::id() || $this->moderator(), 403);

        $make = !$reply->is_answer;
        CommunityPost::where('parent_id', $root->id)->update(['is_answer' => false]);
        $reply->update(['is_answer' => $make]);
    }

    public function render()
    {
        $course = $this->course();
        $me = Auth::user();
        $data = ['course' => $course, 'moderator' => $this->moderator(), 'me' => $me, 'service' => $this->service()];

        if ($this->mode === 'thread' && $this->openId) {
            $root = CommunityPost::visible()->where('course_id', $course->id)->whereNull('parent_id')->with('author.profile')->find($this->openId);
            if (!$root) {
                $this->back();
            } else {
                $data['root'] = $root;
                $data['replies'] = $root->replies()->visible()->with('author.profile')->orderByDesc('is_answer')->orderBy('id')->get();
                $data['liked'] = \DB::table('community_likes')->where('user_id', $me->id)->whereIn('post_id', $data['replies']->pluck('id')->push($root->id))->pluck('post_id')->all();
                $data['lessonTitle'] = $root->curriculum_id ? Curriculum::find($root->curriculum_id)?->title : null;

                return view('livewire.frontend.course-community', $data);
            }
        }

        $q = CommunityPost::visible()->where('course_id', $course->id)->whereNull('parent_id')->with('author.profile')
            ->withCount(['replies as replies_count' => fn ($r) => $r->whereNull('hidden_at')])
            ->withExists(['replies as solved' => fn ($r) => $r->where('is_answer', true)->whereNull('hidden_at')]);
        if ($this->filter === 'unanswered') {
            $q->whereDoesntHave('replies', fn ($r) => $r->whereNull('hidden_at'));
        } elseif ($this->filter === 'solved') {
            $q->whereHas('replies', fn ($r) => $r->where('is_answer', true)->whereNull('hidden_at'));
        }
        if (trim($this->search) !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($this->search)) . '%';
            $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('body', 'like', $like));
        }
        $total = (clone $q)->count();
        $data['posts'] = $q->orderByDesc('pinned')->orderByDesc('last_activity_at')->orderByDesc('id')->limit($this->limit)->get();
        $data['total'] = $total;
        $data['lessons'] = $this->mode === 'new' ? Curriculum::whereHas('section', fn ($s) => $s->where('course_id', $course->id))->orderBy('section_id')->orderBy('sort_order')->get(['id', 'title']) : collect();

        return view('livewire.frontend.course-community', $data);
    }
}
