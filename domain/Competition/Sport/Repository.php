<?php
declare(strict_types=1);

namespace Sports\Competition\Sport;

use Exception;
use Sports\Competition\Field;
use Sports\Sport\Repository as SportRepository;
use Sports\Score\Config as ScoreConfig;
use Sports\Planning\GameAmountConfig;
use Sports\Planning\GameAmountConfig\Repository as GameAmountConfigRepos;
use Sports\Qualify\AgainstConfig as QualifyAgainstConfig;
use Sports\Qualify\AgainstConfig\Repository as QualifyAgainstConfigRepos;
use Sports\Score\Config\Repository as ScoreConfigRepos;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competition\Field\Repository as FieldRepository;
use Sports\Round\Number as RoundNumber;
use Sports\Structure;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?CompetitionSport
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function customAdd(CompetitionSport $competitionSport, Structure $structure): void
    {
        $conn = $this->_em->getConnection();
        $conn->beginTransaction();
        try {
            $this->save($competitionSport);

            $rootRound = $structure->getRootRound();
            $scoreRepos = new ScoreConfigRepos($this->_em, $this->_em->getClassMetadata(ScoreConfig::class));
            $scoreRepos->addObjects($competitionSport, $rootRound);

            $qualifyAgainstConfigRepos = new QualifyAgainstConfigRepos($this->_em, $this->_em->getClassMetadata(QualifyAgainstConfig::class));
            $qualifyAgainstConfigRepos->addObjects($competitionSport, $rootRound);

            $firstRoundNumber = $structure->getFirstRoundNumber();
            $gameAmountRepos = new GameAmountConfigRepos($this->_em, $this->_em->getClassMetadata(GameAmountConfig::class));
            $gameAmountRepos->addObjects($competitionSport, $firstRoundNumber);



            $this->_em->flush();
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function customRemove(CompetitionSport $competitionSport, SportRepository $sportRepos): void
    {
        $conn = $this->_em->getConnection();
        $conn->beginTransaction();
        try {
            $fieldRepos = new FieldRepository($this->_em, $this->_em->getClassMetadata(Field::class));
            while ($field = $competitionSport->getFields()->first()) {
                $competitionSport->getFields()->removeElement($field);
                $fieldRepos->remove($field);
            }

            $scoreRepos = new ScoreConfigRepos($this->_em, $this->_em->getClassMetadata(ScoreConfig::class));
            $scoreRepos->removeObjects($competitionSport);

//            $planningRepos = new SportPlanningConfigRepos($this->_em, $this->_em->getClassMetaData(SportPlanningConfig::class));
//            $planningRepos->removeObjects($sportConfig);

            $sport = $competitionSport->getSport();
            $this->remove($competitionSport);

            if ($this->findOneBy(["sport" => $sport ]) === null) {
                $sportRepos->remove($sport);
            }

            $this->_em->flush();
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
