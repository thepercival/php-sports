<?php
declare(strict_types=1);

namespace Sports;

use DateTimeImmutable;
use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use \Doctrine\ORM\PersistentCollection;
use Exception;
use Sports\Ranking\RuleSet as RankingRuleSet;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competition\Field as CompetitionField;
use SportsHelpers\Identifiable;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Competition\Referee;
use Sports\Competition\Sport as CompetitonSport;

class Competition extends Identifiable
{
    private League $league;
    private DateTimeImmutable $startDateTime;
    private int $rankingRuleSet;
    private int $state;
    /**
     * @phpstan-var ArrayCollection<int|string, Round\Number>|PersistentCollection<int|string, Round\Number>
     * @psalm-var ArrayCollection<int|string, Round\Number>
     */
    private ArrayCollection|PersistentCollection $roundNumbers;
    /**
     * @phpstan-var ArrayCollection<int|string, Referee>|PersistentCollection<int|string, Referee>
     * @psalm-var ArrayCollection<int|string, Referee>
     */
    private ArrayCollection|PersistentCollection $referees;
    /**
     * @phpstan-var ArrayCollection<int|string, CompetitionSport>|PersistentCollection<int|string, CompetitionSport>
     * @psalm-var ArrayCollection<int|string, CompetitionSport>
     */
    private ArrayCollection|PersistentCollection $sports;
    /**
     * @phpstan-var ArrayCollection<int|string, TeamCompetitor>|PersistentCollection<int|string, TeamCompetitor>
     * @psalm-var ArrayCollection<int|string, TeamCompetitor>
     */
    private ArrayCollection|PersistentCollection $teamCompetitors;

    const MIN_COMPETITORS = 3;
    const MAX_COMPETITORS = 40;

    public function __construct(League $league, private Season $season)
    {
        $this->setLeague($league);
        $this->setStartDateTime($season->getStartDateTime());
        $this->rankingRuleSet = RankingRuleSet::Against;
        $this->state = State::Created;
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

    public function getRankingRuleSet(): int
    {
        return $this->rankingRuleSet;
    }

    public function setRankingRuleSet(int $rankingRuleSet): void
    {
        $this->rankingRuleSet = $rankingRuleSet;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, Round\Number>|PersistentCollection<int|string, Round\Number>
     * @psalm-return ArrayCollection<int|string, Round\Number>
     */
    public function getRoundNumbers(): ArrayCollection|PersistentCollection
    {
        return $this->roundNumbers;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, Referee>|PersistentCollection<int|string, Referee>
     * @psalm-return ArrayCollection<int|string, Referee>
     */
    public function getReferees(): ArrayCollection|PersistentCollection
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
     * @phpstan-return ArrayCollection<int|string, TeamCompetitor>|PersistentCollection<int|string, TeamCompetitor>
     * @psalm-return ArrayCollection<int|string, TeamCompetitor>
     */
    public function getTeamCompetitors(): ArrayCollection|PersistentCollection
    {
        return $this->teamCompetitors;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, CompetitionSport>|PersistentCollection<int|string, CompetitionSport>
     * @psalm-return ArrayCollection<int|string, CompetitionSport>
     */
    public function getSports(): ArrayCollection|PersistentCollection
    {
        return $this->sports;
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
        return $this->sports->map(function (CompetitonSport $competitionSport): Sport {
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
}
