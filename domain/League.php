<?php
namespace Sports;

use \Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\Identifiable;

class League extends Identifiable
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $abbreviation;
    /**
     * @var ArrayCollection
     */
    protected $competitions;
    /**
     * @var Association
     */
    protected $association;

    const MIN_LENGTH_NAME = 3;
    const MAX_LENGTH_NAME = 60;
    const MAX_LENGTH_ABBREVIATION = 7;
    const MAX_LENGTH_SPORT = 30;

    public function __construct(Association $association, $name, $abbreviation = null)
    {
        $this->setAssociation($association);
        $this->setName($name);
        $this->setAbbreviation($abbreviation);
        $this->competitions = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name = null)
    {
        if (strlen($name) === 0) {
            throw new \InvalidArgumentException("de naam moet gezet zijn", E_ERROR);
        }

        if (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }

        $this->name = $name;
    }

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation = null)
    {
        if (strlen($abbreviation) === 0) {
            $abbreviation = null;
        }

        if (strlen($abbreviation) > static::MAX_LENGTH_ABBREVIATION) {
            throw new \InvalidArgumentException("de afkorting mag maximaal ".static::MAX_LENGTH_ABBREVIATION." karakters bevatten", E_ERROR);
        }
        $this->abbreviation = $abbreviation;
    }

    public function getAssociation(): Association
    {
        return $this->association;
    }

    protected function setAssociation(Association $association)
    {
        $leagues = $association->getLeagues();
        if ( !$leagues->contains($this)) {
            $leagues->add($this) ;
        }
        $this->association = $association;
    }

    /**
     * @return ArrayCollection|Competition[]
     */
    public function getCompetitions()
    {
        return $this->competitions;
    }

    public function getCompetition( Season $season ): ?Competition
    {
        $filtered =  $this->getCompetitions()->filter( function( Competition $competition) use ($season): bool {
            return $competition->getSeason() === $season;
        });
        return $filtered->count() === 0 ? null : $filtered->first();
    }
}
