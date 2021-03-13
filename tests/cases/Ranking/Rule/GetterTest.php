<?php

namespace Sports\Tests\Ranking\Rule;

use PHPUnit\Framework\TestCase;
use Sports\Ranking\Rule\Getter as RankingRuleGetter;
use Sports\Ranking\RuleSet as RankingRuleSet;

class GetterTest extends TestCase
{
    public function testAgainstRuleWithSubScore()
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = true;
        $rules = $ruleGetter->getRules(RankingRuleSet::Against, $useSubScore);
        self::assertCount(7, $rules);
    }

    public function testAgainstRuleWithoutSubScore()
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = false;
        $rules = $ruleGetter->getRules(RankingRuleSet::Against, $useSubScore);
        self::assertCount(5, $rules);
    }

    public function testAgainstAmongRuleWithSubScore()
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = true;
        $rules = $ruleGetter->getRules(RankingRuleSet::AgainstAmong, $useSubScore);
        self::assertCount(7, $rules);
    }

    public function testAgainstAmongRuleWithoutSubScore()
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = false;
        $rules = $ruleGetter->getRules(RankingRuleSet::AgainstAmong, $useSubScore);
        self::assertCount(5, $rules);
    }

    public function testTogetherRuleWithSubScore()
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = true;
        $rules = $ruleGetter->getRules(RankingRuleSet::Together, $useSubScore);
        self::assertCount(3, $rules);
    }

    public function testTogetherRuleWithoutSubScore()
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = false;
        $rules = $ruleGetter->getRules(RankingRuleSet::Together, $useSubScore);
        self::assertCount(2, $rules);
    }
}
