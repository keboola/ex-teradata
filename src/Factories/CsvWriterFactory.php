<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Factories;

use Keboola\Csv\CsvWriter;

class CsvWriterFactory
{
    public function create(string $outputCsvFilePath): CsvWriter
    {
        return new CsvWriter($outputCsvFilePath);
    }
}
