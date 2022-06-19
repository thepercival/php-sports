<?php

declare(strict_types=1);

namespace Sports\Structure;

use Exception;
use NumberFormatter;
use Sports\Competitor\StartLocationMap;
use Sports\Game\Place as GamePlace;
use Sports\Place;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Target;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Qualify\RoundRank\Service as RoundRankService;
use Sports\Round;
use Sports\Round\Number as RoundNumber;

class NameService
{
    protected PouleStructureNumberMap|null $pouleStructureNumberMap = null;
    protected RoundRankService $roundRankService;

    public function __construct(protected StartLocationMap|null $startLocationMap = null)
    {
        $this->roundRankService = new RoundRankService();
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
            $rounds = $roundNumber->getRounds();
            $firstRound = reset($rounds);
            if ($firstRound !== false) {
                return $this->getRoundName($firstRound, true);
            }
        }
        return $this->getOrdinal($roundNumber->getNumber()) . ' ronde';
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
            $rank = $this->roundRankService->getRank($round) + 1;
            return $this->getOrdinal($rank) . '/' . $this->getOrdinal($rank + 1) . ' plaats';
        }
        return 'finale';
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

    private function getOrdinal(int $number): string
    {
        $locale = 'nl_NL';
        $nf = new NumberFormatter($locale, NumberFormatter::ORDINAL);
        $output = $nf->format($number);
        return $output === false ? '' : $output;
    }

    protected function getFractalNumber(int $number): string
    {
        if ($number === 2) {
            return 'halve';
        } elseif ($number === 4) {
            return 'kwart';
        }
        return '1/' . $number;
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

    /*public function getRefereeName(Game $game, bool $longName = null): string
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
    }*/

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

    public function getPlaceFromName(Place $place, bool $competitorName, bool $longName = false): string
    {
        $startLocation = $place->getStartLocation();
        if ($competitorName && $this->startLocationMap !== null && $startLocation !== null) {
            $competitor = $this->startLocationMap->getCompetitor($startLocation);
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

    public function getPlaceName(Place $place, bool $competitorName = false, ?bool $longName = false): string
    {
        $startLocation = $place->getStartLocation();
        if ($competitorName && $this->startLocationMap !== null && $startLocation !== null) {
            $competitor = $this->startLocationMap->getCompetitor($startLocation);
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

    private function getPouleStructureNumberMap(Round $round): PouleStructureNumberMap
    {
        if ($this->pouleStructureNumberMap === null) {
            $this->pouleStructureNumberMap = new PouleStructureNumberMap(
                $round->getNumber()->getFirst(),
                $this->roundRankService
            );
        }
        return $this->pouleStructureNumberMap;
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
}
