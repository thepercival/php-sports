<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-7-19
 * Time: 15:17
 */

namespace Sports\Sport\ScoreConfig;

use Sports\Sport;
use Sports\Sport\Config as SportConfig;
use Sports\Round\Number as RoundNumber;

/**
 * Class Repository
 * @package Sports\Sport\ScoreConfig
 */
class Repository extends \Sports\Repository
{
    public function addObjects(Sport $sport, RoundNumber $roundNumber)
    {
        $sportScoreConfig = $roundNumber->getSportScoreConfig($sport);
        if ($sportScoreConfig === null) {
            return;
        }
        $this->save($sportScoreConfig);
        if ($roundNumber->hasNext()) {
            $this->addObjects($sport, $roundNumber->getNext());
        }
    }

    public function removeObjects(SportConfig $sportConfig)
    {
        $sportScoreConfigs = $this->findBySportConfig($sportConfig);
        foreach ($sportScoreConfigs as $config) {
            $this->remove($config);
        }
    }

    public function findBySportConfig(SportConfig $sportConfig)
    {
        $competition = $sportConfig->getCompetition();
        $query = $this->createQueryBuilder('ssc')
            ->join("ssc.roundNumber", "rn")
            ->where('rn.competition = :competition')
            ->andWhere('ssc.sport = :sport')
        ;
        $query = $query->setParameter('competition', $competition);
        $query = $query->setParameter('sport', $sportConfig->getSport());
        return $query->getQuery()->getResult();
    }
}
