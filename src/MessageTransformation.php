<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\UserException;

class MessageTransformation
{
    /**
     * @param array<mixed> $argumentIndexes
     */
    public function __construct(private string $pattern, private string $message, private array $argumentIndexes = [])
    {
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @param array<mixed> $arguments
     */
    public function getUserException(array $arguments = []): UserException
    {
        $args = [];
        foreach ($this->argumentIndexes as $argumentIndex) {
            $args[] = $arguments[$argumentIndex];
        }

        /** @phpstan-ignore-next-line */
        return new UserException(vsprintf($this->message, $args));
    }
}
