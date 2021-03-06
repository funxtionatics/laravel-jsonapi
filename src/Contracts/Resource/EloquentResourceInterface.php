<?php
namespace Czim\JsonApi\Contracts\Resource;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Czim\JsonApi\Exceptions\InvalidIncludeException;

interface EloquentResourceInterface extends ResourceInterface
{
    /**
     * Sets the model instance to use.
     *
     * This should be done before calling any other method, unless
     * a model is guaranteed to be set using the constructor.
     *
     * @param Model $model
     * @return $this|EloquentResourceInterface
     */
    public function setModel(Model $model): EloquentResourceInterface;

    public function getModel(): ?Model;

    /**
     * Returns the Eloquent relation method for an include key/name, if possible.
     *
     * @param string $name
     * @return Relation|null
     * @throws InvalidIncludeException
     */
    public function includeRelation(string $name): ?Relation;

    /**
     * Returns the Eloquent relation method for a given include name.
     *
     * @param string $name
     * @return string
     * @throws InvalidIncludeException
     */
    public function getRelationMethodForInclude(string $name): ?string;

    /**
     * Returns the model attribute for a given JSON-API attribute, if available.
     *
     * @param string $name
     * @return string|null
     */
    public function getModelAttributeForApiAttribute(string $name): ?string;
}
