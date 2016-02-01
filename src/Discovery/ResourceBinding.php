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
use Puli\Discovery\Api\Binding\Initializer\NotInitializedException;
use Puli\Discovery\Api\Type\MissingParameterException;
use Puli\Discovery\Api\Type\NoSuchParameterException;
use Puli\Discovery\Binding\AbstractBinding;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\ResourceRepository;

/**
 * Binds one or more resources to a binding type.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceBinding extends AbstractBinding
{
    /**
     * @var string
     */
    private $query;

    /**
     * @var string
     */
    private $language;

    /**
     * @var ResourceRepository
     */
    private $repo;

    /**
     * Creates a new resource binding.
     *
     * A resource binding has a query that is used to retrieve the resources
     * matched by the binding.
     *
     * You can pass parameters that have been defined for the type. If you pass
     * unknown parameters, or if a required parameter is missing, an exception
     * is thrown.
     *
     * All parameters that you do not set here will receive the default values
     * set for the parameter.
     *
     * @param string $query           The resource query.
     * @param string $typeName        The type to bind against.
     * @param array  $parameterValues The values of the parameters defined
     *                                for the type.
     * @param string $language        The language of the resource query.
     *
     * @throws NoSuchParameterException  If an invalid parameter was passed.
     * @throws MissingParameterException If a required parameter was not passed.
     */
    public function __construct($query, $typeName, array $parameterValues = array(), $language = 'glob')
    {
        parent::__construct($typeName, $parameterValues);

        $this->query = $query;
        $this->language = $language;
    }

    /**
     * Returns the query for the resources of the binding.
     *
     * @return string The resource query.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Returns the language of the query.
     *
     * @return string The query language.
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Returns the bound resources.
     *
     * @return ResourceCollection The bound resources.
     */
    public function getResources()
    {
        if (null === $this->repo) {
            throw new NotInitializedException('The repository of the resource binding must be set before accessing resources.');
        }

        return $this->repo->find($this->query, $this->language);
    }

    /**
     * Sets the repository used to load resources.
     *
     * @param ResourceRepository $repo The resource repository.
     */
    public function setRepository(ResourceRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(Binding $other)
    {
        if (!parent::equals($other)) {
            return false;
        }

        /** @var ResourceBinding $other */
        if ($this->query !== $other->query) {
            return false;
        }

        return $this->language === $other->language;
    }

    /**
     * {@inheritdoc}
     */
    protected function preSerialize(array &$data)
    {
        parent::preSerialize($data);

        $data[] = $this->query;
        $data[] = $this->language;
    }

    /**
     * {@inheritdoc}
     */
    protected function postUnserialize(array &$data)
    {
        $this->language = array_pop($data);
        $this->query = array_pop($data);

        parent::postUnserialize($data);
    }
}
