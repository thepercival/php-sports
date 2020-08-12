<?php

namespace Sports;

use League\Period\Period;
use \Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\Identifiable;

class Season implements Identifiable
{
    /**
     * @var int|string
     */
    protected $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var \DateTimeImmutable
     */
    private $startDateTime;
    /**
     * @var \DateTimeImmutable
     */
    private $endDateTime;
    /**
     * @var ArrayCollection
     */
    private $competitions;

    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 9;

    public function __construct($name, Period $period)
    {
        $this->setName($name);
        $this->competitions = new ArrayCollection();
        $this->setPeriod($period);
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        if (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }

//        if(preg_match('/[^0-9\s\/-]/i', $name)){
//            throw new \InvalidArgumentException( "de naam (".$name.") mag alleen cijfers, streepjes, slashes en spaties bevatten", E_ERROR );
//        }

        $this->name = $name;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(\DateTimeImmutable $startDateTime)
    {
        $this->startDateTime = $startDateTime;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }

    public function setEndDateTime(\DateTimeImmutable $endDateTime)
    {
        $this->endDateTime = $endDateTime;
    }

    public function getPeriod()
    {
        return new Period($this->getStartDateTime(), $this->getEndDateTime());
    }

    public function setPeriod(Period $period)
    {
        $this->setStartDateTime($period->getStartDate());
        $this->setEndDateTime($period->getEndDate());
    }

    /**
     * @return ArrayCollection
     */
    public function getCompetitions()
    {
        return $this->competitions;
    }
}
