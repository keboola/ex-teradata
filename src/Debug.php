<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\Logger;

class Debug
{
    public static function print(Logger $logger, string $datadir): void
    {
        $traceFile = '/usr/odbcusr/trace.log';
        if (file_exists($traceFile)) {
            $logger->info('Trace File:');
            $handle = fopen($traceFile, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $logger->info((string) $line);
                }
                fclose($handle);
            }
        } else {
            // print "Trace File does not exist." . PHP_EOL;
        }

        $debugFile = '/usr/odbcusr/debug.log';
        if (file_exists($debugFile)) {
            $logger->info('Debug File:');
            $handle = fopen($debugFile, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $logger->info((string) $line);
                }
                fclose($handle);
            }
        } else {
            // print "Debug File does not exist." . PHP_EOL;
        }
    }
}
