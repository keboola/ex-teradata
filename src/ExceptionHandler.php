<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

class ExceptionHandler
{
    /** @var MessageTransformation[] */
    private $messageTransformations = [];

    public function __construct()
    {
        $this->messageTransformations = [
            new MessageTransformation(
                '~Can\'t assign requested address 08S01~',
                'Cannot assign requested address.'
            ),
            new MessageTransformation(
                '~Network is unreachable 08S01~',
                'Network is unreachable.'
            ),
            new MessageTransformation(
                '~The Teradata server is not accepting connections 08004~',
                'The Teradata server is not accepting connections.'
            ),
            new MessageTransformation(
                '~No response received when attempting to connect to the Teradata server S1000~',
                'No response received when attempting to connect to the Teradata server.'
            ),
            new MessageTransformation(
                '~The Teradata server can\'t currently be reached over this network~',
                'The Teradata server can\'t currently be reached over this network.'
            ),
            new MessageTransformation(
                '~The UserId, Password or Account is invalid.~',
                'The User or Password is invalid.'
            ),
            new MessageTransformation(
                '~Object \'([^\']+)\.([^\']+)\' does not exist.~',
                'Table "%s" does not exist in database "%s".',
                [2, 1]
            ),
            new MessageTransformation(
                '~Database \'([^\']+)\' does not exist.~',
                'Database "%s" does not exist.',
                [1]
            ),
        ];
    }

    public function createException(\Throwable $exception): \Throwable
    {
        foreach ($this->messageTransformations as $messageTransformation) {
            if (preg_match(
                $messageTransformation->getPattern(),
                $exception->getMessage(),
                $matches
            )) {
                return $messageTransformation->getUserException($matches);
            }
        }

        return $exception;
    }
}
