<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210828\Symfony\Component\DependencyInjection\Loader\Configurator\Traits;

trait ClassTrait
{
    /**
     * Sets the service class.
     *
     * @return $this
     * @param string|null $class
     */
    public final function class($class) : self
    {
        $this->definition->setClass($class);
        return $this;
    }
}
