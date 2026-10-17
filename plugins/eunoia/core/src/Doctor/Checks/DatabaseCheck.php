<?php

namespace Eunoia\Core\Doctor\Checks;

use Eunoia\Core\Doctor\CheckResult;
use Eunoia\Core\Doctor\DoctorCheck;
use Illuminate\Support\Facades\DB;

class DatabaseCheck implements DoctorCheck
{
    public function label(): string
    {
        return __('eunoia-core::doctor.checks.database');
    }

    public function run(): CheckResult
    {
        $connection = DB::connection();
        $connection->getPdo();

        return CheckResult::ok(__('eunoia-core::doctor.messages.database_ok', [
            'driver'   => $connection->getDriverName(),
            'database' => $connection->getDatabaseName(),
        ]));
    }
}
