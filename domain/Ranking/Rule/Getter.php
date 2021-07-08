<?php
declare(strict_types=1);

namespace Sports\Ranking\Rule;

use Sports\Ranking\AgainstRuleSet;
use Sports\Ranking\Rule as RankingRule;

class Getter
{
    /**
     * @param int|null $ruleSet
     * @param bool $useSubScore
     * @return list<int>
     */
    public function getRules(int|null $ruleSet, bool $useSubScore): array
    {
        if ($ruleSet === null) {
            return $this->getTogetherRules($useSubScore);
        }
        return $this->getAgainstRules($ruleSet, $useSubScore);
    }

    /**
     * @param int $ruleSet
     * @param bool $useSubScore
     * @return list<int>
     */
    protected function getAgainstRules(int $ruleSet, bool $useSubScore): array
    {
        $rules = [RankingRule::MostPoints, RankingRule::FewestGames];
        if ($ruleSet === AgainstRuleSet::AmongFirst) {
            $rules[] = RankingRule::BestAmongEachOther;
        }
        $rules[] = RankingRule::BestUnitDifference;
        $rules[] = RankingRule::MostUnitsScored;
        if ($useSubScore) {
            $rules[] = RankingRule::BestSubUnitDifference;
            $rules[] = RankingRule::MostSubUnitsScored;
        }
        if ($ruleSet === AgainstRuleSet::DiffFirst) {
            $rules[] = RankingRule::BestAmongEachOther;
        }
        return $rules;
    }

    /**
     * @param bool $useSubScore
     * @return list<int>
     */
    protected function getTogetherRules(bool $useSubScore): array
    {
        $rules = [RankingRule::MostUnitsScored];
        if ($useSubScore) {
            $rules[] = RankingRule::MostSubUnitsScored;
        }
        $rules[] = RankingRule::FewestGames;
        return $rules;
    }
}
