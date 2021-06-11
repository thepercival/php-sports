<?php
declare(strict_types=1);

namespace Sports;

use Exception;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Round\Number as RoundNumber;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Game\Place as GamePlace;
use Sports\Ranking\Rule\Getter as RankingRuleGetter;
use Sports\Ranking\Rule as RankingRule;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Ranking\Map\PouleStructureNumber as PouleStructureNumberMap;
use Sports\Ranking\Map\PreviousNrOfDropouts as PreviousNrOfDropoutsMap;

class NameService
{
    protected PouleStructureNumberMap|null $pouleStructureNumberMap = null;
    protected PreviousNrOfDropoutsMap|null $previousNrOfDropoutsMap = null;

    public function __construct(
        protected CompetitorMap|null $competitorMap = null
    ) {
    }

    public function getQualifyTargetDescription(string $qualifyTarget, bool $multiple = false): string
    {
        $description = $qualifyTarget === QualifyTarget::WINNERS ? 'winnaar' : ($qualifyTarget === QualifyTarget::LOSERS ? 'verliezer' : '');
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
        return $this->getHtmlNumber($roundNumber->getNumber()) . ' ronde';
    }

    public function getRoundName(Round $round, bool $sameName = false): string
    {
        if ($this->roundAndParentsNeedsRanking($round) || !$this->childRoundsHaveEqualDepth($round)) {
            return $this->getHtmlNumber($round->getNumberAsValue()) . ' ronde';
        }

        $nrOfRoundsToGo = $this->getMaxDepth($round);
        if ($nrOfRoundsToGo >= 1) {
            return $this->getFractalNumber((int)pow(2, $nrOfRoundsToGo)) . ' finale';
        }
        // if (round.getNrOfPlaces() > 1) {
        if ($round->getNrOfPlaces() === 2 && $sameName === false) {
            $rank = $this->getPreviousNrOfDropoutsMap($round)->get($round) + 1;
            return $this->getHtmlNumber($rank) . '/' . $this->getHtmlNumber($rank + 1) . ' plaats';
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
        if ($competitorName && $this->competitorMap !== null) {
            $competitor = $this->competitorMap->getCompetitor($place->getStartLocation());
            if ($competitor !== null) {
                return $competitor->getName();
            }
        }
        if ($longName === true) {
            return $this->getPouleName($place->getPoule(), true) . ' nr. ' . $place->getPlaceNr();
        }
        $name = $this->getPouleName($place->getPoule(), false);
        return $name . $place->getPlaceNr();
    }

    public function getPlaceFromName(Place $place, bool $competitorName, bool $longName = false): string
    {
        if ($competitorName && $this->competitorMap !== null) {
            $competitor = $this->competitorMap->getCompetitor($place->getStartLocation());
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

        if ($fromQualifyRule instanceof MultipleQualifyRule) {
            if ($longName) {
                return $this->getHorizontalPouleName($fromQualifyRule->getFromHorizontalPoule());
            }
            return '?' . $fromQualifyRule->getFromPlaceNumber();
        }

        $fromPlace = $fromQualifyRule->getFromPlace($place);
        if ($longName !== true || $fromPlace->getPoule()->needsRanking()) {
            return $this->getPlaceName($fromPlace, false, $longName);
        }
        $name = $this->getQualifyTargetDescription(
            $fromPlace->getPlaceNr() === 1 ? QualifyTarget::WINNERS : QualifyTarget::LOSERS
        );
        return $name . ' ' . $this->getPouleName($fromPlace->getPoule(), false);
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

    /**
     * "nummers 2" voor winners complete
     * "3 beste nummers 2" voor winners incomplete
     *
     * "nummers 2 na laast" voor losers complete
     * "3 slechtste nummers 2 na laast" voor losers incomplete
     *
     * @param HorizontalPoule $horizontalPoule
     * @return string
     */
    public function getHorizontalPouleName(HorizontalPoule $horizontalPoule): string
    {
        $qualifyRule = $horizontalPoule->getQualifyRule();
        if ($qualifyRule === null) {
            return 'nummers ' . $horizontalPoule->getNumber();
        }
        $nrOfToPlaces = $qualifyRule->getNrOfToPlaces();

        if ($qualifyRule->getQualifyTarget() === QualifyTarget::WINNERS) {
            $name = 'nummer' . ($nrOfToPlaces > 1 ? 's ' : ' ') . $horizontalPoule->getNumber();
            if ($qualifyRule instanceof MultipleQualifyRule) {
                return ($nrOfToPlaces > 1 ? ($nrOfToPlaces . ' ') : '') . 'beste ' . $name;
            }
            return $name;
        }
        $name = ($nrOfToPlaces > 1 ? 'nummers ' : '');
        $name .= $horizontalPoule->getNumber() > 1 ? (($horizontalPoule->getNumber() - 1) . ' na laatst') : 'laatste';
        if ($qualifyRule instanceof MultipleQualifyRule) {
            return ($nrOfToPlaces > 1 ? ($nrOfToPlaces . ' ') : '') . 'slechtste ' . $name;
        }
        return $name;
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
     * @param int $ruleSet
     * @return list<string>
     */
    public function getRulesName(int $ruleSet): array
    {
        $rankingRuleGetter = new RankingRuleGetter();
        return array_values(array_map(function (int $rule): string {
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
        }, $rankingRuleGetter->getRules($ruleSet, false)));
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
            }
            return $depthAll === $qualifyGroupMaxDepth;
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

    /**
     * @return string
     */
    protected function getHtmlNumber(int $number): string
    {
        if ($number === 1) {
            return $number . 'ste';
        }
        return $number . 'de';
        // return '&frac1' . $number . ';';
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
