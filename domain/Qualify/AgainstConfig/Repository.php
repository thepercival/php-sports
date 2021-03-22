<?php
declare(strict_types=1);

namespace Sports\Qualify\AgainstConfig;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Repository as SportRepository;
use Sports\Round;
use Doctrine\ORM\EntityRepository;
use Sports\Qualify\AgainstConfig as QualifyAgainstConfig;

/**
 * @template-extends EntityRepository<QualifyAgainstConfig>
 */
class Repository extends EntityRepository
{
    use SportRepository;

    public function addObjects(CompetitionSport $competitionSport, Round $round): void
    {
        $qualifyConfig = $round->getQualifyAgainstConfig($competitionSport);
        if ($qualifyConfig === null) {
            return;
        }
        $this->save($qualifyConfig);
    }

    public function removeObjects(CompetitionSport $competitionSport): void
    {
        $qualifyConfigs = $this->findBy(["competitionSport" => $competitionSport]);
        foreach ($qualifyConfigs as $qualifyConfig) {
            $this->remove($qualifyConfig);
        }
    }
}
