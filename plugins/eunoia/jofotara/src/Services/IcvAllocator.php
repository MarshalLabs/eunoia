<?php

namespace Eunoia\JoFotara\Services;

use Illuminate\Support\Facades\DB;

/**
 * Gap-free Invoice Counter Value per company (JF-04): SELECT … FOR UPDATE on the counter row.
 * Call inside the same transaction that stores the document, so a rollback also gives the number back.
 */
class IcvAllocator
{
    public function __construct(
        protected ?string $connection = null,
        protected string $table = 'eunoia_jofotara_counters',
    ) {}

    public function next(int $companyId): int
    {
        $db = DB::connection($this->connection);

        return $db->transaction(function () use ($db, $companyId): int {
            $db->table($this->table)->insertOrIgnore(['company_id' => $companyId, 'next_icv' => 1]);

            $current = (int) $db->table($this->table)->where('company_id', $companyId)->lockForUpdate()->value('next_icv');

            $db->table($this->table)->where('company_id', $companyId)->update(['next_icv' => $current + 1]);

            return $current;
        });
    }
}
