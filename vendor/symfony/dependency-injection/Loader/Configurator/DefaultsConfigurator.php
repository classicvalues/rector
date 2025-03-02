<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210828\Symfony\Component\DependencyInjection\Loader\Configurator;

use RectorPrefix20210828\Symfony\Component\DependencyInjection\Definition;
use RectorPrefix20210828\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class DefaultsConfigurator extends \RectorPrefix20210828\Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator
{
    use Traits\AutoconfigureTrait;
    use Traits\AutowireTrait;
    use Traits\BindTrait;
    use Traits\PublicTrait;
    public const FACTORY = 'defaults';
    private $path;
    public function __construct(\RectorPrefix20210828\Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator $parent, \RectorPrefix20210828\Symfony\Component\DependencyInjection\Definition $definition, string $path = null)
    {
        parent::__construct($parent, $definition, null, []);
        $this->path = $path;
    }
    /**
     * Adds a tag for this definition.
     *
     * @return $this
     *
     * @throws InvalidArgumentException when an invalid tag name or attribute is provided
     * @param string $name
     * @param mixed[] $attributes
     */
    public final function tag($name, $attributes = []) : self
    {
        if ('' === $name) {
            throw new \RectorPrefix20210828\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException('The tag name in "_defaults" must be a non-empty string.');
        }
        foreach ($attributes as $attribute => $value) {
            if (null !== $value && !\is_scalar($value)) {
                throw new \RectorPrefix20210828\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException(\sprintf('Tag "%s", attribute "%s" in "_defaults" must be of a scalar-type.', $name, $attribute));
            }
        }
        $this->definition->addTag($name, $attributes);
        return $this;
    }
    /**
     * Defines an instanceof-conditional to be applied to following service definitions.
     * @param string $fqcn
     */
    public final function instanceof($fqcn) : \RectorPrefix20210828\Symfony\Component\DependencyInjection\Loader\Configurator\InstanceofConfigurator
    {
        return $this->parent->instanceof($fqcn);
    }
}
