<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\UserException;

class MessageTransformation
{
    /** @var string */
    private $pattern;

    /** @var string */
    private $message;

    /** @var array */
    private $argumentIndexes;

    public function __construct(string $pattern, string $message, array $argumentIndexes = [])
    {
        $this->pattern = $pattern;
        $this->message = $message;
        $this->argumentIndexes = $argumentIndexes;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getUserException(array $arguments = []): UserException
    {
        $args = [];
        foreach ($this->argumentIndexes as $argumentIndex) {
            $args[] = $arguments[$argumentIndex];
        }

        return new UserException(vsprintf($this->message, $args));
    }
}
