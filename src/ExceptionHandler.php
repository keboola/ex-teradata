<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\UserException;

class ExceptionHandler
{
    public function handleException(\Throwable $exception): void
    {
        if (preg_match(
            '~The Teradata server can\'t currently be reached over this network~',
            $exception->getMessage()
        )) {
            throw new UserException('The Teradata server can\'t currently be reached over this network.');
        } elseif (preg_match(
            '~The UserId, Password or Account is invalid.~',
            $exception->getMessage()
        )) {
            throw new UserException('The User or Password is invalid.');
        } elseif (preg_match(
            '~Object \'(.+)\.(.+)\' does not exist.~',
            $exception->getMessage(),
            $matches
        )) {
            throw new UserException(sprintf(
                'Table \'%s\' does not exist in database \'%s\'.',
                $matches[2],
                $matches[1]
            ));
        } elseif (preg_match(
            '~Database \'(.+)\' does not exist.~',
            $exception->getMessage(),
            $matches
        )) {
            throw new UserException(sprintf(
                'Database \'%s\' does not exist.',
                $matches[1]
            ));
        } else {
            throw $exception;
        }
    }
}
