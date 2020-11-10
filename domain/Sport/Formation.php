<?php

namespace Sports\Sport;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Sport as SportBase;
use Sports\Round\Number as RoundNumber;

class Formation
{
    /**
     * @var int
     */
    protected $id;
    protected string $name;
    /**
     * @var SportBase
     */
    protected $sport;
    /**
     * @var ArrayCollection|Formation\Line[]
     */
    protected $lines;


    public function __construct(SportBase $sport, string $name)
    {
        $this->setSport($sport);
        $this->name = $name;
        $this->lines = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function getSport(): SportBase
    {
        return $this->sport;
    }

    protected function setSport(SportBase $sport)
    {
        if (!$sport->getFormations()->contains($this)) {
            $sport->getFormations()->add($this) ;
        }
        $this->sport = $sport;
    }

    public function getName(): string
    {
        return $this->name;
    }


    public function getLines()
    {
        return $this->lines;
    }
}
