<?php

namespace Eunoia\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class LintRtlCommand extends Command
{
    protected $signature = 'eunoia:lint-rtl {paths?* : Paths to check (default plugins/eunoia)}';

    protected $description = 'Fail on physical (non-RTL-safe) CSS classes in Eunoia code';

    public function handle(): int
    {
        $result = Process::path(base_path())
            ->run(['bash', '.github/scripts/eunoia-rtl-lint.sh', ...$this->argument('paths')]);

        $this->output->write($result->output().$result->errorOutput());

        return $result->exitCode() === 0 ? self::SUCCESS : self::FAILURE;
    }
}
