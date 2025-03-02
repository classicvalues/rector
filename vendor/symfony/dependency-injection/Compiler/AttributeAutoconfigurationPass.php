<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210828\Symfony\Component\DependencyInjection\Compiler;

use RectorPrefix20210828\Symfony\Component\DependencyInjection\ChildDefinition;
use RectorPrefix20210828\Symfony\Component\DependencyInjection\ContainerBuilder;
use RectorPrefix20210828\Symfony\Component\DependencyInjection\Definition;
/**
 * @author Alexander M. Turek <me@derrabus.de>
 */
final class AttributeAutoconfigurationPass extends \RectorPrefix20210828\Symfony\Component\DependencyInjection\Compiler\AbstractRecursivePass
{
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process($container) : void
    {
        if (80000 > \PHP_VERSION_ID || !$container->getAutoconfiguredAttributes()) {
            return;
        }
        parent::process($container);
    }
    /**
     * @param bool $isRoot
     */
    protected function processValue($value, $isRoot = \false)
    {
        if (!$value instanceof \RectorPrefix20210828\Symfony\Component\DependencyInjection\Definition || !$value->isAutoconfigured() || $value->isAbstract() || $value->hasTag('container.ignore_attributes') || !($reflector = $this->container->getReflectionClass($value->getClass(), \false))) {
            return parent::processValue($value, $isRoot);
        }
        $autoconfiguredAttributes = $this->container->getAutoconfiguredAttributes();
        $instanceof = $value->getInstanceofConditionals();
        $conditionals = $instanceof[$reflector->getName()] ?? new \RectorPrefix20210828\Symfony\Component\DependencyInjection\ChildDefinition('');
        foreach ($reflector->getAttributes() as $attribute) {
            if ($configurator = $autoconfiguredAttributes[$attribute->getName()] ?? null) {
                $configurator($conditionals, $attribute->newInstance(), $reflector);
            }
        }
        if (!isset($instanceof[$reflector->getName()]) && new \RectorPrefix20210828\Symfony\Component\DependencyInjection\ChildDefinition('') != $conditionals) {
            $instanceof[$reflector->getName()] = $conditionals;
            $value->setInstanceofConditionals($instanceof);
        }
        return parent::processValue($value, $isRoot);
    }
}
