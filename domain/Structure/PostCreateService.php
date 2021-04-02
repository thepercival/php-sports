<?php
declare(strict_types=1);

namespace Sports\Structure;

use Sports\Poule\Horizontal\Creator as HorizontalPoule;
use Sports\Poule\Horizontal\Creator as HorizontolPouleCreator;
use Sports\Structure;
use Sports\Round;
use Sports\Poule\Horizontal\Service as HorizontalPouleService;
use Sports\Qualify\Rule\Service as QualifyRuleService;
use Sports\Qualify\Group as QualifyGroup;

class PostCreateService
{
    public function __construct(private Structure $structure)
    {
    }

    public function create(): void
    {
        $rootRound = $this->structure->getRootRound();

        // $this->removeToQualifyRules($rootRound);
        $this->removeQualifyGroupFromHorizontalPoules($rootRound);
        // this->fromPlace->setSingleToQualifyRule null
        // horpoule set multiple = null

        // empty qualgrouo->Horizontalpoules

        // remove Horizontalpoules


        // add Horizontalpoules with link to places


        $this->createRoundHorizontalPoules($rootRound);
        $this->createQualifyGroupHorizontalPoules($rootRound);

        // $this->createToQualifyRules($rootRound);
    }

    protected function createRoundHorizontalPoules(Round $round): void
    {
        $horizontalPouleService = new HorizontalPouleService($round);
        $horizontalPouleService->recreate();
        foreach ($round->getChildren() as $childRound) {
            $this->createRoundHorizontalPoules($childRound);
        }
    }

    protected function removeQualifyGroupFromHorizontalPoules(Round $round): void
    {
        foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
            foreach( $round->getHorizontalPoules2($winnersOrLosers) as $horPoule) {
                $horPoule->setQualifyGroup(null);
            }
        }
    }

    protected function createQualifyGroupHorizontalPoules(Round $round): void
    {
        $horizontalPouleService = new HorizontalPouleService($round);
        foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
            $horizontalPouleService->updateQualifyGroups(
                $round->getHorizontalPoules2($winnersOrLosers),
                array_values(array_map(function (QualifyGroup $qualifyGroup): HorizontalPoule {
                    return new HorizontolPouleCreator($qualifyGroup, $qualifyGroup->getChildRound()->getNrOfPlaces());
                }, $round->getWinnersOrLosersQualifyGroups($winnersOrLosers)->toArray()))
            );
        }

        foreach ($round->getChildren() as $childRound) {
            $this->createQualifyGroupHorizontalPoules($childRound);
        }
    }

    protected function removeToQualifyRules(Round $round): void
    {
        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->removeTo($round);

        foreach ($round->getChildren() as $childRound) {
            $this->removeToQualifyRules($childRound);
        }
    }

    protected function createToQualifyRules(Round $round): void
    {
        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->createTo($round);

        foreach ($round->getChildren() as $childRound) {
            $this->createToQualifyRules($childRound);
        }
    }
}
