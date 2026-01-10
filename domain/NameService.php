<?php

declare(strict_types=1);

namespace Sports;

use Sports\Ranking\AgainstRuleSet;
use Sports\Ranking\Rule as RankingRule;
use Sports\Ranking\Rule\Getter as RankingRuleGetter;

final class NameService
{
    public function __construct()
    {
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
}
