<?php

declare(strict_types=1);

namespace Sports;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\Competition\Field as CompetitionField;
use Sports\Competition\Referee;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Ranking\AgainstRuleSet;
use SportsHelpers\Identifiable;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;

class Competition extends Identifiable
{
    private League $league;
    private DateTimeImmutable $startDateTime;
    private AgainstRuleSet $againstRuleSet;
    /**
     * @var Collection<int|string, Round\Number>
     */
    private Collection $roundNumbers;
    /**
     * @var Collection<int|string, Referee>
     */
    private Collection $referees;
    /**
     * @var Collection<int|string, CompetitionSport>
     */
    private Collection $sports;
    /**
     * @var Collection<int|string, TeamCompetitor>
     */
    private Collection $teamCompetitors;

    public const MIN_COMPETITORS = 3;
    public const MAX_COMPETITORS = 40;

    public function __construct(League $league, private Season $season)
    {
        $this->setLeague($league);
        $this->setStartDateTime($season->getStartDateTime());
        $this->againstRuleSet = AgainstRuleSet::DiffFirst;
        $this->roundNumbers = new ArrayCollection();
        $this->referees = new ArrayCollection();
        $this->sports = new ArrayCollection();
        $this->teamCompetitors = new ArrayCollection();
    }

    public function getLeague(): League
    {
        return $this->league;
    }

    private function setLeague(League $league): void
    {
        $competitions = $league->getCompetitions();
        if (!$competitions->contains($this)) {
            $competitions->add($this) ;
        }
        $this->league = $league;
    }

    public function getSeason(): Season
    {
        return $this->season;
    }

    public function getName(): string
    {
        return $this->getLeague()->getName() . ' ' . $this->getSeason()->getName();
    }

    public function getStartDateTime(): DateTimeImmutable
    {
        return $this->startDateTime;
    }

    final public function setStartDateTime(DateTimeImmutable $datetime): void
    {
        $this->startDateTime = $datetime;
    }

    public function getAgainstRuleSet(): AgainstRuleSet
    {
        return $this->againstRuleSet;
    }

    public function setAgainstRuleSet(AgainstRuleSet $againstRuleSet): void
    {
        $this->againstRuleSet = $againstRuleSet;
    }

    /**
     * @return Collection<int|string, Round\Number>
     */
    public function getRoundNumbers(): Collection
    {
        return $this->roundNumbers;
    }

    /**
     * @return Collection<int|string, Referee>
     */
    public function getReferees(): Collection
    {
        return $this->referees;
    }

    public function getReferee(int $priority): Referee
    {
        foreach ($this->getReferees() as $referee) {
            if ($referee->getPriority() === $priority) {
                return $referee;
            }
        }
        throw new Exception('kan de scheidsrechter niet vinden o.b.v. de  prioriteit', E_ERROR);
    }

    /**
     * @return Collection<int|string, TeamCompetitor>
     */
    public function getTeamCompetitors(): Collection
    {
        return $this->teamCompetitors;
    }

    public function getTeamCompetitor(Team $team): TeamCompetitor
    {
        $foundCompetitors = $this->teamCompetitors->filter(function (TeamCompetitor $teamCompetitor) use ($team): bool {
            return $teamCompetitor->getTeam() === $team;
        });
        $foundCompetitor = $foundCompetitors->first();
        if ($foundCompetitor === false) {
            throw new \Exception('the competitor for team "' . $team->getName() . '" was not found', E_ERROR);
        }
        return $foundCompetitor;
    }

    /**
     * @return Collection<int|string, CompetitionSport>
     */
    public function getSports(): Collection
    {
        return $this->sports;
    }

    /**
     * @return list<SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant>
     */
    public function createSportVariants(): array
    {
        return array_values($this->getSports()->map(function (CompetitionSport $competitionSport): SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant {
            return $competitionSport->createVariant();
        })->toArray());
    }

    public function getSingleSport(): CompetitionSport
    {
        $sport = $this->sports->first();
        if ($sport === false) {
            throw new Exception('kan geen sport bij de competitie vinden', E_ERROR);
        }
        return $sport;
    }

    public function getSport(Sport $sport): ?CompetitionSport
    {
        $foundConfigs = $this->sports->filter(function (CompetitionSport $competitionSport) use ($sport): bool {
            return $competitionSport->getSport() === $sport;
        });
        $foundConfig = $foundConfigs->first();
        return $foundConfig !== false ? $foundConfig : null;
    }

    public function hasMultipleSports(): bool
    {
        return $this->sports->count() > 1;
    }

    /**
     * @return Collection<int|string, Sport>
     */
    public function getBaseSports(): Collection
    {
        return $this->sports->map(function (CompetitionSport $competitionSport): Sport {
            return $competitionSport->getSport();
        });
    }

//    /**
//     * @param int|string $sportId
//     * @return Sport|null
//     */
//    public function getSportBySportId($sportId): ?Sport
//    {
//        foreach ($this->getSportConfigs() as $sportConfig) {
//            if ($sportConfig->getSport()->getId() === $sportId) {
//                return $sportConfig->getSport();
//            }
//        }
//        return null;
//    }

    /**
     * @return list<CompetitionField>
     */
    public function getFields(): array
    {
        $fields = [];
        foreach ($this->getSports() as $competitionSport) {
            $fields = array_merge($fields, $competitionSport->getFields()->toArray());
        }
        return array_values($fields);
    }

    public function getAgainstRuleSetNative(): int
    {
        return $this->againstRuleSet->value;
    }

    public function setAgainstRuleSetNative(int $againstRuleSet): void
    {
        /** @psalm-suppress MixedAssignment, UndefinedMethod */
        $this->againstRuleSet = AgainstRuleSet::from($againstRuleSet);
    }
}
