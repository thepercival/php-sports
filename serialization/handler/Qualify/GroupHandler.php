<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Qualify;

use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Target;
use Sports\Round;
use Sports\Category;
use Sports\Round\Number as RoundNumber;
use Sports\SerializationHandler\Handler;
use Sports\Structure\Cell;

/**
 * @psalm-type _Round = array{parentQualifyGroup: QualifyGroup, category: Category}
 */
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
     * @param array{parentRound: Round, target: string, nextStructureCell: Cell, number: int, childRound: _Round} $fieldValue
     * @param array<string, array<string, Round|RoundNumber>> $type
     * @param Context $context
     * @return QualifyGroup
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): QualifyGroup {
        if (!isset($fieldValue["parentRound"]) || !isset($fieldValue["nextStructureCell"])) {
            throw new \Exception('malformd json => qualifygroup', E_ERROR);
        }
        $qualifyGroup = new QualifyGroup(
            $fieldValue["parentRound"],
            Target::from($fieldValue["target"]),
            $fieldValue["nextStructureCell"],
            $fieldValue["number"]
        );
        //$fieldValue["childRound"] = $qualifyGroup->getChildRound();
        $fieldValue["childRound"]["parentQualifyGroup"] = $qualifyGroup;
        $fieldValue["childRound"]["category"] = $qualifyGroup->getParentRound()->getCategory();
        $this->getProperty(
            $visitor,
            $fieldValue,
            "childRound",
            Round::class
        );

        return $qualifyGroup;
    }
}
