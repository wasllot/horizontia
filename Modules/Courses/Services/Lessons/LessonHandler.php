<?php

namespace Modules\Courses\Services\Lessons;

use Modules\Courses\Models\Curriculum;

/** One strategy per stored lesson type: how its content is validated and persisted. */
interface LessonHandler
{
    /** The stored `type` this handler produces. */
    public function type(): string;

    /**
     * Validation rules for the posted content. `$ctx` carries what the rules need to know about the lesson
     * (e.g. ['has_content' => true] so re-saving does not force a new upload).
     *
     * @return array<string, mixed>
     */
    public function rules(array $ctx = []): array;

    /** @return array<string, string> */
    public function messages(): array;

    /**
     * Persist the validated content; return the attributes to store on the lesson row.
     *
     * @param array $data  validated content (plus `files` => [name => UploadedFile])
     * @return array<string, mixed>
     */
    public function apply(Curriculum $lesson, array $data): array;

    /** Does the stored lesson have everything a student needs? (drives the "incomplete" marker and the publish checklist) */
    public function isComplete(Curriculum $lesson): bool;
}
