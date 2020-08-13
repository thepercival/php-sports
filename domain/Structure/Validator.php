<?php

namespace Sports\Structure;

use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\NameService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Poule;
use Sports\Round;
use Sports\Association;
use Sports\Round\Number as RoundNumber;
use Sports\Competition;
use Sports\Structure;

class Validator
{
    /**
     * @var NameService
     */
    protected $nameService;

    public function __construct()
    {
        $this->nameService = new NameService();
    }

    public function checkValidity(Competition $competition, Structure $structure = null)
    {
        $prefix = "de structuur(competition-id:" . $competition->getId() . ")";

        if (!($structure instanceof Structure)) {
            throw new \Exception($prefix . " heeft geen rondenummers", E_ERROR);
        }

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $this->checkRoundNumberValidity($firstRoundNumber, $competition);
        foreach ($firstRoundNumber->getRounds() as $round) {
            $this->checkRoundValidity($round);
        }
    }

    public function checkRoundNumberValidity(
        RoundNumber $roundNumber,
        Competition $competition
    ) {
        $prefix = "rondenummer " . $roundNumber->getNumber() . $this->getIdOutput($roundNumber->getId());
        if ($roundNumber->getRounds()->count() === 0) {
            throw new Exception($prefix . " bevat geen ronden", E_ERROR);
        }

        foreach ($competition->getSportConfigs() as $sportConfig) {
            if ($roundNumber->isFirst()) {
                if ($roundNumber->getSportScoreConfig($sportConfig->getSport()) === null) {
                    throw new Exception($prefix . " bevat geen geldige sportscoreconfig", E_ERROR);
                }
            }
        }
        if ($roundNumber->hasNext()) {
            $this->checkRoundNumberValidity($roundNumber->getNext(), $competition);
        }
    }

    protected function getIdOutput($id = null): string
    {
        return $id !== null ? " (" . $id . ")" : '';
    }

    /**
     * @param Round $round
     */
    public function checkRoundValidity(Round $round)
    {
        if ($round->getPoules()->count() === 0) {
            throw new Exception("ronde " . $this->getIdOutput($round->getId()) . " bevat geen poules", E_ERROR);
        }

        $this->checkPoulesNumberGap($round->getPoules());
        foreach ($round->getPoules() as $poule) {
            $this->checkPouleValidity($poule);
        }
        $this->checkRoundNrOfPlaces($round);

        if (!$round->getNumber()->hasNext() && $round->getQualifyGroups()->count() > 0) {
            throw new Exception(
                "ronde " . $this->getIdOutput(
                    $round->getId()
                ) . " heeft geen volgende ronde, maar wel kwalificatiegroepen", E_ERROR
            );
        }

        $this->checkQualifyGroupsNumberGap($round->getQualifyGroups(QualifyGroup::WINNERS));
        $this->checkQualifyGroupsNumberGap($round->getQualifyGroups(QualifyGroup::LOSERS));
        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $this->checkRoundValidity($qualifyGroup->getChildRound());
        }
    }

    public function checkPoulesNumberGap(Collection $poules)
    {
        $startNumber = 1;
        foreach ($poules as $poule) {
            if ($poule->getNumber() !== $startNumber++) {
                throw new Exception("het nummer van de poule is onjuist", E_ERROR);
            }
        }
    }

    public function checkQualifyGroupsNumberGap(Collection $qualifyGroups)
    {
        $startNumber = 1;
        foreach ($qualifyGroups as $qualifyGroup) {
            if ($qualifyGroup->getNumber() !== $startNumber++) {
                throw new Exception("het nummer van de kwalificatiegroep is onjuist", E_ERROR);
            }
        }
    }

    protected function checkRoundNrOfPlaces(Round $round)
    {
        $minNrOfPlaces = null;
        $maxNrOfPlaces = null;
        foreach ($round->getPoules() as $poule) {
            if ($minNrOfPlaces === null || $poule->getPlaces()->count() < $minNrOfPlaces) {
                $minNrOfPlaces = $poule->getPlaces()->count();
            }
            if ($maxNrOfPlaces === null || $poule->getPlaces()->count() > $maxNrOfPlaces) {
                $maxNrOfPlaces = $poule->getPlaces()->count();
            }
        }
        if ($maxNrOfPlaces - $minNrOfPlaces > 1) {
            throw new Exception(
                "bij ronde " . $this->getIdOutput($round->getId()) . " zijn er poules met meer dan 1 plaats verschil",
                E_ERROR
            );
        }
    }

    /**
     * @param Poule $poule
     * @throws \Exception
     */
    public function checkPouleValidity(Poule $poule)
    {
        $this->checkPlacesNumberGap($poule->getPlaces());
        if ($poule->getPlaces()->count() === 0) {
            $prefix = "poule " . $this->getIdOutput($poule->getId()) . "(" . $this->nameService->getPouleName(
                    $poule,
                    false
                ) . ", rondenummer: " . $poule->getRound()->getNumberAsValue() . " )";
            throw new Exception($prefix . " bevat geen plekken", E_ERROR);
        }
    }

    public function checkPlacesNumberGap(Collection $places)
    {
        $startNumber = 1;
        foreach ($places as $place) {
            if ($place->getNumber() !== $startNumber++) {
                throw new Exception("het nummer van de plek in de poule is onjuist", E_ERROR);
            }
        }
    }
}
