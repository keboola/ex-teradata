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
        case 'run':
            $app = new CoreComponent($logger);
            break;
        default:
            $app = new ActionComponent($logger);
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


    print PHP_EOL;
    print PHP_EOL;
    $traceFile = '/usr/odbcusr/trace.log';
    if (file_exists($traceFile)) {
        $trace = file_get_contents($traceFile);
        print "Trace File:" . PHP_EOL;
        print $trace;
        print PHP_EOL;
    } else {
        print "Trace File does not exist." . PHP_EOL;
    }

    $traceFile = '/usr/odbcusr/trace.log';
    if (file_exists($traceFile)) {
        $trace = file_get_contents($traceFile);
        print "Trace File:" . PHP_EOL;
        print $trace;
        print PHP_EOL;
    } else {
        print "Trace File does not exist." . PHP_EOL;
    }

    $debugFile = '/usr/odbcusr/debug.log';
    if (file_exists($debugFile)) {
        $debug = file_get_contents($debugFile);
        print "Debug File:" . PHP_EOL;
        print $debug;
        print PHP_EOL;
    } else {
        print "Debug File does not exist." . PHP_EOL;
    }

    exit(2);
}
