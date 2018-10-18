<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

class Debug
{
    public static function print(string $datadir): void
    {
        if (!is_dir($datadir . '/out/files')) {
            mkdir($datadir . '/out/files', 0777, true);
        }

        $traceFile = '/usr/odbcusr/trace.log';
        if (file_exists($traceFile)) {
            print PHP_EOL;
            print PHP_EOL;
            $trace = file_get_contents($traceFile);
            print "Trace File:" . PHP_EOL;
            print $trace;
            print PHP_EOL;

            file_put_contents($datadir . '/out/files/trace.log', $trace);
        } else {
            // print "Trace File does not exist." . PHP_EOL;
        }

        $debugFile = '/usr/odbcusr/debug.log';
        if (file_exists($debugFile)) {
            $debug = file_get_contents($debugFile);
            print "Debug File:" . PHP_EOL;
            print $debug;
            print PHP_EOL;

            file_put_contents($datadir . '/out/files/debug.log', $debug);
        } else {
            // print "Debug File does not exist." . PHP_EOL;
        }
    }
}
