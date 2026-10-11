<?php

namespace Modules\Courses\Livewire\Pages\Search;

use Modules\Courses\Models\Category;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Services\CourseSearchService;
use Modules\Courses\Services\CourseService;
use App\Models\Language;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SearchCourses extends Component
{
    use WithPagination;

    public $isLoading = true;
    public $showClearFilters = false;

    public $perPage = 12;
    public $totalCourses;
    public $paidCourses;
    #[Url]
    public $tutor_id;
    public $user;
    #[Url]
    public $searchCategories    = [];
    #[Url]
    public $searchLanguages     = [];

    public $currentVideo = null;
    public $parPageList = [9, 20, 30, 50, 100, 200];

    #[Url]
    public $filters     = [
        'keyword'           => '',
        'category'          => '',
        'levels'            => [],
        'languages'         => [],
        'per_page'          => '',
        'min_price'         => null,
        'max_price'         => null,
        'sort'              => 'desc',
        'avg_rating'        => [],
        'duration'          => [],
        'pricing_type'      => 'all',
    ];

    /** Filter facets with their counts. Not public properties: Livewire would rehydrate the models and lose the *_count attributes. */
    protected ?array $facetCache = null;

    protected function facets(): array
    {
        return $this->facetCache ??= (function () {
            $service = new CourseService();

            return [
                'categories' => $service->getCategories()->filter(fn ($c) => $c->active_courses_count > 0)->values(),
                'levels' => $service->getLevels(),
                'languages' => $service->getLanguages(),
                'durationCounts' => array_replace(array_fill_keys(['0-1', '1-3', '3-6', '6-17', '17+'], 0), $service->getCourseByContentLength()->pluck('course_count', 'duration_range')->toArray()),
            ];
        })();
    }

    public const PER_PAGE = 12;
    public const SORTS = [
        'relevance' => 'Más relevantes',
        'desc' => 'Más recientes',
        'popular' => 'Más populares',
        'rating' => 'Mejor calificados',
        'price_asc' => 'Precio: de menor a mayor',
        'price_desc' => 'Precio: de mayor a menor',
        'asc' => 'Más antiguos',
    ];

    /** Al buscar, lo más relevante va primero; al borrar la búsqueda se vuelve a «más recientes». */
    public function updatedFiltersKeyword($value): void
    {
        $sort = $this->filters['sort'] ?? 'desc';
        if (trim((string) $value) !== '' && $sort === 'desc') {
            $this->filters['sort'] = 'relevance';
        } elseif (trim((string) $value) === '' && $sort === 'relevance') {
            $this->filters['sort'] = 'desc';
        }
    }

    public function useSuggestion(string $suggestion): void
    {
        $this->filters['keyword'] = mb_substr(strip_tags($suggestion), 0, 80);
        $this->updatedFiltersKeyword($this->filters['keyword']);
        $this->resetPage();
    }

    public function mount()
    {
        if (!empty($this->tutor_id)) {
            $this->user = (new CourseService())->getUser($this->tutor_id);
        }
        $this->isLoading = false;
        $this->perPage   = self::PER_PAGE;

        // Unknown sort values coming from the URL fall back to the default instead of erroring.
        if (!array_key_exists($this->filters['sort'] ?? 'desc', self::SORTS)) {
            $this->filters['sort'] = 'desc';
        }
        if (trim((string) ($this->filters['keyword'] ?? '')) !== '' && ($this->filters['sort'] ?? 'desc') === 'desc') {
            $this->filters['sort'] = 'relevance';
        }

        $this->toggleShowClearFilters();
    }

    public function closeTutorDetail()
    {
        $this->tutor_id = null;
        $this->dispatch('closeTutorDetail');
    }

    /** Kept for the old wire:init flow; the catalog is rendered on first paint now. */
    public function loadData()
    {
        $this->isLoading = false;
    }

    public function toggleShowClearFilters()
    {
        $this->showClearFilters = $this->appliedFilters() !== [];
        $this->perPage = self::PER_PAGE;
        $this->resetPage();
    }

    /** Applied filters as removable chips: [['type' => 'category', 'value' => 3, 'label' => 'Marketing'], ...]. */
    public function appliedFilters(): array
    {
        $chips = [];
        if (!empty($this->filters['keyword'])) {
            $chips[] = ['type' => 'keyword', 'value' => '', 'label' => '«' . $this->filters['keyword'] . '»'];
        }
        foreach ((array) $this->searchCategories as $id) {
            if ($name = collect($this->facets()['categories'])->firstWhere('id', (int) $id)?->name) {
                $chips[] = ['type' => 'category', 'value' => $id, 'label' => $name];
            }
        }
        foreach ((array) ($this->filters['levels'] ?? []) as $level) {
            $chips[] = ['type' => 'level', 'value' => $level, 'label' => $level === 'all' ? 'Todos los niveles' : __('courses::courses.' . $level)];
        }
        foreach ((array) $this->searchLanguages as $id) {
            if ($name = collect($this->facets()['languages'])->firstWhere('id', (int) $id)?->name) {
                $chips[] = ['type' => 'language', 'value' => $id, 'label' => $name];
            }
        }
        foreach ((array) ($this->filters['duration'] ?? []) as $range) {
            $chips[] = ['type' => 'duration', 'value' => $range, 'label' => self::durationLabel($range)];
        }
        if (!empty($this->filters['pricing_type']) && $this->filters['pricing_type'] !== 'all') {
            $chips[] = ['type' => 'pricing', 'value' => '', 'label' => $this->filters['pricing_type'] === 'free' ? 'Gratis' : 'De pago'];
        }
        if (!empty($this->filters['avg_rating'])) {
            $chips[] = ['type' => 'rating', 'value' => '', 'label' => '4 estrellas o más'];
        }

        return $chips;
    }

    public static function durationLabel(string $range): string
    {
        return match ($range) {
            '0-1' => 'Hasta 1 hora',
            '1-3' => '1 a 3 horas',
            '3-6' => '3 a 6 horas',
            '6-17' => '6 a 17 horas',
            default => 'Más de 17 horas',
        };
    }

    public function removeFilter(string $type, $value = null)
    {
        match ($type) {
            'keyword' => $this->filters['keyword'] = '',
            'category' => $this->searchCategories = array_values(array_diff((array) $this->searchCategories, [$value, (string) $value, (int) $value])),
            'level' => $this->filters['levels'] = array_values(array_diff((array) $this->filters['levels'], [$value])),
            'language' => $this->searchLanguages = array_values(array_diff((array) $this->searchLanguages, [$value, (string) $value, (int) $value])),
            'duration' => $this->filters['duration'] = array_values(array_diff((array) $this->filters['duration'], [$value])),
            'pricing' => $this->filters['pricing_type'] = 'all',
            'rating' => $this->filters['avg_rating'] = [],
            default => null,
        };
        $this->toggleShowClearFilters();
    }

    public function toggleCategory($id)
    {
        $current = array_map('intval', (array) $this->searchCategories);
        $this->searchCategories = in_array((int) $id, $current, true)
            ? array_values(array_diff($current, [(int) $id]))
            : array_values(array_merge($current, [(int) $id]));
        $this->toggleShowClearFilters();
    }

    public function clearCategories()
    {
        $this->searchCategories = [];
        $this->toggleShowClearFilters();
    }

    public function toggleRating()
    {
        $this->filters['avg_rating'] = empty($this->filters['avg_rating']) ? [4, 5] : [];
        $this->toggleShowClearFilters();
    }

    public function loadMore()
    {
        $this->perPage = min($this->perPage + self::PER_PAGE, 120);
    }

    public function updatedFilters()
    {
        $this->toggleShowClearFilters();
    }

    public function updatedSearchCategories()
    {
        $this->toggleShowClearFilters();
    }

    public function updatedSearchLanguages()
    {
        $this->toggleShowClearFilters();
    }

    public function resetFilters()
    {
        $this->filters = [
            'keyword' => '', 'category' => '', 'levels' => [], 'languages' => [], 'per_page' => '', 'min_price' => null,
            'max_price' => null, 'sort' => 'desc', 'avg_rating' => [], 'duration' => [], 'pricing_type' => 'all',
        ];
        $this->searchLanguages = [];
        $this->searchCategories = [];
        $this->toggleShowClearFilters();
    }

    public function render()
    {
        $courses = (new CourseService())->getCourses(
            instructorId: !empty($this->tutor_id) ? $this->tutor_id : null,
            with: ['category', 'pricing', 'language', 'instructor.profile', 'thumbnail'],
            withCount: ['ratings', 'curriculums'],
            withAvg: ['ratings' => 'rating'],
            filters: array_merge($this->filters, ['categories' => $this->searchCategories, 'languages' => $this->searchLanguages, 'status' => 'active', 'exclude_delivery' => ['in_person']]),   // in-person courses live in /eventos
            perPage: $this->perPage
        );

        // "Continue where you left off": the student's own active enrollments, only on the unfiltered catalog.
        $continue = collect();
        if (Auth::check() && !$this->showClearFilters) {
            $continue = Course::query()->with('thumbnail')
                ->where('status', Course::STATUSES['active'])
                ->whereHas('enrollments', fn ($q) => $q->where('student_id', Auth::id())->where('status', Enrollment::STATUSES['active']))
                ->latest('id')->limit(3)->get();
        }

        // Búsqueda por texto: se anota (para saber qué buscan y no encuentran) y, sin resultados, se sugiere cómo se escribe.
        $keyword = trim((string) ($this->filters['keyword'] ?? ''));
        $suggestion = null;
        if ($keyword !== '') {
            $search = app(CourseSearchService::class);
            $search->log($keyword, $courses->total(), Auth::id());
            $suggestion = $courses->total() === 0 ? $search->suggest($keyword) : null;
        }

        return view('courses::livewire.search.search-courses', [
            'courses'  => $courses,
            'continue' => $continue,
            'applied'  => $this->appliedFilters(),
            'sorts'    => $keyword !== '' ? self::SORTS : \Illuminate\Support\Arr::except(self::SORTS, 'relevance'),
            'suggestion' => $suggestion,
        ] + $this->facets())->extends('layouts.frontend-app', [
            'pageTitle'       => 'Catálogo de cursos',
            'pageDescription' => 'Explora los cursos en línea de la academia: filtra por categoría, nivel, duración o precio y empieza a aprender hoy.',
        ]);
    }

    public function isLiked($course)
    {
        return Auth::check() && $course->likes()->where('user_id', Auth::id())->exists();
    }

    public function likeCourse($courseId)
    {
        if (isDemoSite()) {
            $this->dispatch('showAlertMessage', type: 'error', title: __('general.demosite_res_title'), message: __('general.demosite_res_txt'));
            return;
        }
        if (!Auth::check()) {
            $this->dispatch('showAlertMessage', type: 'error', message: __('courses::courses.login_to_like_a_course'));
            return;
        }
        $course = (new CourseService())->getCourse($courseId);
        $this->isLiked($course)
            ? $course->likes()->where('user_id', Auth::id())->delete()
            : $course->likes()->create(['user_id' => Auth::id()]);
    }
}
