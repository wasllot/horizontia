<?php

namespace App\Livewire\Pages\Admin\Community;

use App\Models\CommunityPost;
use App\Services\CommunityService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Courses\Models\Course;

/** Admin > Comunidad: mensajes reportados por los alumnos (descartar el reporte, ocultar o borrar). */
class Moderation extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;

    public function dismiss(int $id): void
    {
        $post = CommunityPost::findOrFail($id);
        \DB::table('community_reports')->where('post_id', $post->id)->delete();
        $post->update(['reports_count' => 0]);
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Reporte descartado.');
    }

    public function hide(int $id): void
    {
        CommunityPost::findOrFail($id)->update(['hidden_at' => now()]);
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Mensaje oculto para los alumnos.');
    }

    public function restore(int $id): void
    {
        $post = CommunityPost::findOrFail($id);
        $post->update(['hidden_at' => null, 'reports_count' => 0]);
        \DB::table('community_reports')->where('post_id', $post->id)->delete();
    }

    public function delete(int $id): void
    {
        $post = CommunityPost::findOrFail($id);
        CommunityPost::where('parent_id', $post->id)->delete();
        $post->delete();
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Mensaje borrado.');
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $posts = CommunityPost::query()->where(fn ($q) => $q->where('reports_count', '>', 0)->orWhereNotNull('hidden_at'))->with('author.profile')->orderByDesc('reports_count')->orderByDesc('id')->limit(100)->get();

        return view('livewire.pages.admin.community.moderation', [
            'posts' => $posts,
            'courses' => Course::whereIn('id', $posts->pluck('course_id'))->pluck('title', 'id'),
            'slugs' => Course::whereIn('id', $posts->pluck('course_id'))->pluck('slug', 'id'),
            'total' => CommunityPost::count(),
        ]);
    }
}
