<?php
declare(strict_types=1);

namespace Sports\SerializationHandler\Qualify;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Sports\Qualify\Group as QualifyGroupBase;

class Group implements SubscribingHandlerInterface
{
    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods()
    {
        return [
//            [
//                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
//                'format' => 'json',
//                'type' => 'DateTime',
//                'method' => 'serializeToJson',
//            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'Sports\Qualify\Group',
                'method' => 'deserializeFromJson',
            ],
        ];
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array<string, int|string|array> $arrQualifyGroup
     * @param array<string, int|string|array|null> $type
     * @param Context $context
     * @return QualifyGroupBase
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $arrQualifyGroup,
        array $type,
        Context $context
    ): QualifyGroupBase
    {
        $qualifyGroup = new QualifyGroupBase($type["params"]["round"], $arrQualifyGroup["winnersOrLosers"], $arrQualifyGroup["number"]);
        return $qualifyGroup;
    }
}
