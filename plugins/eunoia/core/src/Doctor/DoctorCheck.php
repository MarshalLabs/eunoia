<?php

namespace Eunoia\Core\Doctor;

interface DoctorCheck
{
    public function label(): string;

    public function run(): CheckResult;
}
