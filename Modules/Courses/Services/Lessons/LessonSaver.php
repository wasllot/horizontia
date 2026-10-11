<?php

namespace Modules\Courses\Services\Lessons;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Courses\Models\Curriculum;

/** Registry of {@see LessonHandler}s keyed by stored type, and the single entry point to save a lesson's content. */
class LessonSaver
{
    /** @var array<string, LessonHandler> */
    private array $handlers = [];

    public function __construct()
    {
        foreach ([new VideoFileHandler(), new YoutubeHandler(), new VimeoHandler(), new GeniallyHandler(), new ArticleHandler(), new QuizHandler(), new ScormHandler(), new LiveHandler()] as $handler) {
            $this->handlers[$handler->type()] = $handler;
        }
    }

    public function handler(string $type): LessonHandler
    {
        return $this->handlers[$type] ?? throw new \InvalidArgumentException("Unknown lesson type [$type]");
    }

    /**
     * @param array $input  the editor state: yt_link, vm_link, genially_link, article, quiz[], live[], duration, files[]
     * @throws ValidationException errors are keyed `content.<field>` so the editor can show them next to the field
     */
    public function save(Curriculum $lesson, string $type, array $input, array $ctx = []): Curriculum
    {
        $handler = $this->handler($type);
        $ctx += ['has_content' => $lesson->type === $type && $handler->isComplete($lesson)];
        if ($type === 'live') {
            $ctx['existing_date'] = $lesson->liveStream?->date_time?->isPast() ? $lesson->liveStream->date_time : null;
        }

        $validator = Validator::make($input, $handler->rules($ctx), $handler->messages());
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->messages() as $key => $messages) {
                $errors['content.' . $key] = $messages[0];
            }
            throw ValidationException::withMessages($errors);
        }

        // When the lesson changes from one media type to another, the old private file must not be left behind.
        if ($lesson->type !== $type) {
            $this->discard($lesson);
        }

        $attrs = $handler->apply($lesson, $validator->validated() + ['files' => $input['files'] ?? []]);
        $lesson->forceFill($attrs + ['type' => $type])->save();

        return $lesson->refresh();
    }

    public function isComplete(Curriculum $lesson): bool
    {
        return isset($this->handlers[$lesson->type]) && $this->handlers[$lesson->type]->isComplete($lesson);
    }

    /** Free what the lesson owns outside the database (private video, extracted SCORM files, its live session). */
    public function discard(Curriculum $lesson): void
    {
        if ($lesson->type === 'video') {
            (new VideoFileHandler())->discard($lesson);
        } elseif ($lesson->type === 'scorm') {
            $this->purgeScorm($lesson);
        } elseif ($lesson->type === 'live') {
            $lesson->liveStream?->delete();
        }
    }

    private function purgeScorm(Curriculum $lesson): void
    {
        \Illuminate\Support\Facades\Storage::disk(getPrivateDisk())->deleteDirectory('scorm/' . $lesson->id);
        \Illuminate\Support\Facades\File::deleteDirectory(public_path('storage/scorm/' . $lesson->id));
    }
}
