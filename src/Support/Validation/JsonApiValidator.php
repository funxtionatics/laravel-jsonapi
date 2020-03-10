<?php
namespace Czim\JsonApi\Support\Validation;

use Czim\JsonApi\Contracts\Support\Validation\JsonApiValidatorInterface;
use Czim\JsonApi\Enums\SchemaType;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use JsonSchema\Validator;

class JsonApiValidator implements JsonApiValidatorInterface
{
    public const SCHEMA_CREATE_PATH   = '../schemas/create.json';
    public const SCHEMA_REQUEST_PATH  = '../schemas/request.json';

    /**
     * @var false|MessageBagContract
     */
    protected $lastErrors = false;

    /**
     * Returns whether given array data validates against the basic JSON-API schema.
     *
     * @param array|object $data  data to be validated
     * @param string       $type  the type of schema to validate against
     * @return bool
     */
    public function validateSchema($data, string $type = SchemaType::REQUEST): bool
    {
        $validator = new Validator;

        if (is_array($data)) {
            $data = Validator::arrayToObjectRecursive($data);
        }

        $validator->validate(
            $data,
            (object) [
                '$ref' => 'file://' . $this->getSchemaOrgPath($type)
            ]
        );

        $this->storeErrors($validator->getErrors());

        return $validator->isValid();
    }

    /**
     * Returns the errors detected in the last validate call.
     *
     * @return MessageBagContract
     */
    public function getErrors(): MessageBagContract
    {
        if ( ! $this->lastErrors) {
            return new MessageBag;
        }

        return $this->lastErrors;
    }

    protected function getSchemaOrgPath(string $type = SchemaType::REQUEST): string
    {
        switch ($type) {

            case SchemaType::CREATE:
                $path = static::SCHEMA_CREATE_PATH;
                break;

            case SchemaType::REQUEST:
            default:
                $path = static::SCHEMA_REQUEST_PATH;
        }

        return realpath(__DIR__ . '/' . $path);
    }

    /**
     * Stores list of errors as a messagebag, if there are any.
     *
     * @param array $errors
     */
    protected function storeErrors(array $errors): void
    {
        if ( ! count($errors)) {
            $this->lastErrors = false;
            return;
        }

        $normalizedErrors = (new Collection($errors))
            ->groupBy(function ($error) {
                $property = Arr::get($error, 'property');
                if ('' === $property || null === $property) {
                    return '*';
                }
                return $property;
            })
            ->transform(function (Collection $errors) {
                return $errors->pluck('message');
            })
            ->toArray();

        ksort($normalizedErrors);

        $this->lastErrors = new MessageBag($normalizedErrors);
    }
}
