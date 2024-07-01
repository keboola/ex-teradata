<?php

declare(strict_types=1);

use Keboola\Component\Logger;
use Keboola\Component\UserException;
use Keboola\ExTeradata\ActionComponent;
use Keboola\ExTeradata\CoreComponent;

require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger();
try {
    $dataDir = getenv('KBC_DATADIR');
    if ($dataDir === false || $dataDir === '') {
        $dataDir = '/data/';
    }
    $configJson = file_get_contents($dataDir . DIRECTORY_SEPARATOR . 'config.json');
    $configJson = json_decode((string) $configJson, true, 512, JSON_THROW_ON_ERROR);
    assert(is_array($configJson));
    $action = $configJson['action'] ?? 'run';

    $app = match ($action) {
        'run' => new CoreComponent($logger),
        default => new ActionComponent($logger),
    };
    $app->execute();
    exit(0);
} catch (UserException $e) {
    $logger->error($e->getMessage());
    exit(1);
} catch (Throwable $e) {
    $logger->critical(
        $e::class . ':' . $e->getMessage(),
        [
            'errFile' => $e->getFile(),
            'errLine' => $e->getLine(),
            'errCode' => $e->getCode(),
            'errTrace' => $e->getTraceAsString(),
            'errPrevious' => $e->getPrevious() instanceof Throwable ? $e->getPrevious()::class : '',
        ],
    );
    exit(2);
}
