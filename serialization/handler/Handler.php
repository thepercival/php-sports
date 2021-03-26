<?php
declare(strict_types=1);

namespace Sports\SerializationHandler;

use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Metadata\StaticPropertyMetadata;

class Handler
{
    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getDeserializationMethods(string $className): array
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => $className,
                'method' => 'deserializeFromJson',
            ],
        ];
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array<string| mixed> $fieldValue
     * @param string $property
     * @param string $className
     * @return mixed
     */
    protected function getProperty(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        string $property,
        string $className): mixed
    {
        if (!isset($fieldValue[$property])) {
            return null;
        }
        $metadataConfig = new StaticPropertyMetadata($className, $property, $fieldValue[$property]);
        $metadataConfig->setType(['name' => $className, "params" => []]);
        return $visitor->visitProperty($metadataConfig, $fieldValue);
    }
}
