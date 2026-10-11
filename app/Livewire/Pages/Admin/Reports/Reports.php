<?php

namespace App\Livewire\Pages\Admin\Reports;

use App\Services\BusinessReportService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Courses\Models\Course;

/** Admin > Reportes: ventas, aprendizaje (finalización y abandono) y cupones, con exportación a CSV. */
class Reports extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;

    #[Url(as: 'periodo')]
    public string $range = '30d';

    #[Url(as: 'seccion')]
    public string $tab = 'ventas';

    #[Url(as: 'curso')]
    public ?int $courseId = null;

    public function setRange(string $range): void
    {
        $this->range = array_key_exists($range, BusinessReportService::RANGES) ? $range : '30d';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['ventas', 'aprendizaje', 'cupones', 'busquedas', 'whatsapp'], true) ? $tab : 'ventas';
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $report = new BusinessReportService(array_key_exists($this->range, BusinessReportService::RANGES) ? $this->range : '30d');
        $data = ['report' => $report, 'tabs' => ['ventas' => 'Ventas', 'aprendizaje' => 'Aprendizaje', 'cupones' => 'Cupones', 'busquedas' => 'Búsquedas', 'whatsapp' => 'WhatsApp']];

        if ($this->tab === 'aprendizaje') {
            $learning = $report->learning();
            $selected = $this->courseId && $learning->contains('id', $this->courseId) ? $this->courseId : $learning->first()?->id;
            $data += ['learning' => $learning, 'selected' => $selected, 'funnel' => $selected ? $report->lessonFunnel($selected) : collect()];
        } elseif ($this->tab === 'whatsapp') {
            $data += ['wa' => $report->whatsapp(), 'waEnabled' => \App\Services\WhatsAppService::enabled()];
        } elseif ($this->tab === 'busquedas') {
            $data += ['searches' => $report->searches()];
        } elseif ($this->tab === 'cupones') {
            $data += ['coupons' => $report->coupons(), 'summary' => $report->summary()];
        } else {
            $data += ['summary' => $report->summary(), 'series' => $report->series(), 'top' => $report->topCourses(), 'methods' => $report->byMethod()];
        }

        return view('livewire.pages.admin.reports.reports', $data);
    }
}
