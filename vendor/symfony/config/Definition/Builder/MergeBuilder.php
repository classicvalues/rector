<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210828\Symfony\Component\Config\Definition\Builder;

/**
 * This class builds merge conditions.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class MergeBuilder
{
    protected $node;
    public $allowFalse = \false;
    public $allowOverwrite = \true;
    public function __construct(\RectorPrefix20210828\Symfony\Component\Config\Definition\Builder\NodeDefinition $node)
    {
        $this->node = $node;
    }
    /**
     * Sets whether the node can be unset.
     *
     * @return $this
     * @param bool $allow
     */
    public function allowUnset($allow = \true)
    {
        $this->allowFalse = $allow;
        return $this;
    }
    /**
     * Sets whether the node can be overwritten.
     *
     * @return $this
     * @param bool $deny
     */
    public function denyOverwrite($deny = \true)
    {
        $this->allowOverwrite = !$deny;
        return $this;
    }
    /**
     * Returns the related node.
     *
     * @return NodeDefinition|ArrayNodeDefinition|VariableNodeDefinition
     */
    public function end()
    {
        return $this->node;
    }
}
