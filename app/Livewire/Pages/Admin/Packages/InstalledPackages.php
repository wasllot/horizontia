<?php

namespace App\Livewire\Pages\Admin\Packages;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Nwidart\Modules\Facades\Module;

class InstalledPackages extends Component
{
    public $filters = ['sortby' => 'asc', 'per_page' => 10, 'name' => ''];
    public $coreModules = ['LaraPayease', 'MeetFusion'];

    public function mount()
    {
        $this->filters['per_page'] = setting('_general.per_page_record') ?? 10;
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $packages = $this->filterPackages();
        $addonsLists = $this->getAddonsLists();
        foreach($packages as $package => $detail){
           $packages[$package]->image   = $addonsLists[$package]['image'] ?? '';
           $packages[$package]->type    = $addonsLists[$package]['type'] ?? '';
        }
        return view('livewire.pages.admin.packages.installed-packages', compact('packages'));
        
    }

    public function filterPackages()
    {
        $packages = Module::all();
        $packages = collect($packages)->reject(function ($package) {
            return $package->get('version') == 'lite';
        })->toArray();
        if ($this->filters['sortby'] == 'asc') {
            $packages = collect($packages)->sortBy(function ($package) {
                return $package->getName();
            })->toArray();
        } else {
            $packages = collect($packages)->sortByDesc(function ($package) {
                return $package->getName();
            })->toArray();
        }
        if($this->filters['name'] != ''){
            $packages = collect($packages)->filter(function ($package) {
                return strpos(strtolower($package->getName()), strtolower($this->filters['name'])) !== false;
            })->toArray();
        }
        return $packages;
    }
    
    private function getAddonsLists()
    {
       return json_decode(file_get_contents(base_path('addons.json')), true);
    }

    #[On('update-status')]
    public function updateStatus($params)
    {
        $response = isDemoSite();
        if( $response ){
            $this->dispatch('showAlertMessage', type: 'error', title:  __('general.demosite_res_title') , message: __('general.demosite_res_txt'));
            return;
        }

        $module = Module::find($params['id']);
        if(empty($module)){
            $this->dispatch('showAlertMessage', message: __('admin/general.addon_not_found'), type: 'error');
            return;
        }
        if(in_array($module->getName(), $this->coreModules) && $params['type'] == 'active'){
            $this->dispatch('showAlertMessage', message: __('admin/general.core_module_not_deactivate'), type: 'error');
            return;
        }
        if($params['type'] == 'active'){
            Module::disable($module->getName());
        }else{
            Module::enable($module->getName());
            $this->runPostInstallCommands($module->getName());
        }
        Artisan::call('module:clear-compiled');
        Artisan::call('optimize:clear');
        $this->dispatch('showAlertMessage', message: __('admin/general.addon_updated'), type: 'success');
        return $this->redirect(route('admin.packages.installed'));
    }

    #[On('delete-addon')]
    public function deleteAddon($params)
    {
        $response = isDemoSite();
        if( $response ){
            $this->dispatch('showAlertMessage', type: 'error', title:  __('general.demosite_res_title') , message: __('general.demosite_res_txt'));
            return;
        }

        $module = Module::find($params['id']);
        if(empty($module)){
            $this->dispatch('showAlertMessage', message: __('admin/general.addon_not_found'), type: 'error');
            return;
        }
        if(in_array($module->getName(), $this->coreModules)){
            $this->dispatch('showAlertMessage', message: __('admin/general.core_module_not_delete'), type: 'error');
            return;
        }
        Module::enable($module->getName());
        Artisan::call('module:migrate-rollback', ['module' => $module->getName(), '--force' => true]);
        Cache::forget('first_install_'.$module->getName());
        Module::delete($module->getName());
        if (File::exists(public_path('modules/'.$params['id']))){
            File::deleteDirectory(public_path('modules/'.$params['id']));
        }
        Artisan::call('module:clear-compiled');
        $this->dispatch('showAlertMessage', message: __('admin/general.addon_deleted'), type: 'success');
        Artisan::call('optimize:clear');
        sleep(5);
        return $this->redirect(route('admin.packages.installed'));
    }

    private function runPostInstallCommands($module)
    {
        $alreadyRun = Cache::get('first_install_'.$module, false);
        if (!$alreadyRun) {
            Module::boot();
            Module::register();
            Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
            Artisan::call('module:publish', ['module' => $module]);
            Artisan::call('module:seeder', ['module' => $module, '--force' => true]);
            Artisan::call('module:clear-compiled');
            Cache::set('first_install_'.$module, true);
        }
    }
}
