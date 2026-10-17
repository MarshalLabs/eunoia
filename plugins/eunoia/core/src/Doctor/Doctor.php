<?php

namespace Eunoia\Core\Doctor;

use Throwable;

/**
 * Runs health checks for `eunoia:doctor`. Other Eunoia plugins add their own
 * checks with `app(Doctor::class)->register(MyCheck::class)`.
 */
class Doctor
{
    /**
     * @param  array<int, class-string<DoctorCheck>>  $checks
     */
    public function __construct(protected array $checks = []) {}

    /**
     * @param  class-string<DoctorCheck>  $check
     */
    public function register(string $check): static
    {
        if (! in_array($check, $this->checks, true)) {
            $this->checks[] = $check;
        }

        return $this;
    }

    /**
     * @param  class-string<DoctorCheck>  $old
     * @param  class-string<DoctorCheck>  $new
     */
    public function replace(string $old, string $new): static
    {
        $index = array_search($old, $this->checks, true);

        if ($index === false) {
            return $this->register($new);
        }

        $this->checks[$index] = $new;
        $this->checks = array_values(array_unique($this->checks));

        return $this;
    }

    /**
     * @return array<int, class-string<DoctorCheck>>
     */
    public function checks(): array
    {
        return $this->checks;
    }

    /**
     * @return array<int, array{check: DoctorCheck, result: CheckResult}>
     */
    public function run(?callable $configure = null): array
    {
        $results = [];

        foreach ($this->checks as $class) {
            $check = app($class);

            if ($configure) {
                $configure($check);
            }

            try {
                $result = $check->run();
            } catch (Throwable $e) {
                $result = CheckResult::fail($e->getMessage());
            }

            $results[] = ['check' => $check, 'result' => $result];
        }

        return $results;
    }
}
