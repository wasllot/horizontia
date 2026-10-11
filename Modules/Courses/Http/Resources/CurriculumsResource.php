<?php

namespace Modules\Courses\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Services\Lessons\QuizService;

class CurriculumsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        $canSeeMedia = $request->route()?->getName() == 'course-taking' || $this->is_preview;
        $media_path = null;
        if ($canSeeMedia && !empty($this->media_path)) {
            // Private lesson files get a short-lived URL; external links (YouTube/Vimeo/...) pass through untouched.
            $media_path = $this->type === 'video' ? lessonMediaUrl($this->media_path) : $this->media_path;
        }

        // Text lessons and quizzes are paid content too: only the classroom call (the controller already checked access)
        // and free previews get them, and a quiz never carries its answer key or explanations.
        $article = null;
        if ($canSeeMedia && $this->resource->offsetExists('article_content') && $this->article_content !== null) {
            $article = $this->type === 'assignment'
                ? json_encode((new QuizService())->forStudent((new QuizService())->load($this->article_content)), JSON_UNESCAPED_UNICODE)
                : $this->article_content;
        }

        return [
            'id'                        => $this->whenHas('id'),
            'title'                     => $this->whenHas('title'),
            'type'                      => $this->whenHas('type'),
            'media_path'                => $media_path,
            'is_preview'                => $this->whenHas('is_preview'),
            'is_watched'                => $this->watchtime?->duration == $this->content_length,
            'article_content'           => $article,
            'content_length'            => getCourseDuration($this->content_length),
            'description'               => $this->whenHas('description'),
        
        ];

    }
}