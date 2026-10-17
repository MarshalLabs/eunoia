<?php

namespace Eunoia\Core\Console\Commands;

use Eunoia\Core\Doctor\CheckResult;
use Eunoia\Core\Doctor\Checks\QueueWorkerCheck;
use Eunoia\Core\Doctor\Doctor;
use Eunoia\Core\Doctor\DoctorCheck;
use Illuminate\Console\Command;

class DoctorCommand extends Command
{
    protected $signature = 'eunoia:doctor {--timeout=10 : Seconds to wait for the queue worker heartbeat}';

    protected $description = 'Check that this Eunoia install is healthy (database, JOD precision, queue worker, locale, fonts)';

    public function handle(Doctor $doctor): int
    {
        $results = $doctor->run(function (DoctorCheck $check): void {
            if ($check instanceof QueueWorkerCheck) {
                $check->withTimeout((int) $this->option('timeout'));
            }
        });

        $icons = [
            CheckResult::OK   => '✓',
            CheckResult::WARN => '!',
            CheckResult::FAIL => '✕',
            CheckResult::SKIP => '–',
        ];

        $this->table(
            [__('eunoia-core::doctor.columns.check'), __('eunoia-core::doctor.columns.status'), __('eunoia-core::doctor.columns.details')],
            array_map(fn (array $row): array => [
                $row['check']->label(),
                $icons[$row['result']->status].' '.__('eunoia-core::doctor.status.'.$row['result']->status),
                $row['result']->message,
            ], $results),
        );

        $failed = array_filter($results, fn (array $row): bool => $row['result']->status === CheckResult::FAIL);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
