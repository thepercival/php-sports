<?php

declare(strict_types=1);

namespace Sports\Output\StructureOutput;

use Sports\Category;
use Sports\Game\GameState;
use Sports\Game\GameState as GameState;
use Sports\Place;
use Sports\Qualify\QualifyTarget as QualifyTarget;
use Sports\Ranking\Calculator\Cumulative;
use Sports\Structure\NameService as StructureNameService;
use Sports\Output\Coordinate;
use Sports\Output\Grid\Align;
use Sports\Output\Grid\Drawer;
use Sports\Output\Direction\Horizontal as HorizontalDirection;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Rule\Horizontal\MultipleHorizontalQualifyRule as HorizontalMultipleQualifyRule;
use Sports\Qualify\Rule\Horizontal\SingleHorizontalQualifyRule as HorizontalSingleQualifyRule;
use Sports\Qualify\Rule\Vertical\MultipleVerticalQualifyRule as VerticalMultipleQualifyRule;
use Sports\Qualify\Rule\Vertical\SingleVerticalQualifyRule as VerticalSingleQualifyRule;
use Sports\Ranking\Calculator\RoundRankingCalculator as RoundRankingCalculator;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Qualify\QualifyDistribution as QualifyDistribution;
use Sports\Structure;
use SportsHelpers\Output\Color;

final class DrawHelper
{
    protected StructureNameService $structureNameService;
    protected RangeCalculator $rangeCalculator;
    public const int HorizontalPouleWidth = 2;

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
            $color = $this->calculateColor($place);
            $placeName = $this->structureNameService->getPlaceFromName($place, false);
            $this->drawer->drawCellToRight($placeCoordinate, $placeName, $pouleWidth, Align::Center, $color);
            $placeCoordinate = $placeCoordinate->incrementY();
        }

        return $nextPouleCoordrinate->addX(RangeCalculator::PADDING + 1);
    }

    protected function calculateColor(Place $place): Color {
        foreach( [QualifyTarget::Winners, QualifyTarget::Losers] as $qualifyTarget ) {
            $horizontalPoule = $place->getHorizontalPoule($qualifyTarget);

            $multipleQualifyRule = $horizontalPoule->getQualifyRuleNew();
            if( ( $multipleQualifyRule instanceof HorizontalMultipleQualifyRule
                    || $multipleQualifyRule instanceof VerticalMultipleQualifyRule)
                && $this->placeQualifiesForMultipleRule($multipleQualifyRule, $place) ) {
                return QualifyTarget::Winners === $qualifyTarget ? Color::Green : Color::Red;
            }
        }
        return Color::White;
    }

    protected function placeQualifiesForMultipleRule(
        VerticalMultipleQualifyRule|HorizontalMultipleQualifyRule $multipleQualifyRule,
        Place $place
    ): bool
    {
        if ($place->getRound()->getGamesState() !== GameState::Finished) {
            return false;
        }
//        $rankingCalculator = new RoundRankingCalculator(null, Cumulative::ByPerformance);
//        $horizontalPoule = $place->getHorizontalPoule($multipleQualifyRule->getQualifyTarget());
//        $horizontalPouleRankedItems = $rankingCalculator->getItemsForHorizontalPoule($horizontalPoule);

        // $rankingCalculatorEnd = new RoundRankingCalculator(null, Cumulative::ByPerformance );
//        $rankingCalculatorEnd->
//if( $place->getPlaceNr() === 2 and $place->getPouleNr() === 1 ) {
//    $eer = 12;
//}
        foreach( $multipleQualifyRule->getToPlaces() as $toPlace ) {
            if( $toPlace->getQualifiedPlace() === $place ) {
                return true;
            }
        }
        return false;

//        $nrOfToPlaces = $multipleQualifyRule->getNrOfToPlaces();
//        $horizontalPouleRankedItem = array_shift($horizontalPouleRankedItems);
//        while ( $nrOfToPlaces-- > 0 && $horizontalPouleRankedItem !== null ) {
//            if( $horizontalPouleRankedItem->getPlace() === $place ) {
//                return true;
//            }
//            $horizontalPouleRankedItem = array_shift($horizontalPouleRankedItems);
//        }
//        return false;
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
        $horWinnersPoules = $this->getHorPoulesAsArray($round, QualifyTarget::Winners, true);
        $horPoulesOrigin = $seperator->incrementY(); // ->addX( self::HorizontalPouleWidth - 1 );
        $this->drawer->drawVertArrayAwayFromOrigin($horPoulesOrigin, $horWinnersPoules, null, HorizontalDirection::Right);

        // losers
        $horLosersPoules = $this->getHorPoulesAsArray($round, QualifyTarget::Losers, true);
        $losersHorPoulesOrigin = $horPoulesOrigin->add(
            RangeCalculator::PADDING + 1,
            $round->getHorizontalPoules(QualifyTarget::Losers)->count() - 1
        )->addX( self::HorizontalPouleWidth - 1 );
        $this->drawer->drawVertArrayToOrigin($losersHorPoulesOrigin, $horLosersPoules, null, HorizontalDirection::Right);
        return $origin->incrementX();
    }

    /**
     * @param Round $round
     * @param QualifyTarget $qualifyTarget
     * @param bool $reversed
     * @return list<string>
     */
    protected function getHorPoulesAsArray(Round $round, QualifyTarget $qualifyTarget, bool $reversed = false): array
    {
        return array_values( array_map( function(HorizontalPoule $horizontalPoule) use ($reversed): string {
            return $reversed ? strrev('' . $horizontalPoule->getNumber()) : '' .$horizontalPoule->getNumber();
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
//            if( $qualifyGroup->getDistribution() === QualifyDistribution::HorizontalSnake) {
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
                        && $winnersMultipleRuleCoordinate->getX() === $currentCoordinate->getX()
                        && $winnersMultipleRuleCoordinate->getY() === $currentCoordinate->getY()) {
                        $color = Color::Blue;
                    }
                    $this->drawer->drawVertStringAwayFromOrigin(
                        $currentCoordinate,
                        $this->getQualifyRuleString($multipleRule),
                        $color
                    );
                }
//            } else { // QualifyDistribution::Vertical
//                $verticalRule = $qualifyGroup->getFirstVerticalRule();
//                while ($verticalRule !== null) {
//                    $this->drawer->drawVertStringToOrigin(
//                            $currentCoordinate, $this->getQualifyRuleString($verticalRule), $losersColor
//                        );
//                    $currentCoordinate = $currentCoordinate->decrementY();
//                    $verticalRule = $verticalRule->getNext();
//                }
//            }

        }
    }

    protected function getQualifyRuleString(HorizontalMultipleQualifyRule | HorizontalSingleQualifyRule | VerticalMultipleQualifyRule | VerticalSingleQualifyRule $qualifyRule): string
    {
        if( $qualifyRule instanceof HorizontalMultipleQualifyRule ) {
            return 'M';
        } else if ( $qualifyRule instanceof VerticalMultipleQualifyRule ) {
            return 'M';
        }
        return 'S';
    }

    protected function getDistribution(QualifyDistribution $distribution): string {
        return $distribution == QualifyDistribution::HorizontalSnake ? ' (HS)' : ' (V)';
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

        $distributionDescr = $this->getDistribution($qualifyGroup->getDistribution());
        $qualifyGroupName = $qualifyGroup->getTarget()->value . $qualifyGroup->getNumber() . $distributionDescr;
        $color = $this->getQualifyGroupColor($qualifyGroup);
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
                    return Color::Cyan; // '#84CF96';
                case 3:
                    return Color::Cyan; // '#0588BC';
                case 4:
                    return Color::Cyan; // '#00578A';
            }
        } else {
            if ($qualifyGroup->getTarget() === QualifyTarget::Losers) {
                switch ($qualifyGroup->getNumber()) {
                    case 1:
                        return Color::Red; // '#FFFF66';
                    case 2:
                        return Color::Magenta; // '#FFCC00';
                    case 3:
                        return Color::Magenta; // '#FF9900';
                    case 4:
                        return Color::Magenta; // '#FF0000';
                }
            }
        }
        return Color::White; // '#FFFFFF';
    }
}
