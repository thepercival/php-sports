<?php

declare(strict_types=1);

namespace Sports\Structure;

use Doctrine\ORM\EntityManagerInterface;
use Sports\Competition;
use Sports\Poule\Horizontal\Creator as HorizontalPouleCreator;
use Sports\Qualify\Rule\Creator as QualifyRuleCreator;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Round\Number\Repository as RoundNumberRepository;
use Sports\Structure as StructureBase;

class Repository
{
    private RoundNumberRepository $roundNumberRepos;

    public function __construct(
        protected EntityManagerInterface $em,
        private HorizontalPouleCreator $horPouleCreator,
        private QualifyRuleCreator $qualifyRuleCreator
    ) {
        $metaData = $em->getClassMetadata(RoundNumber::class);
        $this->roundNumberRepos = new RoundNumberRepository($em, $metaData);
    }

    public function removeAndAdd(Competition $competition, StructureBase $newStructure, int $roundNumberValue = null): RoundNumber
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $this->remove($competition, $roundNumberValue);
            $roundNumber = $this->addNoFlush($newStructure, $roundNumberValue);

            $this->em->flush();
            $conn->commit();
            return $roundNumber;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function add(StructureBase $structure, int $roundNumberValue = null): RoundNumber
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $roundNumber = $this->addNoFlush($structure, $roundNumberValue);

            $this->em->flush();
            $conn->commit();
            return $roundNumber;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    protected function addNoFlush(StructureBase $structure, int $roundNumberValue = null): RoundNumber
    {
        $roundNumber = $structure->getRoundNumber($roundNumberValue !== null ? $roundNumberValue : 1);
        if ($roundNumber === null) {
            throw new \Exception("rondenummer kon niet gevonden worden", E_ERROR);
        }
        $this->customPersistHelper($roundNumber);
        return $roundNumber;
    }

    protected function customPersistHelper(RoundNumber $roundNumber): void
    {
        foreach ($roundNumber->getRounds() as $round) {
            $this->em->persist($round);
        }
        $this->em->persist($roundNumber);
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->customPersistHelper($nextRoundNumber);
        }
    }

    public function hasStructure(Competition $competition): bool
    {
        $roundNumbers = $this->roundNumberRepos->findBy(array("competition" => $competition));
        return count($roundNumbers) > 0;
    }

    public function getStructure(Competition $competition): StructureBase
    {
        $roundNumbers = $this->roundNumberRepos->findBy(array("competition" => $competition), array("number" => "asc"));
        $firstRoundNumber = reset($roundNumbers);
        if ($firstRoundNumber === false) {
            throw new \Exception('mallformed structure, no roundnumbers', E_ERROR);
        }

        $rootRound = $firstRoundNumber->getRounds()->first();
        if ($rootRound === false) {
            throw new \Exception('mallformed structure, no rootround', E_ERROR);
        }
        $structure = new StructureBase($firstRoundNumber, $rootRound);

        $this->addHorizontalPoules($rootRound);
        $this->addQualifyRules($rootRound);

        return $structure;
    }

    /**
     * @param array<string, mixed> $filter
     * @return array<int|string, StructureBase>
     */
    public function getStructureMap(array $filter): array
    {
        /** @var array<int|string, StructureBase> $structureMap */
        $structureMap = [];
        $roundNumbers = $this->roundNumberRepos->findBy($filter, array("number" => "asc"));
        foreach ($roundNumbers as $roundNumber) {
            $competitionId = $roundNumber->getCompetition()->getId();
            if ($competitionId === null || isset($structureMap[$competitionId])) {
                continue;
            }
            $structure = $this->getStructure($roundNumber->getCompetition());
            $structureMap[$competitionId] = $structure;
        }
        return $structureMap;
    }

    /**
     * @return void
     */
    protected function remove(Competition $competition, int $roundNumberAsValue = null)
    {
        if ($roundNumberAsValue === null) {
            $roundNumberAsValue = 1;
        }
        $structure = $this->getStructure($competition);
        $roundNumber = $structure->getRoundNumber($roundNumberAsValue);
        if ($roundNumber === null) {
            return;
        }
        if ($roundNumber->hasNext()) {
            $this->remove($competition, $roundNumberAsValue + 1);
        }

        $this->em->remove($roundNumber);
        $this->em->flush();
    }

    protected function addHorizontalPoules(Round $parentRound): void
    {
        $this->horPouleCreator->remove($parentRound);
        $this->horPouleCreator->create($parentRound);
        foreach ($parentRound->getChildren() as $childRound) {
            $this->addHorizontalPoules($childRound);
        }
    }

    protected function addQualifyRules(Round $parentRound): void
    {
        $this->qualifyRuleCreator->remove($parentRound);
        $this->qualifyRuleCreator->create($parentRound, null, true);
        foreach ($parentRound->getChildren() as $childRound) {
            $this->addQualifyRules($childRound);
        }
    }

    /*public function remove(Structure $structure, int $roundNumberValue = null )
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $roundNumber = $structure->getRoundNumber( $roundNumberValue ? $roundNumberValue : 1);
            if( $roundNumber === null ) {
                throw new \Exception("rondenummer " . $roundNumberValue . " kon niet gevonden worden", E_ERROR);
            }
            $this->em->remove($roundNumber);
            $this->em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }*/
}
