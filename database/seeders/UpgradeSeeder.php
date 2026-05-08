<?php

namespace Database\Seeders;

use Database\Seeders\VersionSeeders\V110Seeder;
use Database\Seeders\VersionSeeders\V111Seeder;
use Database\Seeders\VersionSeeders\V11Seeder;
use Database\Seeders\VersionSeeders\V12Seeder;
use Database\Seeders\VersionSeeders\V13Seeder;
use Database\Seeders\VersionSeeders\V14Seeder;
use Database\Seeders\VersionSeeders\V15Seeder;
use Database\Seeders\VersionSeeders\V16Seeder;
use Database\Seeders\VersionSeeders\V17Seeder;
use Database\Seeders\VersionSeeders\V18Seeder;
use Database\Seeders\VersionSeeders\V19Seeder;
use Database\Seeders\VersionSeeders\V201Seeder;
use Database\Seeders\VersionSeeders\V202Seeder;
use Database\Seeders\VersionSeeders\V203Seeder;
use Database\Seeders\VersionSeeders\V20Seeder;
use Database\Seeders\VersionSeeders\V210Seeder;
use Database\Seeders\VersionSeeders\V211Seeder;
use Database\Seeders\VersionSeeders\V212Seeder;
use Database\Seeders\VersionSeeders\V213Seeder;
use Database\Seeders\VersionSeeders\V214Seeder;
use Database\Seeders\VersionSeeders\V215Seeder;
use Database\Seeders\VersionSeeders\V216Seeder;
use Database\Seeders\VersionSeeders\V217Seeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Facades\Module;

class UpgradeSeeder extends Seeder
{
    /**
     * Run the upgrade seeder.
     *
     * @param string $from_verison
     * @param string $to_version
     * @return void
     */
    public function run($from_version, $to_version)
    {
        $seeders = [

            '1.1'   => V11Seeder::class,
            '1.2'   => V12Seeder::class,
            '1.3'   => V13Seeder::class,
            '1.4'   => V14Seeder::class,
            '1.5'   => V15Seeder::class,
            '1.6'   => V16Seeder::class,
            '1.7'   => V17Seeder::class,
            '1.8'   => V18Seeder::class,
            '1.9'   => V19Seeder::class,
            '1.10'  => V110Seeder::class,
            '1.11'  => V111Seeder::class,
            '2.0'   => V20Seeder::class,
            '2.0.1' => V201Seeder::class,
            '2.0.2' => V202Seeder::class,
            '2.0.3' => V203Seeder::class,
            '2.1.0' => V210Seeder::class,
            '2.1.1' => V211Seeder::class,
            '2.1.2' => V212Seeder::class,
            '2.1.3' => V213Seeder::class,
            '2.1.4' => V214Seeder::class,
            '2.1.5' => V215Seeder::class,
            '2.1.6' => V216Seeder::class,
            '2.1.7' => V217Seeder::class,
        ];

        $seedersToRun = [];

        foreach ($seeders as $version => $seederClass) {
            if (version_compare($version, $from_version, '>') && version_compare($version, $to_version, '<=')) {
                $seedersToRun[] = $seederClass;
            }
        }

        foreach ($seedersToRun as $seederClass) {
            $this->call($seederClass);
        }

        Artisan::call('storage:unlink');
        Artisan::call('storage:link');

        if (!empty(Module::allEnabled())) {
            foreach (Module::allEnabled() as $enabledModule) {
                Artisan::call('module:publish', ['module' => $enabledModule->getName()]);
            }
        }
    }
}
