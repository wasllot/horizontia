<?php

namespace App\Livewire\Pages\Admin\Coupons;

use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Courses\Models\Course;

/** Admin > Cupones: códigos de descuento para el checkout. */
class Coupons extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;

    public bool $formOpen = false;
    public ?int $editingId = null;
    public string $code = '';
    public string $description = '';
    public string $type = Coupon::PERCENT;
    public $value = '';
    public $minAmount = '';
    public $maxUses = '';
    public $perUserLimit = 1;
    public string $startsAt = '';
    public string $endsAt = '';
    public bool $firstPurchaseOnly = false;
    public bool $active = true;
    public string $scope = 'all';
    /** @var array<int, int|string> */
    public array $courseIds = [];

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('showAlertMessage', type: $type, title: $type === 'success' ? __('general.success_title') : 'Atención', message: $message);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->formOpen = true;
    }

    public function suggestCode(): void
    {
        do {
            $this->code = 'HZ' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (Coupon::where('code', $this->code)->exists());
    }

    public function edit(int $id): void
    {
        $c = Coupon::findOrFail($id);
        $this->resetForm();
        $this->editingId = $c->id;
        $this->code = $c->code;
        $this->description = (string) $c->description;
        $this->type = $c->type;
        $this->value = (string) $c->value;
        $this->minAmount = $c->min_amount !== null ? (string) $c->min_amount : '';
        $this->maxUses = $c->max_uses !== null ? (string) $c->max_uses : '';
        $this->perUserLimit = $c->per_user_limit;
        $this->startsAt = $c->starts_at?->format('Y-m-d') ?? '';
        $this->endsAt = $c->ends_at?->format('Y-m-d') ?? '';
        $this->firstPurchaseOnly = $c->first_purchase_only;
        $this->active = $c->active;
        $this->scope = empty($c->course_ids) ? 'all' : 'courses';
        $this->courseIds = array_map('strval', $c->course_ids ?? []);
        $this->formOpen = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset('formOpen', 'editingId', 'code', 'description', 'value', 'minAmount', 'maxUses', 'startsAt', 'endsAt', 'firstPurchaseOnly', 'courseIds');
        $this->type = Coupon::PERCENT;
        $this->perUserLimit = 1;
        $this->active = true;
        $this->scope = 'all';
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->code = CouponService::normalize($this->code);
        $this->validate([
            'code' => ['required', 'string', 'min:3', 'max:40', Rule::unique('coupons', 'code')->ignore($this->editingId)],
            'description' => 'nullable|string|max:160',
            'type' => 'required|in:percent,fixed',
            'value' => ['required', 'numeric', 'gt:0', $this->type === 'percent' ? 'max:100' : 'max:1000000'],
            'minAmount' => 'nullable|numeric|min:0',
            'maxUses' => 'nullable|integer|min:1',
            'perUserLimit' => 'required|integer|min:0|max:1000',
            'startsAt' => 'nullable|date',
            'endsAt' => 'nullable|date|after_or_equal:startsAt',
        ], [
            'code.required' => 'Escribe el código o genera uno.', 'code.unique' => 'Ya existe un cupón con ese código.', 'code.min' => 'El código necesita al menos 3 caracteres.',
            'value.required' => 'Indica el valor del descuento.', 'value.gt' => 'El descuento debe ser mayor a 0.', 'value.max' => 'El porcentaje no puede pasar de 100.',
            'endsAt.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ], ['value' => 'valor', 'minAmount' => 'compra mínima', 'maxUses' => 'límite de usos', 'perUserLimit' => 'usos por persona']);

        $ids = $this->scope === 'courses' ? collect($this->courseIds)->map(fn ($i) => (int) $i)->unique()->values() : collect();
        if ($this->scope === 'courses' && $ids->isEmpty()) {
            $this->addError('courseIds', 'Elige al menos un curso o deja «Todos los cursos».');

            return;
        }

        $data = [
            'code' => $this->code, 'description' => trim(strip_tags($this->description)) ?: null, 'type' => $this->type, 'value' => round((float) $this->value, 2),
            'min_amount' => $this->minAmount === '' || $this->minAmount === null ? null : round((float) $this->minAmount, 2),
            'max_uses' => $this->maxUses === '' || $this->maxUses === null ? null : (int) $this->maxUses,
            'per_user_limit' => (int) $this->perUserLimit, 'first_purchase_only' => $this->firstPurchaseOnly, 'active' => $this->active,
            'starts_at' => $this->startsAt ? \Carbon\Carbon::parse($this->startsAt)->startOfDay() : null,
            'ends_at' => $this->endsAt ? \Carbon\Carbon::parse($this->endsAt)->endOfDay() : null,
            'course_ids' => $ids->isEmpty() ? null : $ids->all(),
        ];
        $this->editingId ? Coupon::findOrFail($this->editingId)->update($data) : Coupon::create($data);

        $this->resetForm();
        $this->toast('Cupón guardado.');
    }

    public function toggle(int $id): void
    {
        $c = Coupon::findOrFail($id);
        $c->update(['active' => !$c->active]);
        $this->toast($c->active ? 'Cupón activado.' : 'Cupón pausado: ya no se puede usar.');
    }

    public function delete(int $id): void
    {
        Coupon::findOrFail($id)->delete();
        $this->toast('Cupón eliminado. Los pedidos que lo usaron conservan su descuento.');
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        return view('livewire.pages.admin.coupons.coupons', [
            'coupons' => Coupon::orderByDesc('id')->get(),
            'courses' => $this->formOpen && $this->scope === 'courses' ? Course::where('status', Course::STATUSES['active'])->orderBy('title')->get(['id', 'title']) : collect(),
        ]);
    }
}
