<?php
namespace Czim\JsonApi\Contracts\Support\Resource;

use Czim\JsonApi\Contracts\Resource\ResourceInterface;

interface ResourcePathHelperInterface
{
    /**
     * Makes a relative URL path for a given resource.
     *
     * @param ResourceInterface $resource
     * @return string
     */
    public function makePath(ResourceInterface $resource): string;
}
