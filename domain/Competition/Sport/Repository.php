<?php

declare(strict_types=1);

namespace Sports\Competition\Sport;

use Doctrine\ORM\EntityRepository;
use Exception;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Planning\GameAmountConfig;
use Sports\Planning\GameAmountConfig\Repository as GameAmountConfigRepos;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;
use Sports\Qualify\AgainstConfig\Repository as AgainstQualifyConfigRepos;
use Sports\Score\Config as ScoreConfig;
use Sports\Score\Config\Repository as ScoreConfigRepos;
use Sports\Structure;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<CompetitionSport>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<CompetitionSport>
     */
    use BaseRepository;

    public function customAdd(CompetitionSport $competitionSport, Structure $structure): void
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        $conn->beginTransaction();
        try {
            $this->save($competitionSport);

            $metaData = $em->getClassMetadata(ScoreConfig::class);
            $rootRound = $structure->getRootRound();
            $scoreRepos = new ScoreConfigRepos($em, $metaData);
            $scoreRepos->addObjects($competitionSport, $rootRound);
            $metaData = $em->getClassMetadata(AgainstQualifyConfig::class);
            $againstQualifyConfigRepos = new AgainstQualifyConfigRepos($em, $metaData);
            $againstQualifyConfigRepos->addObjects($competitionSport, $rootRound);

            $firstRoundNumber = $structure->getFirstRoundNumber();
            $metaData = $em->getClassMetadata(GameAmountConfig::class);
            $gameAmountRepos = new GameAmountConfigRepos($em, $metaData);
            $gameAmountRepos->addObjects($competitionSport, $firstRoundNumber);

            $em->flush();
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function customRemove(CompetitionSport $competitionSport): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        try {
            while ($field = $competitionSport->getFields()->first()) {
                $competitionSport->getFields()->removeElement($field);
                $this->getEntityManager()->remove($field);
            }

            // $scoreRepos = new ScoreConfigRepos($this->_em, $this->_em->getClassMetadata(ScoreConfig::class));
            // $scoreRepos->removeObjects($competitionSport);

//            $planningRepos = new SportPlanningConfigRepos($this->_em, $this->_em->getClassMetaData(SportPlanningConfig::class));
//            $planningRepos->removeObjects($sportConfig);

            $sport = $competitionSport->getSport();
            $this->remove($competitionSport);

            if ($this->findOneBy(["sport" => $sport ]) === null) {
                $this->getEntityManager()->remove($sport);
            }

            $this->getEntityManager()->flush();
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
