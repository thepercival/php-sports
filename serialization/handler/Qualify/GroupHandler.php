<?php
declare(strict_types=1);

namespace Sports\SerializationHandler\Qualify;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Target;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\SerializationHandler\Handler;

class GroupHandler extends Handler implements SubscribingHandlerInterface
{
    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(QualifyGroup::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array<string, int|string|array> $fieldValue
     * @param array<string, array<string, Round|RoundNumber>> $type
     * @param Context $context
     * @return QualifyGroup
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): QualifyGroup
    {
        if (!isset($fieldValue["parentRound"]) || !isset($fieldValue["nextRoundNumber"])) {
            throw new \Exception('malformd json => qualifygroup', E_ERROR);
        }
        /** @var Round $parentRound */
        $parentRound = $fieldValue["parentRound"];
        /** @var RoundNumber $nextRoundNumber */
        $nextRoundNumber = $fieldValue["nextRoundNumber"];
        $qualifyGroup = new QualifyGroup(
            $parentRound,
            Target::from($fieldValue["target"]),
            $nextRoundNumber,
            $fieldValue["number"]);
        //$fieldValue["childRound"] = $qualifyGroup->getChildRound();
        $fieldValue["childRound"]["parentQualifyGroup"] = $qualifyGroup;
        $this->getProperty(
            $visitor,
            $fieldValue,
            "childRound",
            Round::class);

        return $qualifyGroup;
    }
}
