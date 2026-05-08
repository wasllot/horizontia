<?php

namespace App\Listeners;

use Database\Seeders\SubscriptionsModuleManagementSeeder;
use Illuminate\Support\Facades\Log;

class ModuleManagementListener
{
    /**
     * Handle the event.
     */
    public function handle($event, $data): void
    {
        $module = $data[0] ?? null;
        if (empty($module)) {
            return;
        }

        //Subscrpitions module enabled   
        if ($event == 'modules.subscriptions.enabled' && $module->getName() === 'Subscriptions') {
            Log::info('module enabled');
            $seeder = new SubscriptionsModuleManagementSeeder();
            $seeder->run('enabled');
        } 

        //Subscrpitions module disabled
        elseif ($event == 'modules.subscriptions.disabled' && $module->getName() === 'Subscriptions') {
            Log::info('module disabled');
            $seeder = new SubscriptionsModuleManagementSeeder();
            $seeder->run('disabled');
        }

        //Subscrpitions module deleted
        elseif ($event == 'modules.subscriptions.deleted' && $module->getName() === 'Subscriptions') {
            Log::info('module deleted');
            $seeder = new SubscriptionsModuleManagementSeeder();
            $seeder->run('deleted');
        }
    }
}
