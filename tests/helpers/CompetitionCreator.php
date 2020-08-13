<?php

namespace Sports\TestHelper;

use Sports\Competition;

trait CompetitionCreator {
    protected function createCompetition(): Competition
    {
        $json_raw = file_get_contents(__DIR__ . "/../data/competition.json");
        if ($json_raw === false) {
            throw new \Exception("competition-json not read well from file", E_ERROR);
        }
        $json = json_decode($json_raw, true);
        if ($json === false) {
            throw new \Exception("competition-json not read well from file", E_ERROR);
        }
        $jsonEncoded = json_encode($json);
        if ($jsonEncoded === false) {
            throw new \Exception("competition-json not read well from file", E_ERROR);
        }
        $serializer = (new Serializer())->getSerializer();
        $competition = $serializer->deserialize($jsonEncoded, 'Sports\Competition', 'json');

        foreach ($competition->getSportConfigs() as $sportConfig) {
            $refCl = new \ReflectionClass($sportConfig);
            $refClPropSport = $refCl->getProperty("competition");
            $refClPropSport->setAccessible(true);
            $refClPropSport->setValue($sportConfig, $competition);
            $refClPropSport->setAccessible(false);

            foreach ($sportConfig->getFields() as $field) {
                $refCl = new \ReflectionClass($field);
                $refClPropSport = $refCl->getProperty("sportConfig");
                $refClPropSport->setAccessible(true);
                $refClPropSport->setValue($field, $sportConfig);
                $refClPropSport->setAccessible(false);
            }
        }

        return $competition;
    }
}

