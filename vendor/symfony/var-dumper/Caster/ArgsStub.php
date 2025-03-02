<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210828\Symfony\Component\VarDumper\Caster;

use RectorPrefix20210828\Symfony\Component\VarDumper\Cloner\Stub;
/**
 * Represents a list of function arguments.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class ArgsStub extends \RectorPrefix20210828\Symfony\Component\VarDumper\Caster\EnumStub
{
    private static $parameters = [];
    public function __construct(array $args, string $function, ?string $class)
    {
        [$variadic, $params] = self::getParameters($function, $class);
        $values = [];
        foreach ($args as $k => $v) {
            $values[$k] = !\is_scalar($v) && !$v instanceof \RectorPrefix20210828\Symfony\Component\VarDumper\Cloner\Stub ? new \RectorPrefix20210828\Symfony\Component\VarDumper\Caster\CutStub($v) : $v;
        }
        if (null === $params) {
            parent::__construct($values, \false);
            return;
        }
        if (\count($values) < \count($params)) {
            $params = \array_slice($params, 0, \count($values));
        } elseif (\count($values) > \count($params)) {
            $values[] = new \RectorPrefix20210828\Symfony\Component\VarDumper\Caster\EnumStub(\array_splice($values, \count($params)), \false);
            $params[] = $variadic;
        }
        if (['...'] === $params) {
            $this->dumpKeys = \false;
            $this->value = $values[0]->value;
        } else {
            $this->value = \array_combine($params, $values);
        }
    }
    private static function getParameters(string $function, ?string $class) : array
    {
        if (isset(self::$parameters[$k = $class . '::' . $function])) {
            return self::$parameters[$k];
        }
        try {
            $r = null !== $class ? new \ReflectionMethod($class, $function) : new \ReflectionFunction($function);
        } catch (\ReflectionException $e) {
            return [null, null];
        }
        $variadic = '...';
        $params = [];
        foreach ($r->getParameters() as $v) {
            $k = '$' . $v->name;
            if ($v->isPassedByReference()) {
                $k = '&' . $k;
            }
            if ($v->isVariadic()) {
                $variadic .= $k;
            } else {
                $params[] = $k;
            }
        }
        return self::$parameters[$k] = [$variadic, $params];
    }
}
