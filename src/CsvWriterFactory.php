<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Csv\CsvWriter;

class CsvWriterFactory
{
    public function create(string $outputCsvFilePath): CsvWriter
    {
        return new CsvWriter(
            $outputCsvFilePath,
            CsvWriter::DEFAULT_DELIMITER,
            CsvWriter::DEFAULT_ENCLOSURE,
            "\r\n"
        );
    }
}
