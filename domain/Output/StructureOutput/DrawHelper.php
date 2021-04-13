<?php
declare(strict_types=1);

namespace Sports\Output\StructureOutput;

use Sports\Output\Grid\Drawer;
use Sports\Output\Coordinate;
use Sports\NameService;
use Sports\Poule;
use Sports\Round;
use Sports\Structure;
use Sports\Round\Number as RoundNumber;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use SportsHelpers\Output\Color;

final class DrawHelper
{
    use Color;
    
    protected NameService $nameService;
    protected RangeCalculator $rangeCalculator;

    public function __construct(protected Drawer $drawer)
    {
        $this->nameService = new NameService();
        $this->rangeCalculator = new RangeCalculator();
    }

    public function drawStructure(Structure $structure, Coordinate $origin): Coordinate
    {
        $roundNumberHeight = $this->rangeCalculator->getRoundNumberHeight($structure->getFirstRoundNumber());
        $roundCoordinate = $this->getRoundStartCoordinate($origin, $structure->getFirstRoundNumber(), $structure);
        $rounds = [$structure->getRootRound()];
        foreach ($rounds as $round) {
            $roundCoordinate = $this->drawRound($round, $roundCoordinate, $roundNumberHeight);
        }
        return $roundCoordinate;
    }

    protected function getRoundStartCoordinate(Coordinate $origin, RoundNumber $roundNumber, Structure $structure): Coordinate
    {
        $structureWidth = $this->rangeCalculator->getStructureWidth($structure);
        $roundNumberWidth = $this->rangeCalculator->getRoundNumberWidth($roundNumber);
        $delta = (int)floor(($structureWidth - $roundNumberWidth) / 2);
        return $origin->addX($delta);
    }

    public function drawRound(Round $round, Coordinate $origin, int $roundNumberHeight): Coordinate
    {
        $this->drawRoundBorder($round, $origin, $roundNumberHeight);

        $pouleCoordinate = $this->getPoulesStartCoordinate($origin, $round);
        foreach ($round->getPoules() as $poule) {
            $pouleCoordinate = $this->drawPoule($poule, $pouleCoordinate);
        }

        $qualifyRulesOrigin = $this->drawHorPoules($round, $pouleCoordinate->incrementX());
        if ($qualifyRulesOrigin !== null) {
            $this->drawQualifyRules($round, $qualifyRulesOrigin);
        }
        
        $nextRoundNumber = $round->getNumber()->getNext();
        if ($nextRoundNumber !== null) {
            $nextRoundNumberHeight = $this->rangeCalculator->getRoundNumberHeight($nextRoundNumber);
            $this->drawQualifyGroups($round, $origin->addY($roundNumberHeight), $nextRoundNumberHeight);
        }
        $roundWidth = $this->rangeCalculator->getRoundWidth($round);
        return $origin->addX($roundWidth + RangeCalculator::PADDING);


//        $batchColor = $this->useColors() ? ($batchNr % 10) : -1;
//        $retVal = 'batch ' . ($batchNr < 10 ? ' ' : '') . $batchNr;
//        return $this->outputColor($batchColor, $retVal);
    }

    protected function getPoulesStartCoordinate(Coordinate $origin, Round $round): Coordinate
    {
        $newCoordinate = $origin->add(RangeCalculator::BORDER, RangeCalculator::BORDER);

        $innerRoundWidth = $this->rangeCalculator->getRoundWidth($round) - (2 * RangeCalculator::BORDER);
        $poulesWidth = $this->rangeCalculator->getAllPoulesWidth($round);
        $delta = (int)floor(($innerRoundWidth - $poulesWidth) / 2);
        return $newCoordinate->addX($delta);
    }

    protected function drawRoundBorder(Round $round, Coordinate $origin, int $roundNumberHeight): void
    {
        $width = $this->rangeCalculator->getRoundWidth($round);

        $origin->addX($width-1);
        $this->drawer->drawRectangle($origin, new Coordinate($width, $roundNumberHeight));
    }

    protected function drawPoule(Poule $poule, Coordinate $origin): Coordinate
    {
        $pouleWidth = $this->rangeCalculator->getPouleWidth($poule);
        $pouleName = $this->nameService->getPouleName($poule, false);
        $nextPouleCoordrinate = $this->drawer->drawCellToRight($origin, $pouleName, $pouleWidth, Drawer::ALIGN_CENTER);

        $this->drawer->drawLineToRight($origin->addY(1), $pouleWidth);

        $placeCoordinate = $origin->addY(2);
        foreach ($poule->getPlaces() as $place) {
            $placeName = $this->nameService->getPlaceFromName($place, false);
            $this->drawer->drawCellToRight($placeCoordinate, $placeName, $pouleWidth, Drawer::ALIGN_CENTER);
            $placeCoordinate = $placeCoordinate->incrementY();
        }

        return $nextPouleCoordrinate->addX(RangeCalculator::PADDING + 1);
    }

    protected function drawHorPoules(Round $round, Coordinate $borderOrigin): Coordinate | null
    {
        if ($round->getHorizontalPoules(QualifyTarget::WINNERS)->count() === 0
            && $round->getHorizontalPoules(QualifyTarget::LOSERS)->count() === 0) {
            return null;
        }

        $this->drawer->drawVertLineAwayFromOrigin($borderOrigin, 2 + $round->getHorizontalPoules(QualifyTarget::WINNERS)->count());
        $origin = $borderOrigin->addX(2);
        $this->drawer->drawToRight($origin, QualifyTarget::WINNERS . ' ' . QualifyTarget::LOSERS);
        $seperator = $origin->incrementY();
        $this->drawer->drawToRight($seperator, '- -');

        // winners
        $horWinnersPoules = $this->getHorPoulesAsString($round, QualifyTarget::WINNERS);
        $horPoulesOrigin = $seperator->incrementY();
        $this->drawer->drawVertAwayFromOrigin($horPoulesOrigin, $horWinnersPoules);

        // losers
        $horLosersPoules = $this->getHorPoulesAsString($round, QualifyTarget::LOSERS);
        $losersHorPoulesOrigin = $horPoulesOrigin->add(
            RangeCalculator::PADDING + 1,
            $round->getHorizontalPoules(QualifyTarget::LOSERS)->count() - 1
        );
        $this->drawer->drawVertToOrigin($losersHorPoulesOrigin, $horLosersPoules);
        return $origin->incrementX();
    }

    protected function getHorPoulesAsString(Round $round, string $qualifyTarget): string
    {
        $value = '';
        foreach ($round->getHorizontalPoules($qualifyTarget) as $horPoule) {
            $value .= $horPoule->getNumber();
        }
        return $value;
    }

    protected function drawQualifyRules(Round $round, Coordinate $origin): void
    {
        $seperator = $origin->incrementY();
        $currentCoordinate = $this->drawer->drawVertAwayFromOrigin($seperator, '-')->incrementY();
        $winnersMultipleRuleCoordinate = null;
        // winners
        foreach ($round->getTargetQualifyGroups(QualifyTarget::WINNERS) as $qualifyGroup) {
            $winnersColor = $this->getQualifyGroupColor($qualifyGroup);
            $singleRule = $qualifyGroup->getFirstSingleRule();
            while ($singleRule !== null) {
                $currentCoordinate = $this->drawer->drawVertAwayFromOrigin(
                    $currentCoordinate,
                    $this->getQualifyRuleString($singleRule),
                    $winnersColor
                )->incrementY();
                $singleRule = $singleRule->getNext();
            }
            $multipleRule = $qualifyGroup->getMultipleRule();
            if ($multipleRule !== null) {
                $winnersMultipleRuleCoordinate = $currentCoordinate;
                $this->drawer->drawVertAwayFromOrigin(
                    $currentCoordinate,
                    $this->getQualifyRuleString($multipleRule),
                    $winnersColor
                );
            }
        }
        $currentCoordinate = $seperator->addY($round->getFirstPoule()->getPlaces()->count());

        // losers
        foreach ($round->getTargetQualifyGroups(QualifyTarget::LOSERS) as $qualifyGroup) {
            $losersColor = $this->getQualifyGroupColor($qualifyGroup);
            $singleRule = $qualifyGroup->getFirstSingleRule();
            while ($singleRule !== null) {
                $currentCoordinate = $this->drawer->drawVertToOrigin(
                    $currentCoordinate,
                    $this->getQualifyRuleString($singleRule),
                    $losersColor
                )->decrementY();
                $singleRule = $singleRule->getNext();
            }
            $multipleRule = $qualifyGroup->getMultipleRule();
            if ($multipleRule !== null) {
                $color = $losersColor;
                if ($winnersMultipleRuleCoordinate !== null
                    && $winnersMultipleRuleCoordinate->getX() === $currentCoordinate->getX()) {
                    $color = 3; // 'Magenta';
                }
                $this->drawer->drawVertAwayFromOrigin(
                    $currentCoordinate,
                    $this->getQualifyRuleString($multipleRule),
                    $color
                );
            }
        }
    }

    protected function getQualifyRuleString(MultipleQualifyRule | SingleQualifyRule $qualifyRule): string {
        return ($qualifyRule instanceof MultipleQualifyRule) ? 'M' : 'S';
    }


    protected function drawQualifyGroups(Round $round, Coordinate $origin, int $nextRoundNumberHeight): void
    {
        $qualifyGroupCoordinate = $this->getQualifyGroupsStartCoordinate($origin, $round);
        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $qualifyGroupCoordinate = $this->drawQualifyGroup($qualifyGroup, $qualifyGroupCoordinate, $nextRoundNumberHeight);
        }
    }

    protected function getQualifyGroupsStartCoordinate(Coordinate $origin, Round $parentRound): Coordinate
    {
        $parentRoundWidth = $this->rangeCalculator->getRoundWidth($parentRound);
        $qualifyGroupsWidth = $this->rangeCalculator->getQualifyGroupsWidth($parentRound);
        $delta = (int)floor(($parentRoundWidth - $qualifyGroupsWidth) / 2);
        return $origin->addX($delta);
    }

    protected function drawQualifyGroup(QualifyGroup $qualifyGroup, Coordinate $origin, int $nextRoundNumberHeight): Coordinate
    {
        $roundWidth = $this->rangeCalculator->getRoundWidth($qualifyGroup->getChildRound());

        $selfCoordinate = $origin;
        $this->drawer->drawCellToRight($selfCoordinate, '|', $roundWidth, Drawer::ALIGN_CENTER);
        $selfCoordinate = $selfCoordinate->incrementY();
        $qualifyGroupName = $qualifyGroup->getTarget();
        $color = $this->getQualifyGroupColor($qualifyGroup);
        $qualifyGroupName .= $qualifyGroup->getNumber();
        $this->drawer->drawCellToRight($selfCoordinate, $qualifyGroupName, $roundWidth, Drawer::ALIGN_CENTER, $color);
        $this->drawer->drawCellToRight($selfCoordinate->incrementY(), '|', $roundWidth, Drawer::ALIGN_CENTER);

        $childRoundCoordinate = $origin->addY(RangeCalculator::QUALIFYGROUPHEIGHT);
        $this->drawRound($qualifyGroup->getChildRound(), $childRoundCoordinate, $nextRoundNumberHeight);

        return $origin->addX($roundWidth + RangeCalculator::PADDING);
    }

    public function getQualifyGroupColor(QualifyGroup $qualifyGroup): int
    {
        if ($qualifyGroup->getTarget() === QualifyTarget::WINNERS) {
            switch ($qualifyGroup->getNumber()) {
                case 1:
                    return 2; // '#298F00';
                case 2:
                    return 2; // '#84CF96';
                case 3:
                    return 2; // '#0588BC';
                case 4:
                    return 2; // '#00578A';
            }
            return 0; // '#FFFFFF';
        }
        switch ($qualifyGroup->getNumber()) {
            case 1:
                return 1; // '#FFFF66';
            case 2:
                return 1; // '#FFCC00';
            case 3:
                return 1; // '#FF9900';
            case 4:
                return 1; // '#FF0000';
        }
        return 0; // '#FFFFFF';
    }
}
