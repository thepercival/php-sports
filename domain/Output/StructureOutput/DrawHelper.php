<?php

declare(strict_types=1);

namespace Sports\Output\StructureOutput;

use Sports\Category;
use Sports\Structure\NameService as StructureNameService;
use Sports\Output\Coordinate;
use Sports\Output\Grid\Align;
use Sports\Output\Grid\Drawer;
use Sports\Output\Direction\Horizontal as HorizontalDirection;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Structure;
use SportsHelpers\Output\Color;

final class DrawHelper
{
    protected StructureNameService $structureNameService;
    protected RangeCalculator $rangeCalculator;
    public const HorizontalPouleWidth = 2;

    public function __construct(protected Drawer $drawer)
    {
        $this->rangeCalculator = new RangeCalculator();
        $this->structureNameService = new StructureNameService();
    }

    public function drawStructure(Structure $structure, Coordinate $coordinate): void
    {
        $this->structureNameService = new StructureNameService();

        // $coordinate = $this->getCategoryStartCoordinate($origin, $structure->getFirstRoundNumber(), $structure);
        foreach ($structure->getCategories() as $category) {
            $coordinate = $this->drawCategory($category, $coordinate)->addX(RangeCalculator::PADDING);
        }
    }

//    protected function getCategoryStartCoordinate(
//        Coordinate $origin,
//        RoundNumber $roundNumber,
//        Structure $structure
//    ): Coordinate {
//        $structureWidth = $this->rangeCalculator->getStructureWidth($structure);
//        $roundNumberWidth = $this->rangeCalculator->getRoundNumberWidth($roundNumber);
//        $delta = (int)floor(($structureWidth - $roundNumberWidth) / 2);
//        return $origin->addX($delta);
//    }



    protected function drawCategory(Category $category, Coordinate $coordinate): Coordinate
    {
        $title = $this->rangeCalculator->getCategoryTitle($category);
        $width = $this->rangeCalculator->getCategoryWidth($category);
        $height = $this->rangeCalculator->getCategoryHeight($category);
        $this->drawer->drawRectangle($coordinate, new Coordinate($width,$height), Color::Cyan);

        $middle = (int)($width/2);
        $titleHalfLength = (int)(mb_strlen($title)/2);
        $startCoord = $coordinate->addX( $middle - $titleHalfLength );
        $this->drawer->drawToRight($startCoord, $title, Color::Cyan);

        $this->drawRound($category->getRootRound(), $coordinate->add(1, 1));
        return new Coordinate( $coordinate->addX($width)->getX(), $coordinate->getY() );
    }

    protected function drawRound(
        Round $round,
        Coordinate $origin,
    ): void {
        $roundNumberHeight = $this->rangeCalculator->getRoundNumberHeight($round->getStructureCell()->getRoundNumber());
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
            // $nextRoundNumberHeight = $this->rangeCalculator->getRoundNumberHeight($round->getNumber());
            $this->drawQualifyGroups($round, $origin->addY($roundNumberHeight));
        }
//        $roundWidth = $this->rangeCalculator->getRoundWidth($round);
//        return $origin->addX($roundWidth + RangeCalculator::PADDING);


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
        $pouleName = $this->structureNameService->getPouleName($poule, false);
        $nextPouleCoordrinate = $this->drawer->drawCellToRight($origin, $pouleName, $pouleWidth, Align::Center);

        $this->drawer->drawLineToRight($origin->addY(1), $pouleWidth);

        $placeCoordinate = $origin->addY(2);
        foreach ($poule->getPlaces() as $place) {
            $placeName = $this->structureNameService->getPlaceFromName($place, false);
            $this->drawer->drawCellToRight($placeCoordinate, $placeName, $pouleWidth, Align::Center);
            $placeCoordinate = $placeCoordinate->incrementY();
        }

        return $nextPouleCoordrinate->addX(RangeCalculator::PADDING + 1);
    }

    protected function drawHorPoules(Round $round, Coordinate $borderOrigin): Coordinate | null
    {
        if ($round->getHorizontalPoules(QualifyTarget::Winners)->count() === 0
            && $round->getHorizontalPoules(QualifyTarget::Losers)->count() === 0) {
            return null;
        }

        $this->drawer->drawVertLineAwayFromOrigin($borderOrigin, self::HorizontalPouleWidth + $round->getHorizontalPoules(QualifyTarget::Winners)->count());
        $origin = $borderOrigin->addX(self::HorizontalPouleWidth);
        $this->drawer->drawToRight($origin, QualifyTarget::Winners->value . ' ' . QualifyTarget::Losers->value);
        $seperator = $origin->incrementY();
        $this->drawer->drawToRight($seperator, '- -');

        // winners
        $horWinnersPoules = $this->getHorPoulesAsArray($round, QualifyTarget::Winners);
        $horPoulesOrigin = $seperator->incrementY()->addX( self::HorizontalPouleWidth - 1 );
        $this->drawer->drawVertArrayAwayFromOrigin($horPoulesOrigin, $horWinnersPoules, null, HorizontalDirection::Right);

        // losers
        $horLosersPoules = $this->getHorPoulesAsArray($round, QualifyTarget::Losers);
        $losersHorPoulesOrigin = $horPoulesOrigin->add(
            RangeCalculator::PADDING + 1,
            $round->getHorizontalPoules(QualifyTarget::Losers)->count() - 1
        )->addX( self::HorizontalPouleWidth - 1 );;
        $this->drawer->drawVertArrayToOrigin($losersHorPoulesOrigin, $horLosersPoules, null, HorizontalDirection::Right);
        return $origin->incrementX();
    }

    /**
     * @param Round $round
     * @param QualifyTarget $qualifyTarget
     * @return list<string>
     */
    protected function getHorPoulesAsArray(Round $round, QualifyTarget $qualifyTarget): array
    {
        return array_values( array_map( function(HorizontalPoule $horizontalPoule): string {
            return '' . $horizontalPoule->getNumber();
        }, $round->getHorizontalPoules($qualifyTarget)->toArray() ) );
    }

    protected function drawQualifyRules(Round $round, Coordinate $origin): void
    {
        $seperator = $origin->incrementY();
        $currentCoordinate = $this->drawer->drawVertStringAwayFromOrigin($seperator, '-')->incrementY();
        $winnersMultipleRuleCoordinate = null;
        // winners
        foreach ($round->getTargetQualifyGroups(QualifyTarget::Winners) as $qualifyGroup) {
            $winnersColor = $this->getQualifyGroupColor($qualifyGroup);
            $singleRule = $qualifyGroup->getFirstSingleRule();
            while ($singleRule !== null) {
                $currentCoordinate = $this->drawer->drawVertStringAwayFromOrigin(
                    $currentCoordinate,
                    $this->getQualifyRuleString($singleRule),
                    $winnersColor
                )->incrementY();
                $singleRule = $singleRule->getNext();
            }
            $multipleRule = $qualifyGroup->getMultipleRule();
            if ($multipleRule !== null) {
                $winnersMultipleRuleCoordinate = $currentCoordinate;
                $this->drawer->drawVertStringAwayFromOrigin(
                    $currentCoordinate,
                    $this->getQualifyRuleString($multipleRule),
                    $winnersColor
                );
            }
        }
        $currentCoordinate = $seperator->addY($round->getFirstPoule()->getPlaces()->count());

        // losers
        foreach ($round->getTargetQualifyGroups(QualifyTarget::Losers) as $qualifyGroup) {
            $losersColor = $this->getQualifyGroupColor($qualifyGroup);
            $singleRule = $qualifyGroup->getFirstSingleRule();
            while ($singleRule !== null) {
                $this->drawer->drawVertStringToOrigin(
                    $currentCoordinate,
                    $this->getQualifyRuleString($singleRule),
                    $losersColor
                );
                $currentCoordinate = $currentCoordinate->decrementY();
                $singleRule = $singleRule->getNext();
            }
            $multipleRule = $qualifyGroup->getMultipleRule();
            if ($multipleRule !== null) {
                $color = $losersColor;
                if ($winnersMultipleRuleCoordinate !== null
                    && $winnersMultipleRuleCoordinate->getX() === $currentCoordinate->getX()) {
                    $color = Color::Blue;
                }
                $this->drawer->drawVertStringAwayFromOrigin(
                    $currentCoordinate,
                    $this->getQualifyRuleString($multipleRule),
                    $color
                );
            }
        }
    }

    protected function getQualifyRuleString(MultipleQualifyRule | SingleQualifyRule $qualifyRule): string
    {
        return ($qualifyRule instanceof MultipleQualifyRule) ? 'M' : 'S';
    }


    protected function drawQualifyGroups(Round $round, Coordinate $origin): void
    {
        $qualifyGroupCoordinate = $this->getQualifyGroupsStartCoordinate($origin, $round);
        foreach ($round->getQualifyGroupsLosersReversed() as $qualifyGroup) {
            $qualifyGroupCoordinate = $this->drawQualifyGroup(
                $qualifyGroup,
                $qualifyGroupCoordinate,
            );
        }
    }

    protected function getQualifyGroupsStartCoordinate(Coordinate $origin, Round $parentRound): Coordinate
    {
        $parentRoundWidth = $this->rangeCalculator->getRoundWidth($parentRound);
        $qualifyGroupsWidth = $this->rangeCalculator->getQualifyGroupsWidth($parentRound);
        $delta = (int)floor(($parentRoundWidth - $qualifyGroupsWidth) / 2);
        return $origin->addX($delta);
    }

    protected function drawQualifyGroup(QualifyGroup $qualifyGroup, Coordinate $origin): Coordinate
    {
        $roundWidth = $this->rangeCalculator->getRoundWidth($qualifyGroup->getChildRound());

        $selfCoordinate = $origin;
        $this->drawer->drawCellToRight($selfCoordinate, '|', $roundWidth, Align::Center);
        $selfCoordinate = $selfCoordinate->incrementY();
        $qualifyGroupName = $qualifyGroup->getTarget()->value;
        $color = $this->getQualifyGroupColor($qualifyGroup);
        $qualifyGroupName .= $qualifyGroup->getNumber();
        $this->drawer->drawCellToRight($selfCoordinate, $qualifyGroupName, $roundWidth, Align::Center, $color);
        $this->drawer->drawCellToRight($selfCoordinate->incrementY(), '|', $roundWidth, Align::Center);

        $childRoundCoordinate = $origin->addY(RangeCalculator::QUALIFYGROUPHEIGHT);
        $this->drawRound($qualifyGroup->getChildRound(), $childRoundCoordinate);

        return $origin->addX($roundWidth + RangeCalculator::PADDING);
    }

    public function getQualifyGroupColor(QualifyGroup $qualifyGroup): Color
    {
        if ($qualifyGroup->getTarget() === QualifyTarget::Winners) {
            switch ($qualifyGroup->getNumber()) {
                case 1:
                    return Color::Green; // '#298F00';
                case 2:
                    return Color::Green; // '#84CF96';
                case 3:
                    return Color::Green; // '#0588BC';
                case 4:
                    return Color::Green; // '#00578A';
            }
        } else {
            if ($qualifyGroup->getTarget() === QualifyTarget::Losers) {
                switch ($qualifyGroup->getNumber()) {
                    case 1:
                        return Color::Red; // '#FFFF66';
                    case 2:
                        return Color::Red; // '#FFCC00';
                    case 3:
                        return Color::Red; // '#FF9900';
                    case 4:
                        return Color::Red; // '#FF0000';
                }
            }
        }
        return Color::White; // '#FFFFFF';
    }
}
