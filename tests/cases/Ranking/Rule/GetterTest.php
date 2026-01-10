<?php

declare(strict_types=1);

namespace Sports\Tests\Ranking\Rule;

use PHPUnit\Framework\TestCase;
use Sports\Ranking\AgainstRuleSet;
use Sports\Ranking\Rule\Getter as RankingRuleGetter;

final class GetterTest extends TestCase
{
    public function testAgainstRuleWithSubScore(): void
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = true;
        $rules = $ruleGetter->getRules(AgainstRuleSet::DiffFirst, $useSubScore);
        self::assertCount(7, $rules);
    }

    public function testAgainstRuleWithoutSubScore(): void
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = false;
        $rules = $ruleGetter->getRules(AgainstRuleSet::DiffFirst, $useSubScore);
        self::assertCount(5, $rules);
    }

    public function testAgainstAmongRuleWithSubScore(): void
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = true;
        $rules = $ruleGetter->getRules(AgainstRuleSet::AmongFirst, $useSubScore);
        self::assertCount(7, $rules);
    }

    public function testAgainstAmongRuleWithoutSubScore(): void
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = false;
        $rules = $ruleGetter->getRules(AgainstRuleSet::AmongFirst, $useSubScore);
        self::assertCount(5, $rules);
    }

    public function testTogetherRuleWithSubScore(): void
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = true;
        $rules = $ruleGetter->getRules(null, $useSubScore);
        self::assertCount(3, $rules);
    }

    public function testTogetherRuleWithoutSubScore(): void
    {
        $ruleGetter = new RankingRuleGetter();
        $useSubScore = false;
        $rules = $ruleGetter->getRules(null, $useSubScore);
        self::assertCount(2, $rules);
    }
}
