<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Sports\Sport\Config;

use Sports\Sport\Repository as SportRepository;
use Sports\Sport\Config as SportConfig;
use Sports\Sport\ScoreConfig as SportScoreConfig;
use Sports\Sport\ScoreConfig\Repository as SportScoreConfigRepos;
use Sports\Sport\PlanningConfig as SportPlanningConfig;
use Sports\Sport\PlanningConfig\Repository as SportPlanningConfigRepos;
use Sports\Field;
use Sports\Field\Repository as FieldRepository;
use Sports\Round\Number as RoundNumber;

/**
 * Class Repository
 * @package Sports\Config\Score
 */
class Repository extends \Sports\Repository
{
    public function customAdd(SportConfig $sportConfig, RoundNumber $roundNumber)
    {
        $conn = $this->_em->getConnection();
        $conn->beginTransaction();
        try {
            $this->save($sportConfig);

            $scoreRepos = new SportScoreConfigRepos($this->_em, $this->_em->getClassMetadata(SportScoreConfig::class));
            $scoreRepos->addObjects($sportConfig->getSport(), $roundNumber);

//            $planningRepos = new SportPlanningConfigRepos($this->_em, $this->_em->getClassMetaData(SportPlanningConfig::class));
//            $planningRepos->addObjects($sportConfig->getSport(), $roundNumber );

            $this->_em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function customRemove(SportConfig $sportConfig, SportRepository $sportRepos)
    {
        $conn = $this->_em->getConnection();
        $conn->beginTransaction();
        try {
            $fieldRepos = new FieldRepository($this->_em, $this->_em->getClassMetadata(Field::class));
            $fields = $sportConfig->getFields()->filter(
                function ($field) use ($sportConfig): bool {
                    return $field->getSport() === $sportConfig->getSport();
                }
            );
            foreach ($fields as $field) {
                $fieldRepos->remove($field);
            }

            $scoreRepos = new SportScoreConfigRepos($this->_em, $this->_em->getClassMetadata(SportScoreConfig::class));
            $scoreRepos->removeObjects($sportConfig);

//            $planningRepos = new SportPlanningConfigRepos($this->_em, $this->_em->getClassMetaData(SportPlanningConfig::class));
//            $planningRepos->removeObjects($sportConfig);

            $sport = $sportConfig->getSport();
            $this->remove($sportConfig);

            if ($this->findOneBy(["sport" => $sport ]) === null) {
                $sportRepos->remove($sport);
            }

            $this->_em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
