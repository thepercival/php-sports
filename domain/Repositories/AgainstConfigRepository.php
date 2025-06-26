<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;
use Sports\Round;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<AgainstQualifyConfig>
 */
class AgainstConfigRepository extends EntityRepository
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
