<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Qualify;

use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use Sports\Qualify\QualifyDistribution;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\QualifyTarget;
use Sports\Round;
use Sports\Category;
use Sports\Round\Number as RoundNumber;
use Sports\SerializationHandler\Handler;
use Sports\Structure\StructureCell;

/**
 * @psalm-type _Round = array{parentQualifyGroup: QualifyGroup, category: Category}
 */
final class GroupHandler extends Handler implements SubscribingHandlerInterface
{
    #[\Override]
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(QualifyGroup::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array{parentRound: Round, target: string, nextStructureCell: StructureCell, number: int, childRound: _Round, distribution: string} $fieldValue
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
        if (!isset($fieldValue['parentRound']) || !isset($fieldValue['nextStructureCell'])) {
            throw new \Exception('malformd json => qualifygroup', E_ERROR);
        }
        $qualifyGroup = new QualifyGroup(
            $fieldValue['parentRound'],
            QualifyTarget::from($fieldValue['target']),
            $fieldValue['nextStructureCell'],
            $fieldValue['number']
        );
        $qualifyGroup->setDistribution(QualifyDistribution::from($fieldValue['distribution']));

        //$fieldValue["childRound"] = $qualifyGroup->getChildRound();
        $fieldValue['childRound']['parentQualifyGroup'] = $qualifyGroup;
        $fieldValue['childRound']['category'] = $qualifyGroup->getParentRound()->getCategory();
        $this->getProperty($visitor, $fieldValue, 'childRound', Round::class);

        return $qualifyGroup;
    }
}
