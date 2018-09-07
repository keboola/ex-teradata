<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\UserException;

class ExceptionHandler
{
    public function createException(\Throwable $exception): \Throwable
    {
        if (preg_match(
            '~Can\'t assign requested address 08S01~',
            $exception->getMessage()
        )) {
            return new UserException('Cannot assign requested address.');
        } elseif (preg_match(
            '~Network is unreachable 08S01~',
            $exception->getMessage()
        )) {
            return new UserException('Network is unreachable.');
        } elseif (preg_match(
            '~The Teradata server is not accepting connections 08004~',
            $exception->getMessage()
        )) {
            return new UserException('The Teradata server is not accepting connections.');
        } elseif (preg_match(
            '~No response received when attempting to connect to the Teradata server S1000~',
            $exception->getMessage()
        )) {
            return new UserException('No response received when attempting to connect to the Teradata server.');
        } elseif (preg_match(
            '~The Teradata server can\'t currently be reached over this network~',
            $exception->getMessage()
        )) {
            return new UserException('The Teradata server can\'t currently be reached over this network.');
        } elseif (preg_match(
            '~The UserId, Password or Account is invalid.~',
            $exception->getMessage()
        )) {
            return new UserException('The User or Password is invalid.');
        } elseif (preg_match(
            '~Object \'([^\']+)\.([^\']+)\' does not exist.~',
            $exception->getMessage(),
            $matches
        )) {
            return new UserException(sprintf(
                'Table "%s" does not exist in database "%s".',
                $matches[2],
                $matches[1]
            ));
        } elseif (preg_match(
            '~Database \'([^\']+)\' does not exist.~',
            $exception->getMessage(),
            $matches
        )) {
            return new UserException(sprintf(
                'Database "%s" does not exist.',
                $matches[1]
            ));
        }

        return $exception;
    }
}
