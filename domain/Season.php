<?php
declare(strict_types=1);

namespace Sports;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use League\Period\Period;
use SportsHelpers\Identifiable;

class Season extends Identifiable
{
    private string $name;
    private DateTimeImmutable $startDateTime;
    private DateTimeImmutable $endDateTime;
    /**
     * @var Collection<int|string, Competition>
     */
    private Collection $competitions;

    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 9;

    public function __construct(string $name, Period $period)
    {
        $this->setName($name);
        $this->competitions = new ArrayCollection();
        $this->setPeriod($period);
    }

    public function getName(): string
    {
        return $this->name;
    }

    final public function setName(string $name): void
    {
        if (strlen($name) < self::MIN_LENGTH_NAME or strlen($name) > self::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam moet minimaal ".self::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".self::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }

//        if(preg_match('/[^0-9\s\/-]/i', $name)){
//            throw new \InvalidArgumentException( "de naam (".$name.") mag alleen cijfers, streepjes, slashes en spaties bevatten", E_ERROR );
//        }

        $this->name = $name;
    }

    public function getStartDateTime(): DateTimeImmutable
    {
        return $this->startDateTime;
    }

    final public function setStartDateTime(DateTimeImmutable $startDateTime): void
    {
        $this->startDateTime = $startDateTime;
    }

    public function getEndDateTime(): DateTimeImmutable
    {
        return $this->endDateTime;
    }

    final public function setEndDateTime(DateTimeImmutable $endDateTime): void
    {
        $this->endDateTime = $endDateTime;
    }

    public function getPeriod(): Period
    {
        return new Period($this->getStartDateTime(), $this->getEndDateTime());
    }

    final public function setPeriod(Period $period): void
    {
        $this->setStartDateTime($period->getStartDate());
        $this->setEndDateTime($period->getEndDate());
    }

    /**
     * @return Collection<int|string, Competition>
     */
    public function getCompetitions(): Collection
    {
        return $this->competitions;
    }
}
