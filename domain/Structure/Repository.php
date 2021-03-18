<?php

namespace Sports\Structure;

use Sports\Structure as StructureBase;
use Sports\Round\Number as RoundNumber;
use Sports\Competition;
use Doctrine\ORM\EntityManager;
use Sports\Round\Number\Repository as RoundNumberRepository;

class Repository
{
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var RoundNumberRepository
     */
    protected $roundNumberRepos;


    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->roundNumberRepos = new RoundNumberRepository($this->em, $this->em->getClassMetadata(RoundNumber::class));
    }

    public function removeAndAdd(Competition $competition, StructureBase $newStructure, int $roundNumberValue = null): RoundNumber
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $this->remove($competition, $roundNumberValue);
            $roundNumber = $this->add($newStructure, $roundNumberValue);

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
        $roundNumber = $structure->getRoundNumber($roundNumberValue !== null ? $roundNumberValue : 1);
        if ($roundNumber === null) {
            throw new \Exception("rondenummer " . $roundNumberValue . " kon niet gevonden worden", E_ERROR);
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
        if ($roundNumber->hasNext()) {
            $this->customPersistHelper($roundNumber->getNext());
        }
    }

    public function hasStructure(Competition $competition): bool
    {
        $roundNumbers = $this->roundNumberRepos->findBy(array("competition" => $competition));
        return count($roundNumbers) > 0;
    }

    public function getStructure(Competition $competition): ?StructureBase
    {
        $roundNumbers = $this->roundNumberRepos->findBy(array("competition" => $competition), array("number" => "asc"));
        if (count($roundNumbers) === 0) {
            return null;
        }
        $roundNumber = reset($roundNumbers);
        while ($nextRoundNumber = next($roundNumbers)) {
            $roundNumber->setNext($nextRoundNumber);
            $roundNumber = $nextRoundNumber;
        }
        $firstRoundNumber = reset($roundNumbers);

        $firstRound = $firstRoundNumber->getRounds()->first();
        $structure = new StructureBase($firstRoundNumber, $firstRound);
        $structure->setStructureNumbers();

        $postCreateService = new PostCreateService($structure);
        $postCreateService->create();

        return $structure;
    }

    /**
     * @param array $filter
     * @return array|StructureBase[]
     */
    public function getStructures(array $filter): array
    {
        $structures = [];

        $roundNumbers = $this->roundNumberRepos->findBy($filter, array("number" => "asc"));
        foreach ($roundNumbers as $roundNumber) {
            if (array_key_exists($roundNumber->getCompetition()->getId(), $structures)) {
                continue;
            }
            $structures[$roundNumber->getCompetition()->getId()] = $this->getStructure($roundNumber->getCompetition());
        }
        return $structures;
    }

    /**
     * @return void
     */
    public function remove(Competition $competition, int $roundNumberAsValue = null)
    {
        if ($roundNumberAsValue === null) {
            $roundNumberAsValue = 1;
        }
        $structure = $this->getStructure($competition);
        if ($structure === null) {
            return;
        }
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
