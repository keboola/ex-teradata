<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\UserException;

class ExceptionHandler
{
    public function handleException(
        \Throwable $exception,
        ?string $databaseName = null,
        ?string $tableName = null
    ): void {
        if (preg_match(
            '~The Teradata server can\'t currently be reached over this network~',
            $exception->getMessage()
        )) {
            throw new UserException('The Teradata server can\'t currently be reached over this network.');
        } elseif (preg_match(
            '~The UserId, Password or Account is invalid.~',
            $exception->getMessage()
        )) {
            throw new UserException('The Username or Password is invalid.');
        } elseif (preg_match('~Object \'.+\..+\' does not exist.~', $exception->getMessage())) {
            throw new UserException(sprintf(
                'Table \'%s\' does not exist in database \'%s\'.',
                $tableName,
                $databaseName
            ));
        } elseif (preg_match('~Database \'.+\' does not exist.~', $exception->getMessage())) {
            throw new UserException(sprintf(
                'Database \'%s\' does not exist.',
                $databaseName
            ));
        } else {
            throw $exception;
        }
    }
}
