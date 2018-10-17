<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

class Debug
{
    public static function print(): void
    {
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
    }
}
