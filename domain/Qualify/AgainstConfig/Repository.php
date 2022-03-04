<?php

declare(strict_types=1);

namespace Sports\Qualify\AgainstConfig;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;
use SportsHelpers\Repository as BaseRepository;
use Sports\Round;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<AgainstQualifyConfig>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<AgainstQualifyConfig>
     */
    use BaseRepository;

    public function addObjects(CompetitionSport $competitionSport, Round $round): void
    {
        $qualifyConfig = $round->getAgainstQualifyConfig($competitionSport);
        if ($qualifyConfig === null) {
            return;
        }
        $this->save($qualifyConfig);
    }

    public function removeObjects(CompetitionSport $competitionSport): void
    {
        $qualifyConfigs = $this->findBy(["competitionSport" => $competitionSport]);
        foreach ($qualifyConfigs as $qualifyConfig) {
            $this->remove($qualifyConfig, true);
        }
    }
}
