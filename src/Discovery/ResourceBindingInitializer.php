<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Discovery;

use Puli\Discovery\Api\Binding\Binding;
use Puli\Discovery\Api\Binding\Initializer\BindingInitializer;
use Puli\Repository\Api\ResourceRepository;
use Webmozart\Assert\Assert;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceBindingInitializer implements BindingInitializer
{
    /**
     * @var ResourceRepository
     */
    private $repo;

    /**
     * @param ResourceRepository $repo
     */
    public function __construct(ResourceRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptsBinding($binding)
    {
        return $binding instanceof ResourceBinding
            || $binding === 'Puli\Repository\Discovery\ResourceBinding'
            || is_subclass_of($binding, 'Puli\Repository\Discovery\ResourceBinding');
    }

    /**
     * {@inheritdoc}
     */
    public function getAcceptedBindingClass()
    {
        return 'Puli\Repository\Discovery\ResourceBinding';
    }

    /**
     * {@inheritdoc}
     */
    public function initializeBinding(Binding $binding)
    {
        Assert::isInstanceOf($binding, 'Puli\Repository\Discovery\ResourceBinding', 'The binding must be an instance of ResourceBinding. Got: %s');

        /* @var ResourceBinding $binding */
        $binding->setRepository($this->repo);
    }
}
