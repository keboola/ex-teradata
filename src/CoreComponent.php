<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\BaseComponent;
use Keboola\ExTeradata\Config\CoreComponent\Config;
use Keboola\ExTeradata\Config\CoreComponent\ConfigDefinition;
use Keboola\ExTeradata\Factories\ConnectionFactory;
use Keboola\ExTeradata\Factories\CsvWriterFactory;

class CoreComponent extends BaseComponent
{
    public function run(): void
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $exceptionHandler = new ExceptionHandler();

        $connection = (new ConnectionFactory())->create(
            $config->getHost(),
            $config->getPort(),
            $config->getUser(),
            $config->getPassword(),
        );

        $extractorHelper = new ExtractorHelper();
        $extractor = new Extractor(
            $connection,
            new CsvWriterFactory(),
            $exceptionHandler,
            $this->getLogger(),
        );

        $query = $config->getQuery();
        if ($query === null) {
            assert($config->getSchema() !== null);
            assert($config->getTableName() !== null);
            $query = $extractorHelper->getExportSql(
                $config->getSchema(),
                $config->getTableName(),
                $config->getColumns(),
            );
        }
        $outputCsvFilePath = $this->getDataDir() . '/out/tables/' . $config->getOutputTable() . '.csv';

        $extractor->extractTable($query, $outputCsvFilePath);

        $manifestFileName = $outputCsvFilePath . '.manifest';
        $manifestData = [
            'incremental' => $config->getIncremental(),
            'primary_key' => $config->getPrimaryKey(),
        ];
        file_put_contents($manifestFileName, json_encode($manifestData, JSON_PRETTY_PRINT));

        $this->getLogger()->info(sprintf('Extracted table into: "%s".', $config->getOutputTable()));
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
