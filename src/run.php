<?php

declare(strict_types=1);

use Keboola\Component\UserException;
use Keboola\Component\Logger;
use Keboola\ExTeradata\ActionComponent;
use Keboola\ExTeradata\CoreComponent;

require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger();
try {
    $dataDir = getenv('KBC_DATADIR') ?? '/data/';
    $configJson = file_get_contents($dataDir . DIRECTORY_SEPARATOR . 'config.json');
    $action = json_decode((string) $configJson, true)['action'] ?? 'run';

    switch ($action) {
        case 'testConnection':
            $app = new ActionComponent($logger);
            break;
        default:
            $app = new CoreComponent($logger);
    }
    $app->run();
    exit(0);
} catch (UserException $e) {
    $logger->error($e->getMessage());
    exit(1);
} catch (\Throwable $e) {
    $logger->critical(
        get_class($e) . ':' . $e->getMessage(),
        [
            'errFile' => $e->getFile(),
            'errLine' => $e->getLine(),
            'errCode' => $e->getCode(),
            'errTrace' => $e->getTraceAsString(),
            'errPrevious' => $e->getPrevious() ? get_class($e->getPrevious()) : '',
        ]
    );
    exit(2);
}
