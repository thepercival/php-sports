<?php
declare(strict_types=1);

namespace Sports\Structure;

use Sports\Structure;
use Sports\Round;
use Sports\Poule\Horizontal\Creator as HorizontolPouleCreator;
use Sports\Poule\Horizontal\Service as HorizontalPouleService;
use Sports\Qualify\Rule\Service as QualifyRuleService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Poule\Horizontal\Creator as HorizontalPoule;

class PostCreateService
{
    public function __construct(private Structure $structure)
    {
    }

    public function create(): void
    {
        $rootRound = $this->structure->getRootRound();
        $this->createRoundHorizontalPoules($rootRound);
        $this->createQualifyGroupHorizontalPoules($rootRound);
        $this->recreateToQualifyRules($rootRound);
    }

    protected function createRoundHorizontalPoules(Round $round): void
    {
        $horizontalPouleService = new HorizontalPouleService($round);
        $horizontalPouleService->recreate();
        foreach ($round->getChildren() as $childRound) {
            $this->createRoundHorizontalPoules($childRound);
        }
    }

    protected function createQualifyGroupHorizontalPoules(Round $round): void
    {
        $horizontalPouleService = new HorizontalPouleService($round);
        foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
            $horizontalPouleService->updateQualifyGroups(
                array_slice($round->getHorizontalPoules($winnersOrLosers), 0),
                array_values(array_map(function (QualifyGroup $qualifyGroup): HorizontalPoule {
                    return new HorizontolPouleCreator($qualifyGroup, $qualifyGroup->getChildRound()->getNrOfPlaces());
                }, $round->getWinnersOrLosersQualifyGroups($winnersOrLosers)->toArray()))
            );
        }

        foreach ($round->getChildren() as $childRound) {
            $this->createQualifyGroupHorizontalPoules($childRound);
        }
    }

    protected function recreateToQualifyRules(Round $round): void
    {
        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();

        foreach ($round->getChildren() as $childRound) {
            $this->recreateToQualifyRules($childRound);
        }
    }
}
