<?php

namespace Sports;

use Sports\Competitor\Map as CompetitorMap;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Round\Number as RoundNumber;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Game\Place as GamePlace;
use Sports\Ranking\Rule\Getter as RankingRuleGetter;
use Sports\Ranking\Rule as RankingRule;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;

class NameService
{
    /**
     * @var CompetitorMap|null
     */
    protected $competitorMap;

    public function __construct(CompetitorMap $competitorMap = null)
    {
        $this->competitorMap = $competitorMap;
    }

    public function getWinnersLosersDescription(int $winnersOrLosers, bool $multiple = false): string
    {
        $description = $winnersOrLosers === QualifyGroup::WINNERS ? 'winnaar' : ($winnersOrLosers === QualifyGroup::LOSERS ? 'verliezer' : '');
        return (($multiple && ($description !== '')) ? $description . 's' : $description);
    }

    /**
     *  als allemaal dezelfde naam dan geef die naam
     * als verschillde namen geef dan xde ronde met tooltip van de namen
     */
    public function getRoundNumberName(RoundNumber $roundNumber): string
    {
        if ($this->roundsHaveSameName($roundNumber)) {
            return $this->getRoundName($roundNumber->getRounds()->first(), true);
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
            $rank = $round->getStructureNumber() + 1;
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
        $pouleStructureNumber = $poule->getStructureNumber() - 1;
        $secondLetter = $pouleStructureNumber % 26;
        if ($pouleStructureNumber >= 26) {
            $firstLetter = (int)(($pouleStructureNumber - $secondLetter) / 26);
            $pouleName .= (chr(ord('A') + ($firstLetter - 1)));
        }
        return $pouleName . (chr(ord('A') + $secondLetter));
    }

    /**
     * @param bool|null $longName
     */
    public function getPlaceName(Place $place, bool $competitorName = false, ?bool $longName = false): string
    {
        $competitorName = $competitorName && $this->competitorMap !== null;
        if ($competitorName === true) {
            $competitor = $this->competitorMap->getCompetitor($place->getStartLocation());
            if ($competitor !== null) {
                return $competitor->getName();
            }
        }
        if ($longName === true) {
            return $this->getPouleName($place->getPoule(), true) . ' nr. ' . $place->getNumber();
        }
        $name = $this->getPouleName($place->getPoule(), false);
        return $name . $place->getNumber();
    }

    public function getPlaceFromName(Place $place, bool $competitorName, bool $longName = false): string
    {
        $competitorName = $competitorName && $this->competitorMap !== null;
        if ($competitorName === true) {
            $competitor = $this->competitorMap->getCompetitor($place->getStartLocation());
            if ($competitor !== null) {
                return $competitor->getName();
            }
        }

        $parentQualifyGroup = $place->getRound()->getParentQualifyGroup();
        if ($parentQualifyGroup === null) {
            return $this->getPlaceName($place, false, $longName);
        }

        $fromQualifyRule = $place->getFromQualifyRule();
        if ($fromQualifyRule instanceof MultipleQualifyRule) {
            if ($longName) {
                return $this->getHorizontalPouleName($fromQualifyRule->getFromHorizontalPoule());
            }
            return '?' . $fromQualifyRule->getFromPlaceNumber();
        }
        if ($fromQualifyRule instanceof SingleQualifyRule) {
            $fromPlace = $fromQualifyRule->getFromPlace();
            if ($longName !== true || $fromPlace->getPoule()->needsRanking()) {
                return $this->getPlaceName($fromPlace, false, $longName);
            }
            $name = $this->getWinnersLosersDescription(
                $fromPlace->getNumber() === 1 ? QualifyGroup::WINNERS : QualifyGroup::LOSERS
            );
            return $name . ' ' . $this->getPouleName($fromPlace->getPoule(), false);
        }
        return '?';
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
        if ($horizontalPoule->getQualifyGroup() === null) {
            return 'nummers ' . $horizontalPoule->getNumber();
        }
        $nrOfQualifiers = $horizontalPoule->getNrOfQualifiers();

        if ($horizontalPoule->getWinnersOrLosers() === QualifyGroup::WINNERS) {
            $name = 'nummer' . ($nrOfQualifiers > 1 ? 's ' : ' ') . $horizontalPoule->getNumber();
            if ($horizontalPoule->isBorderPoule()) {
                return ($nrOfQualifiers > 1 ? ($nrOfQualifiers . ' ') : '') . 'beste ' . $name;
            }
            return $name;
        }
        $name = ($nrOfQualifiers > 1 ? 'nummers ' : '');
        $name .= $horizontalPoule->getNumber() > 1 ? (($horizontalPoule->getNumber() - 1) . ' na laatst') : 'laatste';
        if ($horizontalPoule->isBorderPoule()) {
            return ($nrOfQualifiers > 1 ? ($nrOfQualifiers . ' ') : '') . 'slechtste ' . $name;
        }
        return $name;
    }

    public function getRefereeName(Game $game, bool $longName = null): ?string
    {
        if ($game->getReferee() !== null) {
            return $longName ? $game->getReferee()->getName() : $game->getReferee()->getInitials();
        }
        if ($game->getRefereePlace() !== null) {
            return $this->getPlaceName($game->getRefereePlace(), true, $longName);
        }
        return '';
    }

    public function getRulesName(int $ruleSet): array
    {
        $rankingRuleGetter = new RankingRuleGetter();
        return array_map(function (int $rule): string {
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
        if (!$round->isRoot()) {
            return $this->roundAndParentsNeedsRanking($round->getParent());
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
}
