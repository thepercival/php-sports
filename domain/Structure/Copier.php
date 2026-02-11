<?php

declare(strict_types=1);

namespace Sports\Structure;

use Sports\Category;
use Sports\Competition;
use Sports\Competition\CompetitionSportFromToMapper as CompetitionSportFromToMapper;
use Sports\Competition\CompetitionSport;
use Sports\Place;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Planning\GameAmountConfig;
use Sports\Poule;
use Sports\Poule\Horizontal\Creator as HorizontalPouleCreator;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;
use Sports\Qualify\AgainstConfig\Service as AgainstQualifyConfigService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Rule\Creator as QualifyRuleCreator;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Score\Config as ScoreConfig;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Structure;

final class Copier
{
    public function __construct(
        private HorizontalPouleCreator $horPouleCreator,
        private QualifyRuleCreator $qualifyRuleCreator,
        private CompetitionSportFromToMapper $competitionSportFromToMapper
    ) {
    }

    public function copy(Structure $fromStructure, Competition $toCompetition): Structure
    {
        $newFirstRoundNumber = new RoundNumber($toCompetition);
        $this->copyRoundNumber($fromStructure->getFirstRoundNumber(), $newFirstRoundNumber);
        $newCategories = [];
        foreach ($fromStructure->getCategories() as $fromCategory) {
            $newCategory = new Category($toCompetition, $fromCategory->getName(), $fromCategory->getNumber());
            $newCategory->setAbbreviation($fromCategory->getAbbreviation());
            $newCategories[] = $newCategory;
            $this->copyCategory($fromCategory, $newCategory, $newFirstRoundNumber);
        }
        return new Structure($newCategories, $newFirstRoundNumber);
    }

    protected function copyRoundNumber(RoundNumber $fromRoundNumber, RoundNumber $toRoundNumber): void
    {
        $planningConfigService = new PlanningConfigService();

        $fromPlanningConfig = $fromRoundNumber->getPlanningConfig();
        if ($fromPlanningConfig !== null) {
            $planningConfigService->copy($fromPlanningConfig, $toRoundNumber);
        }
        foreach ($fromRoundNumber->getGameAmountConfigs() as $fromGameAmountConfig) {
            $toCompetitionSport = $this->getToCompetitionSport($fromGameAmountConfig->getCompetitionSport());
            new GameAmountConfig($toCompetitionSport, $toRoundNumber, $fromGameAmountConfig->getAmount() );
        }
        $fromNextRoundNumber = $fromRoundNumber->getNext();
        if ($fromNextRoundNumber !== null) {
            $this->copyRoundNumber($fromNextRoundNumber, $toRoundNumber->createNext());
        }
    }

    protected function getToCompetitionSport(CompetitionSport $fromCompetitionSport): CompetitionSport
    {
        return $this->competitionSportFromToMapper->getToCompetitionSport($fromCompetitionSport);
    }

//    protected function getNewCompetitionSport(CompetitionSport $sourceCompetitionSport, Competition $newCompetition): CompetitionSport
//    {
//        foreach ($newCompetition->getSports() as $competitionSport) {
//            if ($this->sportMappingProperty === self::SPORT_MAPPING_PROP_ID) {
//                if ($competitionSport->getSport()->getId() === $sourceCompetitionSport->getSport()->getId()) {
//                    return $competitionSport;
//                }
//            } else {
//                if ($competitionSport->getSport()->getName() === $sourceCompetitionSport->getSport()->getName()) {
//                    return $competitionSport;
//                }
//            }
//        }
//        throw new Exception("een sport kon niet gevonden worden", E_ERROR);
//    }

    protected function copyCategory(Category $fromCategory, Category $toCategory, RoundNumber $toRoundNumber): void
    {
        $newRoundNumber = $toRoundNumber;
        $fromStructureCells = $fromCategory->getStructureCells()->toArray();
        while( array_shift($fromStructureCells) !== null && $newRoundNumber !== null) {
            new Cell($toCategory, $newRoundNumber);
            $newRoundNumber = $newRoundNumber->getNext();
        }

        $newRootRound = new Round($toCategory->getFirstStructureCell());
        $this->deepCopyRound($fromCategory->getRootRound(), $newRootRound);
    }

    protected function deepCopyRound(Round $fromRound, Round $toRound): void
    {
        $this->copyRoundHelper(
            array_values($fromRound->getPoules()->toArray()),
            array_values($fromRound->getFirstScoreConfigs()->toArray()),
            array_values($fromRound->getAgainstQualifyConfigs()->toArray()),
            $toRound
        );
        $this->horPouleCreator->create($toRound);

        $newNextCell = $toRound->getStructureCell()->getNext();
        if ($newNextCell === null) {
            return;
        }
        foreach ($fromRound->getQualifyGroups() as $qualifyGroup) {
            $newQualifyGroup = new QualifyGroup($toRound, $qualifyGroup->getTarget(), $newNextCell);
            $newQualifyGroup->setNumber($qualifyGroup->getNumber());
            $newQualifyGroup->setDistribution($qualifyGroup->getDistribution());
            // $qualifyGroup->setNrOfHorizontalPoules( $qualifyGroupSerialized->getNrOfHorizontalPoules() );
            $this->deepCopyRound($qualifyGroup->getChildRound(), $newQualifyGroup->getChildRound());
        }
        $this->qualifyRuleCreator->create($toRound, null);
    }

    /**
     * @param list<Poule> $fromPoules
     * @param list<ScoreConfig> $fromScoreConfigs
     * @param list<AgainstQualifyConfig> $fromAgainstQualifyConfigs
     * @param Round $toRound
     */
    protected function copyRoundHelper(
        array $fromPoules,
        array $fromScoreConfigs,
        array $fromAgainstQualifyConfigs,
        Round $toRound
    ): void {
        foreach ($fromPoules as $fromPoule) {
            $this->copyPoule($fromPoule->getNumber(), array_values($fromPoule->getPlaces()->toArray()), $toRound);
        }
        $scoreConfigService = new ScoreConfigService();
        foreach ($fromScoreConfigs as $fromScoreConfig) {
            $newCompetitionSport = $this->getToCompetitionSport($fromScoreConfig->getCompetitionSport() );
            $scoreConfigService->copy($fromScoreConfig, $newCompetitionSport, $toRound );
        }
        $againstQualifyConfigService = new AgainstQualifyConfigService();
        foreach ($fromAgainstQualifyConfigs as $fromAgainstQualifyConfig) {
            $newCompetitionSport = $this->getToCompetitionSport($fromAgainstQualifyConfig->getCompetitionSport() );
            $againstQualifyConfigService->copy($fromAgainstQualifyConfig, $newCompetitionSport, $toRound);
        }
    }

    /**
     * @param int $fromNumber
     * @param list<Place> $fromPlaces
     * @param Round $toRound
     */
    protected function copyPoule(int $fromNumber, array $fromPlaces, Round $toRound): void
    {
        $newPoule = new Poule($toRound, $fromNumber);
        foreach ($fromPlaces as $fromPlace) {
            new Place($newPoule, $fromPlace->getPlaceNr());
        }
    }
}
