<?php
declare(strict_types=1);

namespace Sports\Ranking\Rule;

use Sports\Ranking\AgainstRuleSet;
use Sports\Ranking\Rule as RankingRule;

class Getter
{
    /**
     * @param AgainstRuleSet|null $ruleSet
     * @param bool $useSubScore
     * @return list<RankingRule>
     */
    public function getRules(AgainstRuleSet|null $ruleSet, bool $useSubScore): array
    {
        if ($ruleSet === null) {
            return $this->getTogetherRules($useSubScore);
        }
        return $this->getAgainstRules($ruleSet, $useSubScore);
    }

    /**
     * @param AgainstRuleSet $ruleSet
     * @param bool $useSubScore
     * @return list<RankingRule>
     */
    protected function getAgainstRules(AgainstRuleSet $ruleSet, bool $useSubScore): array
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
     * @return list<RankingRule>
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
