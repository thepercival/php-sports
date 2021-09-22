<?php
declare(strict_types=1);

namespace Sports\Competition\Sport;

use Exception;
use SportsHelpers\Repository as BaseRepository;
use Sports\Score\Config as ScoreConfig;
use Sports\Planning\GameAmountConfig;
use Sports\Planning\GameAmountConfig\Repository as GameAmountConfigRepos;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;
use Sports\Qualify\AgainstConfig\Repository as AgainstQualifyConfigRepos;
use Sports\Score\Config\Repository as ScoreConfigRepos;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Structure;
use Doctrine\ORM\EntityRepository;

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
        $conn = $this->_em->getConnection();
        $conn->beginTransaction();
        try {
            $this->save($competitionSport);

            $rootRound = $structure->getRootRound();
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $scoreRepos = new ScoreConfigRepos($this->_em, $this->_em->getClassMetadata(ScoreConfig::class));
            $scoreRepos->addObjects($competitionSport, $rootRound);
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $againstQualifyConfigRepos = new AgainstQualifyConfigRepos($this->_em, $this->_em->getClassMetadata(AgainstQualifyConfig::class));
            $againstQualifyConfigRepos->addObjects($competitionSport, $rootRound);

            $firstRoundNumber = $structure->getFirstRoundNumber();
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $gameAmountRepos = new GameAmountConfigRepos($this->_em, $this->_em->getClassMetadata(GameAmountConfig::class));
            $gameAmountRepos->addObjects($competitionSport, $firstRoundNumber);

            $this->_em->flush();
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function customRemove(CompetitionSport $competitionSport): void
    {
        $conn = $this->_em->getConnection();
        $conn->beginTransaction();
        try {
            while ($field = $competitionSport->getFields()->first()) {
                $competitionSport->getFields()->removeElement($field);
                $this->_em->remove($field);
            }

            // $scoreRepos = new ScoreConfigRepos($this->_em, $this->_em->getClassMetadata(ScoreConfig::class));
            // $scoreRepos->removeObjects($competitionSport);

//            $planningRepos = new SportPlanningConfigRepos($this->_em, $this->_em->getClassMetaData(SportPlanningConfig::class));
//            $planningRepos->removeObjects($sportConfig);

            $sport = $competitionSport->getSport();
            $this->remove($competitionSport);

            if ($this->findOneBy(["sport" => $sport ]) === null) {
                $this->_em->remove($sport);
            }

            $this->_em->flush();
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
