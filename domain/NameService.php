<?php

declare(strict_types=1);

namespace Sports;

use Exception;
use NumberFormatter;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Game\Place as GamePlace;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Target;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Ranking\AgainstRuleSet;
use Sports\Ranking\Map\PouleStructureNumber as PouleStructureNumberMap;
use Sports\Ranking\Map\PreviousNrOfDropouts as PreviousNrOfDropoutsMap;
use Sports\Ranking\Rule as RankingRule;
use Sports\Ranking\Rule\Getter as RankingRuleGetter;
use Sports\Round\Number as RoundNumber;

class NameService
{
    protected PouleStructureNumberMap|null $pouleStructureNumberMap = null;
    protected PreviousNrOfDropoutsMap|null $previousNrOfDropoutsMap = null;

    public function __construct(
        protected CompetitorMap|null $competitorMap = null
    ) {
    }

    public function getQualifyTargetDescription(QualifyTarget $qualifyTarget, bool $multiple = false): string
    {
        $description = $qualifyTarget === QualifyTarget::Winners ? 'winnaar' : ($qualifyTarget === QualifyTarget::Losers ? 'verliezer' : '');
        return (($multiple && ($description !== '')) ? $description . 's' : $description);
    }

    /**
     *  als allemaal dezelfde naam dan geef die naam
     * als verschillde namen geef dan xde ronde met tooltip van de namen
     */
    public function getRoundNumberName(RoundNumber $roundNumber): string
    {
        if ($this->roundsHaveSameName($roundNumber)) {
            $firstRound = $roundNumber->getRounds()->first();
            if ($firstRound !== false) {
                return $this->getRoundName($firstRound, true);
            }
        }
        return $this->getOrdinal($roundNumber->getNumber()) . ' ronde';
    }

    public function getRoundName(Round $round, bool $sameName = false): string
    {
        if ($this->roundAndParentsNeedsRanking($round) || !$this->childRoundsHaveEqualDepth($round)) {
            return $this->getOrdinal($round->getNumberAsValue()) . ' ronde';
        }

        $nrOfRoundsToGo = $this->getMaxDepth($round);
        if ($nrOfRoundsToGo >= 1) {
            return $this->getFractalNumber((int)pow(2, $nrOfRoundsToGo)) . ' finale';
        }
        // if (round.getNrOfPlaces() > 1) {
        if ($round->getNrOfPlaces() === 2 && $sameName === false) {
            $rank = $this->getPreviousNrOfDropoutsMap($round)->get($round) + 1;
            return $this->getOrdinal($rank) . '/' . $this->getOrdinal($rank + 1) . ' plaats';
        }
        return 'finale';
    }

    public function getPouleName(Poule $poule, bool $withPrefix): string
    {
        $pouleName = '';
        if ($withPrefix === true) {
            $pouleName = $poule->needsRanking() ? 'poule ' : 'wed. ';
        }
        $pouleStructureNumber = $this->getPouleStructureNumberMap($poule->getRound())->get($poule) - 1;
        $secondLetter = $pouleStructureNumber % 26;
        if ($pouleStructureNumber >= 26) {
            $firstLetter = (int)(($pouleStructureNumber - $secondLetter) / 26);
            $pouleName .= (chr(ord('A') + ($firstLetter - 1)));
        }
        return $pouleName . (chr(ord('A') + $secondLetter));
    }

    public function getPlaceName(Place $place, bool $competitorName = false, ?bool $longName = false): string
    {
        $startLocation = $place->getStartLocation();
        if ($competitorName && $this->competitorMap !== null && $startLocation !== null) {
            $competitor = $this->competitorMap->getCompetitor($startLocation);
            if ($competitor !== null) {
                return $competitor->getName();
            }
        }
        if ($longName === true) {
            return 'nr. ' . $place->getPlaceNr() . ' ' . $this->getPouleName($place->getPoule(), true);
        }
        $name = $this->getPouleName($place->getPoule(), false);
        return $name . $place->getPlaceNr();
    }

    public function getPlaceFromName(Place $place, bool $competitorName, bool $longName = false): string
    {
        $startLocation = $place->getStartLocation();
        if ($competitorName && $this->competitorMap !== null && $startLocation !== null) {
            $competitor = $this->competitorMap->getCompetitor($startLocation);
            if ($competitor !== null) {
                return $competitor->getName();
            }
        }

        $fromQualifyRule = null;
        $parentQualifyGroup = $place->getRound()->getParentQualifyGroup();
        if ($parentQualifyGroup !== null) {
            try {
                $fromQualifyRule = $parentQualifyGroup->getRule($place);
            } catch (Exception $exception) {
            }
        }
        if ($fromQualifyRule === null) {
            return $this->getPlaceName($place, false, $longName);
        }
        $balanced = $place->getRound()->createPouleStructure()->isBalanced();
        $absolute = !$longName || $fromQualifyRule->getQualifyTarget() === QualifyTarget::Winners || $balanced;
        if ($fromQualifyRule instanceof MultipleQualifyRule) {
            return $this->getMultipleQualifyRuleName($fromQualifyRule, $place, $longName, $absolute);
        }
        // SingleQualifyRule
        $fromPlace = $fromQualifyRule->getFromPlace($place);
        $rank = $fromPlace->getPlaceNr();

        $poule = $fromPlace->getPoule();
        $pouleName = $this->getPouleName($poule, $longName);
        $ordinal = $this->getOrdinal($rank) . (!$poule->needsRanking() ? ' pl.' : '');
        return $longName ? $ordinal . ' ' . $pouleName : $pouleName . $rank;
    }

    public function getQualifyRuleName(SingleQualifyRule|MultipleQualifyRule $rule): string
    {
        $balanced = $rule->getFromRound()->createPouleStructure()->isBalanced();
        $absolute = $rule->getQualifyTarget() === QualifyTarget::Winners || $balanced;

        $fromHorPoule = $rule->getFromHorizontalPoule();
        $fromNumber = $absolute ? $fromHorPoule->getPlaceNumber() : $fromHorPoule->getNumber();

        $name = $this->getOrdinal($fromNumber);
        if ($rule->getQualifyTarget() === QualifyTarget::Losers && !$absolute) {
            return $name . ' pl. van onderen';
        }
        return $name . ' plekken';
    }

    public function getMultipleQualifyRuleName(
        MultipleQualifyRule $rule,
        Place $place,
        bool $longName,
        bool $absolute
    ): string {
        $fromHorPoule = $rule->getFromHorizontalPoule();
        $fromNumber = $absolute ? $fromHorPoule->getPlaceNumber() : $fromHorPoule->getNumber();

        $nrOfToPlaces = $rule->getNrOfToPlaces();
        if ($rule->getQualifyTarget() === QualifyTarget::Winners) {
            $toPlaceNumber = $rule->getToPlaceNumber($place);
        } else {
            $toPlaceNumber = count($fromHorPoule->getPlaces()) - ($nrOfToPlaces - $rule->getToPlaceNumber($place));
        }

        $ordinal = $this->getOrdinal($toPlaceNumber);
        if (!$longName) {
            return $ordinal . $fromNumber;
        }

        $firstpart = $ordinal . ' van';
//        if ($nrOfToPlaces === 1) {
//            $nrOfHorPlaces = count($rule->getFromHorizontalPoule()->getPlaces());
//            $rank = ($rule->getQualifyTarget() === QualifyTarget::Winners) ? 1 : $nrOfHorPlaces;
//            $firstpart = $this->getOrdinal($rank) . ' van';
//        }
        $name = $firstpart . ' ' . $this->getOrdinal($fromNumber);
        if ($rule->getQualifyTarget() === QualifyTarget::Losers && !$absolute) {
            $name .= ' pl. van onderen';
        } else {
            $name .= ' plekken';
        }
        return $name;
    }

    private function getOrdinal(int $number): string
    {
        $locale = 'nl_NL';
        $nf = new NumberFormatter($locale, NumberFormatter::ORDINAL);
        $output = $nf->format($number);
        return $output === false ? '' : $output;
    }

    /**
     * @param array<GamePlace> $gamePlaces
     * @param bool $competitorName
     * @param bool $longName
     * @return string
     */
    public function getPlacesFromName(array $gamePlaces, bool $competitorName, bool $longName): string
    {
        return implode(
            ' & ',
            array_map(function ($gamePlace) use ($competitorName, $longName): string {
                return $this->getPlaceFromName($gamePlace->getPlace(), $competitorName, $longName);
            }, $gamePlaces)
        );
    }

    public function getRefereeName(Game $game, bool $longName = null): string
    {
        $referee = $game->getReferee();
        if ($referee !== null) {
            if ($longName !== true) {
                return $referee->getInitials();
            }
            $refereeName = $referee->getName();
            return $refereeName !== null ? $refereeName : '';
        }
        $refereePlace = $game->getRefereePlace();
        if ($refereePlace !== null) {
            return $this->getPlaceName($refereePlace, true, $longName);
        }
        return '';
    }

    /**
     * @param AgainstRuleSet $ruleSet
     * @return list<string>
     */
    public function getRulesName(AgainstRuleSet $ruleSet): array
    {
        $rankingRuleGetter = new RankingRuleGetter();
        return array_map(function (RankingRule $rule): string {
            switch ($rule) {
                case RankingRule::MostPoints:
                    return 'meeste aantal punten';
                case RankingRule::FewestGames:
                    return 'minste aantal wedstrijden';
                case RankingRule::BestUnitDifference:
                    return 'beste saldo';
                case RankingRule::MostUnitsScored:
                    return 'meeste aantal eenheden voor';
                case RankingRule::BestAmongEachOther:
                    return 'beste onderling resultaat';
                case RankingRule::BestSubUnitDifference:
                    return 'beste subsaldo';
                case RankingRule::MostSubUnitsScored:
                    return 'meeste aantal subeenheden voor';
            }
            return '';
        }, $rankingRuleGetter->getRules($ruleSet, false));
    }

    protected function childRoundsHaveEqualDepth(Round $round): bool
    {
        if ($round->getQualifyGroups()->count() === 1) {
            return true;
        }

        $depthAll = null;
        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $qualifyGroupMaxDepth = $this->getMaxDepth($qualifyGroup->getChildRound());
            if ($depthAll === null) {
                $depthAll = $qualifyGroupMaxDepth;
            } else {
                if ($depthAll !== $qualifyGroupMaxDepth) {
                    return false;
                }
            }
        }
        return true;
    }

    private function roundsHaveSameName(RoundNumber $roundNumber): bool
    {
        $roundNameAll = null;
        foreach ($roundNumber->getRounds() as $round) {
            $roundName = $this->getRoundName($round, true);
            if ($roundNameAll === null) {
                $roundNameAll = $roundName;
            }
            if ($roundNameAll !== $roundName) {
                return false;
            }
        }
        return true;
    }

    private function roundAndParentsNeedsRanking(Round $round): bool
    {
        if (!$round->needsRanking()) {
            return false;
        }
        $parent = $round->getParent();
        if ($parent !== null) {
            return $this->roundAndParentsNeedsRanking($parent);
        }
        return true;
    }

    /*private function getHtmlFractalNumber(int $number): string {
        if ($number === 2 || $number === 4) {
            return '&frac1' . $number . ';';
        }
        return '<span style="font-size: 80%"><sup>1</sup>&frasl;<sub>' . $number . '</sub></span>';
    }

    private function getHtmlNumber(int $number): string {
        return $number . '<sup>' . ($number === 1 ? 'st' : 'd') . 'e</sup>';
    }*/

    protected function getFractalNumber(int $number): string
    {
        if ($number === 2) {
            return 'halve';
        } elseif ($number === 4) {
            return 'kwart';
        }
        return '1/' . $number;
    }

//    /**
//     * @return string
//     */
//    protected function getHtmlNumber(int $number): string
//    {
//        if ($number === 1) {
//            return $number . 'ste';
//        }
//        return $number . 'de';
//        // return '&frac1' . $number . ';';
//    }

    private function getMaxDepth(Round $round): int
    {
        $biggestMaxDepth = 0;
        foreach ($round->getChildren() as $child) {
            $maxDepth = 1 + $this->getMaxDepth($child);
            if ($maxDepth > $biggestMaxDepth) {
                $biggestMaxDepth = $maxDepth;
            }
        }
        return $biggestMaxDepth;
    }

    private function getPreviousNrOfDropoutsMap(Round $round): PreviousNrOfDropoutsMap
    {
        if ($this->previousNrOfDropoutsMap === null) {
            $this->previousNrOfDropoutsMap = new PreviousNrOfDropoutsMap($round->getRoot());
        }
        return $this->previousNrOfDropoutsMap;
    }

    private function getPouleStructureNumberMap(Round $round): PouleStructureNumberMap
    {
        if ($this->pouleStructureNumberMap === null) {
            $this->pouleStructureNumberMap = new PouleStructureNumberMap(
                $round->getNumber()->getFirst(),
                $this->getPreviousNrOfDropoutsMap($round)
            );
        }
        return $this->pouleStructureNumberMap;
    }
}
