<?php

declare (strict_types=1);
namespace RectorPrefix20210828\Idiosyncratic\EditorConfig\Declaration;

use function in_array;
use function is_numeric;
use function sprintf;
use function strtolower;
abstract class Declaration
{
    /** @var string */
    private $name;
    /** @var string */
    private $stringValue;
    /** @var mixed */
    private $value;
    public function __construct(string $value)
    {
        $typedValue = $this->getTypedValue($value);
        $this->setStringValue($value);
        $this->validateValue($typedValue);
        $this->setValue($typedValue);
    }
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * @return mixed
     */
    public final function getValue()
    {
        return $this->value;
    }
    public final function getStringValue() : string
    {
        return $this->stringValue;
    }
    /**
     * @param mixed $value
     */
    public function validateValue($value) : void
    {
        return;
    }
    public final function __toString() : string
    {
        return \sprintf('%s=%s', $this->getName(), $this->getStringValue());
    }
    /**
     * @param string $name
     */
    protected function setName($name) : void
    {
        $this->name = \strtolower($name);
    }
    /**
     * @return mixed
     * @param string $value
     */
    protected function getTypedValue($value)
    {
        if (\in_array($value, ['true', 'false']) === \true) {
            return $value === 'true';
        }
        if (\is_numeric($value) === \true && (string) (int) $value === $value) {
            return (int) $value;
        }
        return $value;
    }
    /**
     * @param string $value
     */
    protected final function setStringValue($value) : void
    {
        $this->stringValue = $value;
    }
    /**
     * @param mixed $value
     */
    protected final function setValue($value) : void
    {
        $this->value = $value;
    }
}
