<?php

namespace Sports\Competition\Sport;

use Exception;
use Sports\Competition\Field;
use Sports\Sport\Repository as SportRepository;
use Sports\Sport\ScoreConfig as SportScoreConfig;
use Sports\Sport\ScoreConfig\Repository as SportScoreConfigRepos;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competition\Field\Repository as FieldRepository;
use Sports\Round\Number as RoundNumber;

class Repository extends \Sports\Repository
{
    public function customAdd(CompetitionSport $competitionSport, RoundNumber $roundNumber)
    {
        $conn = $this->_em->getConnection();
        $conn->beginTransaction();
        try {
            $this->save($competitionSport);

            $scoreRepos = new SportScoreConfigRepos($this->_em, $this->_em->getClassMetadata(SportScoreConfig::class));
            $scoreRepos->addObjects($competitionSport, $roundNumber);

//            $planningRepos = new SportPlanningConfigRepos($this->_em, $this->_em->getClassMetaData(SportPlanningConfig::class));
//            $planningRepos->addObjects($sportConfig->getSport(), $roundNumber );

            $this->_em->flush();
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function customRemove(CompetitionSport $competitionSport, SportRepository $sportRepos)
    {
        $conn = $this->_em->getConnection();
        $conn->beginTransaction();
        try {
            $fieldRepos = new FieldRepository($this->_em, $this->_em->getClassMetadata(Field::class));
            $fields = $competitionSport->getFields()->filter(
                function ($field) use ($competitionSport): bool {
                    return $field->getSport() === $competitionSport->getSport();
                }
            );
            foreach ($fields as $field) {
                $fieldRepos->remove($field);
            }

            $scoreRepos = new SportScoreConfigRepos($this->_em, $this->_em->getClassMetadata(SportScoreConfig::class));
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
