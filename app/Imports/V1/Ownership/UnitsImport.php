<?php

namespace App\Imports\V1\Ownership;

use App\Services\V1\Ownership\UnitImportService;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Collection;

class UnitsImport implements ToCollection, WithHeadingRow, SkipsOnFailure
{
    protected int $ownershipId;
    protected ?int $buildingId;
    protected bool $skipErrors;
    protected array $failures = [];
    protected array $results = [];

    public function __construct(int $ownershipId, ?int $buildingId = null, bool $skipErrors = false)
    {
        $this->ownershipId = $ownershipId;
        $this->buildingId = $buildingId;
        $this->skipErrors = $skipErrors;
    }

    public function collection(Collection $rows)
    {
        $importService = new UnitImportService($this->ownershipId, $this->buildingId);
        $this->results = $importService->import($rows, $this->skipErrors);
        return $rows;
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ];
        }
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}

