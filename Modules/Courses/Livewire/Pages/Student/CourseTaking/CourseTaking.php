<?php

namespace Modules\Courses\Livewire\Pages\Student\CourseTaking;

use Modules\Courses\Services\CourseService;
use Modules\Courses\Services\CurriculumService;
use Modules\Assignments\Services\AssignemntsService;
use Modules\Quiz\Services\QuizService;
use Illuminate\Support\Facades\Auth;
use App\Services\CourseCertificateService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Courses\Services\CourseAccessService;
use App\Jobs\SendNotificationJob;
use App\Jobs\SendDbNotificationJob;
use Illuminate\Support\Str;
use Modules\Assignments\Models\Assignment;
use Modules\Quiz\Models\Quiz;
use App\Models\User;
use Livewire\Component;
use Modules\Courses\Models\Curriculum as CurriculumModel;
use Modules\Courses\Services\LiveSessionService;
use Modules\Courses\Services\Lessons\QuizService as LessonQuizService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\WithFileUploads;
use Modules\Courses\Models\AssignmentSubmission;

class CourseTaking extends Component
{
    use WithFileUploads;

    public $logo;
    #[Locked]
    public $activeCurriculum;
    public $instructorCoursesCount;
    public $slug;
    public $rating;
    public $description;
    public $isLoading = true;
    public $progress;
    public ?string $certificateUrl = null;
    public $studentRating;
    public $role;
    public $backRoute = null;
    public $curriculumOrder = [];
    public $nextCurriculumItem = [];
    public $assignment_comment;
    public $assignment_file;
    public $socialIcons = [
        'Facebook' => 'am-icon-facebook-1',
        'X/Twitter' => 'am-icon-twitter-02',
        'LinkedIn' => 'am-icon-linkedin-02',
        'Instagram' => 'am-icon-instagram',
        'Pinterest' => 'am-icon-pinterest',
        'YouTube' => 'am-icon-youtube',
        'TikTok' => 'am-icon-tiktok-02',
        'WhatsApp' => 'am-icon-whatsapp',
    ];

    #[Url]
    public $redirect = 'courses-list';

    /** ?leccion=ID opens that lesson; without it a student resumes at the first lesson they have not completed. */
    #[Url(as: 'leccion')]
    public $startLesson = null;

    public function mount()
    {
        $this->slug = request()->route('slug');
        $this->role = Auth::user()->role;
        // The admin-configurable '_general.logo_white' setting still points at
        // the base theme's stock demo logo (never replaced with a real branded
        // asset), so it's not trustworthy here. Use the same real, dark-background-
        // ready Horizontia asset login.blade.php already relies on instead.
        $this->logo = webpAsset('images/nuevo/Logo-Horizontia-footer.png');

        if (!$this->course) {
            abort(404);
        }

        if ($this->role == 'tutor' && $this->course?->instructor_id != Auth::id()) {
            return $this->redirect(route('courses.course-detail', ['slug' => $this->course->slug]));
        }

        if ($this->role == 'student' && !app(CourseAccessService::class)->canAccessCourse(Auth::user(), $this->course)) {
            return $this->redirect(route('courses.search-courses'));
        }

        if (!empty($this->course->course_watchtime_sum_duration) && !empty($this->course->content_length)) {
            $progress = floor(($this->course->course_watchtime_sum_duration / $this->course->content_length) * 100);
            $this->progress = $progress >= 99 ? 100 : $progress;
        }
        $this->syncCertificate();

        $firstCurriculum = $this->course?->sections?->first()?->curriculums?->first();
        // Resume point: an explicit lesson of this course, else (students) the first one not completed yet.
        $allLessons = $this->course?->sections?->flatMap(fn ($section) => $section->curriculums) ?? collect();
        if (!empty($this->startLesson) && ($wanted = $allLessons->firstWhere('id', (int) $this->startLesson))) {
            $firstCurriculum = $wanted;
        } elseif ($this->role == 'student' && ($resume = app(\Modules\Courses\Services\ResumeService::class)->resume(Auth::user(), $this->course))) {
            $firstCurriculum = $allLessons->firstWhere('id', $resume['lesson']->id) ?? $firstCurriculum;
        }
        if ($firstCurriculum) {
            $this->activeCurriculum = $this->activeFrom($firstCurriculum);
            $this->setActiveCurriculmPath();
        }

        $this->studentRating = $this->course->ratings->where('student_id', Auth::id())->first();

        if ($this->role == 'student') {
            $this->backRoute = $this->redirect == 'courses-list' ? route('courses.course-list') : route('courses.course-detail', ['slug' => $this->course->slug]);
        }

        if ($this->role == 'admin') {
            $this->backRoute =  route('courses.admin.courses');
        }

        if ($this->course->instructor_id == Auth::id()) {
            $this->backRoute =  route('courses.tutor.courses');
        }

        // Build curriculum order array for navigation
        $allCurriculums = collect();
        foreach ($this->course->sections as $section) {
            $allCurriculums = $allCurriculums->concat($section->curriculums);
        }

        $sortedCurriculums = $allCurriculums->values();

        for ($i = 0; $i < $sortedCurriculums->count(); $i++) {
            $currentCurriculum = $sortedCurriculums[$i];
            $nextItem = $sortedCurriculums[$i + 1] ?? null;

            $this->curriculumOrder[$currentCurriculum->id] = $nextItem ? $nextItem->id : null;
            $this->nextCurriculumItem[$currentCurriculum->id] = !empty($nextItem) ? [
                'id' => $nextItem?->id,
                'title' => $nextItem?->title,
                'description' => $nextItem?->description,
                'type' => $nextItem?->type,
            ] : null;
        }
    }

    #[Computed(persist: true)]
    public function course()
    {
        return (new CourseService())->getCourse(
            slug: $this->slug,
            relations: [
                'category',
                'instructor',
                'instructor.languages',
                'instructor.profile:id,user_id,first_name,last_name,image,slug,tagline,gender,native_language,description,verified_at',
                'instructor.socialProfiles',
                'instructor.address',
                'subCategory',
                'language',
                'thumbnail',
                'promotionalVideo',
                'pricing',
                'noticeboards',
                'liveStreams' => function ($q) {
                    $q->where('status', \Modules\Courses\Models\CourseLiveStream::STATUS_SCHEDULED)
                      ->where('date_time', '>', now())
                      ->orderBy('date_time', 'asc');
                },
                'sections' => function ($query) {
                    $query->withWhereHas('curriculums', function ($subQuery) {
                        $subQuery->withContent();
                        $subQuery->with(['watchtime', 'liveStream']);
                        $subQuery->orderBy('sort_order', 'asc');
                    });
                },
                'ratings.student.profile',
                'ratings.student.address',
            ],
            withSum: [
                'courseWatchtime' => 'duration'  // Add this line to get sum of duration
            ],
            withAvg: [
                'ratings' => 'rating',
            ],
            withCount: [
                'ratings',
                'sections',
                'curriculums',
                'instructorReviews',
                'faqs',
                'enrollments',
                'instructor as active_students_count' => function ($query) {  // Count total enrollments
                    $query->withCount('courses')
                        ->join(config('courses.db_prefix') . 'courses', 'users.id', '=', config('courses.db_prefix') . 'courses.instructor_id')
                        ->join(config('courses.db_prefix') . 'enrollments', config('courses.db_prefix') . 'courses.id', '=', config('courses.db_prefix') . 'enrollments.course_id');
                },
            ],
            status: null
        );
    }

    #[Computed]
    public function totalArticles()
    {
        $count = 0;
        foreach ($this->course->sections as $section) {
            $count += count($section->curriculums->where('type', 'article'));
        }
        return $count;
    }

    #[Computed]
    public function totalVideos()
    {
        $count = 0;
        foreach ($this->course->sections as $section) {
            $count += count($section->curriculums->where('type', 'video'));
        }
        return $count;
    }

    public function loadCourseData() {}

    public function render()
    {
        $totalVideos = $this->totalVideos;
        $this->instructorCoursesCount       = (new CourseService())->getInstructorCoursesCount($this->course->instructor_id);

        // The date of an in-person course that this student bought (staff see no personal date).
        $myDate = $this->course->isOnsite() && $this->role == 'student'
            ? \Modules\Courses\Models\Enrollment::where('student_id', Auth::id())->where('course_id', $this->course->id)->with('session')->first()?->session
            : null;

        return view('courses::livewire.student.course-taking.course-taking', ['course' => $this->course, 'totalArticles' => $this->totalArticles, 'totalVideos' => $totalVideos, 'myDate' => $myDate])->extends('courses::layouts.app', ['title' => html_entity_decode((string) $this->course->title)]);
    }

    public function loadData()
    {
        $this->isLoading = false;
    }

    /**
     * The browser only tells us WHICH lesson to open; the lesson itself is always loaded
     * from this course so a tampered payload can't expose files of other courses.
     */
    public function setActiveCurriculum($curriculum)
    {
        $id = is_array($curriculum) ? ($curriculum['id'] ?? 0) : $curriculum;
        $this->openCurriculum((int) $id);
    }

    public function nextCurriculum($id)
    {
        $this->openCurriculum((int) $id);
    }

    private function openCurriculum(int $id): void
    {
        $curriculum = $this->course->sections
            ->flatMap(fn ($section) => $section->curriculums)
            ->firstWhere('id', $id);

        if (!$curriculum) {
            return;
        }

        $this->activeCurriculum = $this->activeFrom($curriculum);
        $this->setActiveCurriculmPath();
    }

    public function markAsCompleted()
    {
        $response = isDemoSite();
        if ($response) {
            $this->dispatch('showAlertMessage', type: 'error', title: __('general.demosite_res_title'), message: __('general.demosite_res_txt'));
            return;
        }
        $curriculumId = (int)$this->activeCurriculum['id'] ?? 0;
        $sectionId = (int) $this->activeCurriculum['section_id'] ?? 0;
        $totalDuration = (int) $this->activeCurriculum['content_length'] ?? 0;
        $watchtime = (new CurriculumService())->getWatchtime($curriculumId, $sectionId);
        if ($watchtime) {
            (new CurriculumService())->updateWatchtime((int) $curriculumId, (int) $sectionId, (int) $totalDuration);
        } else {
            (new CurriculumService())->addWatchtime((int) $this->course?->id, (int) $curriculumId, (int) $sectionId, (int) $totalDuration);
        }
        app(\Modules\Courses\Services\LiveRecordingService::class)->creditLiveLesson(Auth::user(), $curriculumId);   // terminar la grabación cumple la sesión en vivo

        $courseDuration = (new CourseService())->getCourse(
            courseId: $this->course->id,
            withSum: [
                'courseWatchtime' => 'duration'
            ]
        );

        if (!empty($courseDuration->course_watchtime_sum_duration) && !empty($this->course->content_length)) {
            $this->progress = floor(($courseDuration->course_watchtime_sum_duration / $this->course->content_length) * 100);
        }

        $this->activeCurriculum['watchtime']['duration'] = $totalDuration;

        $this->syncCertificate();
        $this->dispatch('updated-progress', progress: $this->progress);
    }

    #[Renderless]
    public function updateWatchtime($isCompleted = false)
    {

        $response = isDemoSite();
        if ($response) {
            return;
        }

        $curriculumId = (int)$this->activeCurriculum['id'] ?? 0;
        $sectionId = (int) $this->activeCurriculum['section_id'] ?? 0;
        $totalDuration = (int) $this->activeCurriculum['content_length'] ?? 0;
        $isAssigned = false;
        $watchtime = (new CurriculumService())->getWatchtime($curriculumId, $sectionId);
        if ($watchtime) {
            $duration = $watchtime->duration;
            if ($isCompleted) {
                (new CurriculumService())->updateWatchtime((int) $curriculumId, (int) $sectionId, (int) $totalDuration);
            } else {
                if ($duration < $totalDuration) {
                    $updateDuration = $duration + 60 <= $totalDuration ? $duration + 60 : $totalDuration;
                    (new CurriculumService())->updateWatchtime((int) $curriculumId, (int) $sectionId, (int) $updateDuration);
                }
            }
        } else {

            if ($isCompleted) {
                $updateDuration = $totalDuration;
            } else {
                $updateDuration = $totalDuration > 60 ? 60 : $totalDuration;
            }

            (new CurriculumService())->addWatchtime((int) $this->course?->id, (int) $curriculumId, (int) $sectionId, (int) $updateDuration);
        }
        if ($isCompleted) {
            app(\Modules\Courses\Services\LiveRecordingService::class)->creditLiveLesson(Auth::user(), $curriculumId);   // terminar la grabación cumple la sesión en vivo
        }
        // Refresh course data to get updated watchtime values
        $courseDuration = (new CourseService())->getCourse(
            courseId: $this->course->id,
            withSum: [
                'courseWatchtime' => 'duration'
            ]
        );

        if (!empty($courseDuration->course_watchtime_sum_duration) && !empty($this->course->content_length)) {
            $this->progress = floor(($courseDuration->course_watchtime_sum_duration / $this->course->content_length) * 100);
        }

        $this->syncCertificate();
        if ($this->progress >= 100) {

            if (isActiveModule('upcertify')  && !empty($this->course?->certificate_id)) {
                $metaData = $this->course->meta_data ?? null;
                if (isActiveModule('Quiz')) {
                    if (!empty($metaData['assign_quiz_certificate']) && $metaData['assign_quiz_certificate'] == 'none') {
                        $this->generateCertificate();
                    }
                } else {
                    $this->generateCertificate();
                }
            }
            $isAssigned = $this->assignQuiz();
            $this->assignAssignment();
        }

        if ($isCompleted) {
            $this->nextCurriculum($this->activeCurriculum['id']);
        }

        $this->dispatch('updated-progress', progress: $this->progress, resultAssigned: $isAssigned);
    }

    public function assignQuiz()
    {
        $studentId = auth()->user()->id;
        $student = User::with('profile')->find($studentId);
        $isAssigned = false;
        if (isActiveModule('Quiz')) {
            $quizzes = $this->course?->quizzes;
            if (!empty($quizzes)) {

                foreach ($quizzes as $quiz) {
                    $isAlreadyAssigned = (new \Modules\Quiz\Services\QuizService())->getAssignedQuiz($quiz->id, auth()->user()->id);
                    if (!$isAlreadyAssigned && $quiz->status == 'published') {
                        $isAssigned = true;
                        $quizDetail = (new \Modules\Quiz\Services\QuizService())->assignQuiz($quiz->id, [auth()->user()->id]);
                        $quizData   = Quiz::with('questions', 'tutor.profile')->whereStatus(Quiz::PUBLISHED)->find($quizDetail->quiz_id);

                        $emailData = [
                            'quizTitle'       => $quiz->title,
                            'studentName'     => $student?->profile?->full_name,
                            'tutorName'       => $quizData?->tutor?->profile?->full_name,
                            'quizUrl'         => route('quiz.student.quiz-details', ['attemptId' => $quizDetail?->id])
                        ];

                        $notifyData = [
                            'quizTitle'         => $quiz->title,
                            'studentName'       => $student?->profile?->full_name,
                            'tutorName'         => $quizData?->tutor?->profile?->full_name,
                            'assignedQuizUrl'   => route('quiz.student.quizzes')
                        ];

                        dispatch(new SendNotificationJob('assignedQuiz', $student, $emailData));
                        dispatch(new SendDbNotificationJob('assignedquiz', $student, $notifyData));
                    }
                }
            }
        }
        return $isAssigned;
    }

    public function assignAssignment()
    {
        $studentId = auth()->user()->id;
        $student = User::with('profile')->find($studentId);
        $isAssigned = false;
        if (isActiveModule('assignments')) {
            $assignments = $this->course?->assignments;
            if (!empty($assignments)) {

                foreach ($assignments as $assignment) {
                    $isAlreadyAssigned = (new \Modules\Assignments\Services\AssignemntsService())->getAssignedAssignment($assignment->id, auth()->user()->id);
                   
                    if (!$isAlreadyAssigned && $assignment->status == 'published') {
                        $isAssigned = true;
                        $assignmentDetail = (new \Modules\Assignments\Services\AssignemntsService())->assignAssignment($assignment->id, [auth()->user()->id]);
                        $assignmentData   = Assignment::with('tutor.profile')->whereStatus(Assignment::STATUS_PUBLISHED)->find($assignmentDetail->assignment_id);
                       
                        $emailData = [
                            'assignmentTitle'       => $assignment->title,
                            'studentName'           => $student?->profile?->full_name,
                            'tutorName'             => $assignmentData?->instructor?->profile?->full_name,
                            'assignedAssignmentUrl' => route('assignments.student.attempt-assignment', ['id' => $assignmentDetail?->id])
                        ];
       
                        $notifyData = [
                            'assignmentTitle'       => $assignment->title,
                            'studentName'           => $student?->profile?->full_name,
                            'tutorName'             => $assignmentData?->instructor?->profile?->full_name,
                            'assignedAssignmentUrl' => route('assignments.student.attempt-assignment', ['id' => $assignmentDetail?->id])
                        ];
                    
                        dispatch(new SendNotificationJob('assignedAssignment', $student, $emailData));
                        dispatch(new SendDbNotificationJob('assignedassignment', $student, $notifyData));
                    }
                }
            }
        }
        return $isAssigned;
    }

    /** Al completar el curso se emite el certificado verificable (una sola vez) y se avisa a la pantalla. */
    protected function syncCertificate(): void
    {
        if ($this->role !== 'student' || (float) $this->progress < 99 || !CourseCertificateService::enabled()) {
            return;
        }
        $cert = app(CourseCertificateService::class)->issueIfComplete(Auth::user(), $this->course);
        if ($cert && $this->certificateUrl !== $cert->url()) {
            $this->certificateUrl = $cert->url();
            $this->dispatch('certificate-ready', url: $this->certificateUrl);
        }
    }

    public function generateCertificate()
    {

        $wildcard_data = [
            'tutor_name'         => $this->course?->instructor?->profile?->full_name ?? '',
            'student_name'       => auth()->user()->profile?->full_name ?? '',
            'gender'             => !empty(auth()->user()->profile?->gender) ? ucfirst(auth()->user()->profile?->gender) : '',
            'tutor_tagline'      => $this->course?->instructor?->profile?->tagline ?? '',
            'issued_by'          => $this->course?->instructor?->profile?->full_name ?? '',
            'platform_name'      => setting('_general.site_name'),
            'platform_email'     => setting('_general.site_email'),
            'course_title'       => $this->course?->title ?? '',
            'course_subtitle'    => $this->course?->subtitle ?? '',
            'course_description' => $this->course?->description ?? '',
            'course_category'    => $this->course?->category?->name ?? '',
            'course_subcategory' => $this->course?->subCategory?->name ?? '',
            'course_type'        => $this->course?->type ?? '',
            'course_level'       => $this->course?->level ?? '',
            'course_language'    => $this->course?->language?->name ?? '',
            'free_course'        => $this->course?->is_free ? 'Yes' : 'No',
            'course_price'       => $this->course?->pricing?->price ? formatAmount($this->course?->pricing?->price) : '',
            'course_discount'    => $this->course?->pricing?->discount ? formatAmount($this->course?->pricing?->discount) : '',
            'issue_date'         => now()->format(setting('_general.date_format')),
            'student_email'      => auth()->user()->email ?? '',
            'tutor_email'        => $this->course?->instructor?->email ?? '',
        ];

        generate_certificate(template_id: $this->course?->certificate_id, generated_for_type: 'App\Models\User', generated_for_id: auth()->user()->id, wildcard_data: $wildcard_data);
    }

    public function submitRating()
    {
        $response = isDemoSite();
        if ($response) {
            $this->dispatch('showAlertMessage', type: 'error', title: __('general.demosite_res_title'), message: __('general.demosite_res_txt'));
            return;
        }
        $data = $this->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'description' => 'required|string|max:1000',
        ]);

        $isAdded = (new CourseService())->addCourseRating($this->course->id, $data);
        if ($isAdded) {
            $this->dispatch('showAlertMessage', type: 'success', title: __('courses::courses.rating_added'), message: __('courses::courses.rating_added_successfully'));
        } else {
            $this->dispatch('showAlertMessage', type: 'error', title: __('courses::courses.error_title'), message: __('courses::courses.rating_add_failed'));
        }

        $courseRatings = (new CourseService())->getCourse(
            courseId: $this->course->id,
            relations: ['ratings']
        );

        $this->studentRating = $courseRatings->ratings->where('student_id', auth()->id())->first();
    }

    public function submitAssignment()
    {
        $response = isDemoSite();
        if ($response) {
            $this->dispatch('showAlertMessage', type: 'error', title: __('general.demosite_res_title'), message: __('general.demosite_res_txt'));
            return;
        }

        $this->validate([
            'assignment_file' => 'nullable|file|max:10240', // 10MB max
            'assignment_comment' => 'nullable|string|max:1000',
        ]);

        $filePath = null;
        if ($this->assignment_file) {
            $filePath = $this->assignment_file->store('assignments', getPrivateDisk());
        }

        AssignmentSubmission::updateOrCreate(
            [
                'curriculum_id' => $this->activeCurriculum['id'],
                'user_id' => auth()->id(),
            ],
            [
                'file_path' => $filePath,
                'student_comment' => $this->assignment_comment,
                'status' => 'submitted',
            ]
        );

        $this->assignment_file = null;
        $this->assignment_comment = null;

        $this->dispatch('showAlertMessage', type: 'success', title: 'Éxito', message: 'Tarea enviada correctamente');
    }

    /**
     * The lesson as the browser may see it. `activeCurriculum` is a public Livewire property (it travels in the page and in
     * every request), so quiz answers never go in it, and a live lesson only carries what the current phase allows.
     */
    private function activeFrom(CurriculumModel $curriculum): array
    {
        $data = $curriculum->toArray();
        unset($data['live_stream']);   // the raw session (with the meeting link) must never travel; see livePayload()
        if ($curriculum->type === 'assignment') {
            $quizzes = new LessonQuizService();
            $data['article_content'] = json_encode($quizzes->forStudent($quizzes->load($curriculum->article_content)), JSON_UNESCAPED_UNICODE);
        }
        if ($curriculum->type === 'live') {
            $data['live'] = $this->livePayload($curriculum);
        }

        return $data;
    }

    private function livePayload(CurriculumModel $curriculum): ?array
    {
        $stream = $curriculum->liveStream;
        if (!$stream) {
            return null;
        }
        $phase = LiveSessionService::phase($stream);

        return [
            'id' => $stream->id,
            'title' => $stream->title,
            'description' => $stream->description,
            'starts_at' => $stream->date_time->toIso8601String(),
            'ends_at' => $stream->date_time->copy()->addMinutes($stream->duration_minutes ?: 60)->toIso8601String(),
            'join_from' => $stream->date_time->copy()->subMinutes(LiveSessionService::JOIN_WINDOW_MINUTES)->toIso8601String(),
            'duration_minutes' => $stream->duration_minutes,
            'phase' => $phase,
            'provider' => $stream->provider,
            // The meeting link is only handed out when the join window is open.
            'join_url' => in_array($phase, ['soon', 'live'], true) ? $stream->meeting_link : null,
            'recording_url' => $phase === 'ended' ? $stream->recording_url : null,
            'ics_url' => route('courses.live.ics', ['stream' => $stream->id]),
        ];
    }

    /** Called by the player when the countdown reaches the join window (or the end) to fetch the up-to-date state. */
    public function refreshLive(): void
    {
        $lesson = $this->currentLesson();
        if ($lesson && $lesson->type === 'live') {
            $lesson->unsetRelation('liveStream');
            $this->activeCurriculum['live'] = $this->livePayload($lesson);
        }
    }

    /** The student pressed "Unirme": the lesson counts as attended. */
    public function markAttended(): void
    {
        $lesson = $this->currentLesson();
        if ($lesson && $lesson->type === 'live') {
            $this->markAsCompleted();
        }
    }

    private function currentLesson(): ?CurriculumModel
    {
        return $this->course->sections->flatMap(fn ($section) => $section->curriculums)->firstWhere('id', $this->activeCurriculum['id'] ?? 0);
    }

    /**
     * Grades the answers on the server (the browser only ever receives the questions) and, with the right quiz settings,
     * returns the review. Returns the result so the player can show it without another round trip.
     *
     * @param array<int|string, int|array<int>> $answers keyed by question id
     */
    public function submitQuiz(array $answers = []): array
    {
        if (isDemoSite()) {
            $this->dispatch('showAlertMessage', type: 'error', title: __('general.demosite_res_title'), message: __('general.demosite_res_txt'));
            return ['error' => 'demo'];
        }

        $curriculum = $this->currentLesson();
        if (!$curriculum || $curriculum->type !== 'assignment' || $this->role !== 'student') {
            return ['error' => 'invalid'];
        }

        $quizzes = new LessonQuizService();
        $quiz = $quizzes->load($curriculum->article_content);
        $submission = AssignmentSubmission::firstOrNew(['curriculum_id' => $curriculum->id, 'user_id' => auth()->id()]);
        $used = (int) $submission->attempts;
        if ($quiz['max_attempts'] > 0 && $used >= $quiz['max_attempts']) {
            return ['error' => 'attempts', 'attempts' => $used, 'max_attempts' => $quiz['max_attempts']];
        }

        $graded = $quizzes->grade($quiz, $answers);
        $submission->fill([
            'student_comment' => json_encode($answers),
            'score' => $graded['score'],
            'status' => 'graded',
            'attempts' => $used + 1,
        ]);
        if ($graded['passed'] && !$submission->passed_at) {
            $submission->passed_at = now();
        }
        $submission->save();

        if ($graded['passed']) {
            $this->markAsCompleted();   // passing the test completes the lesson (and moves the course progress)
        }

        $results = [];
        foreach ($graded['results'] as $id => $r) {
            $results[$id] = $quiz['show_answers']
                ? ['correct' => $r['correct'], 'correct_answer' => $r['correct_answer'], 'explanation' => $r['explanation']]
                : ['correct' => $r['correct']];
        }

        return [
            'score' => $graded['score'], 'passed' => $graded['passed'], 'correct' => $graded['correct'], 'total' => $graded['total'],
            'attempts' => $used + 1, 'max_attempts' => $quiz['max_attempts'], 'pass_score' => $quiz['pass_score'], 'results' => $results,
        ];
    }

    private function setActiveCurriculmPath()
    {
        if(!empty($this->activeCurriculum['media_path'])) {
            if (isset($this->activeCurriculum['type']) && $this->activeCurriculum['type'] === 'video') {
                // Short-lived signed/presigned URL; only reached by users who passed the enrollment check in mount().
                $this->activeCurriculum['media_path'] = lessonMediaUrl($this->activeCurriculum['media_path']);
            }
        } else{
            $this->activeCurriculum['media_path'] = null;
        }
    }
}
