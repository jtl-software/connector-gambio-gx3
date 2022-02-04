<?php

namespace jtl\Connector\Gambio\Exception;

/**
 *
 */
class ControllerException extends \Exception
{
    /**
     *
     */
    public const
        VALUE_CANNOT_BE_EMPTY = 100;

    /**
     * @param string $variableName
     * @return static
     */
    public static function valueCannotBeEmpty(string $variableName): self
    {
        return new self(sprintf('Value of `%s` cannot be empty.', $variableName), self::VALUE_CANNOT_BE_EMPTY);
    }
}