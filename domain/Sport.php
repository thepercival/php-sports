<?php

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\Identifiable;

class Sport implements Identifiable
{
    const MIN_LENGTH_NAME = 3;
    const MAX_LENGTH_NAME = 30;
    const MIN_LENGTH_UNITNAME = 2;
    const MAX_LENGTH_UNITNAME = 20;

    const WARNING = 1;
    const SENDOFF = 2;

    /**
     * @var int|string
     */
    private $id;
    private string $name;
    /**
     * @var string
     */
    // private $scoreUnitName;
    /**
     * @var string
     */
    // private $scoreSubUnitName;
    /**
     * @var bool
     */
    private $team;
    /**
     * @var int|null
     */
    private $customId;

    public function __construct(string $name)
    {
        $this->setName($name);
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

//    public function getScoreUnitName(): string {
//        return $this->scoreUnitName;
//    }
//
//    public function setScoreUnitName(string $name): void {
//        $this->scoreUnitName = $name;
//    }
//
//    public function getScoreSubUnitName(): ?string {
//        return $this->scoreSubUnitName;
//    }
//
//    public function setScoreSubUnitName(string $name): void {
//        $this->scoreSubUnitName = $name;
//    }
//
//    public function hasScoreSubUnitName(): bool {
//        return $this->scoreSubUnitName === null;
//    }

    public function getTeam(): bool
    {
        return $this->team;
    }

    public function setTeam(bool $team): void
    {
        $this->team = $team;
    }

    public function getCustomId(): ?int
    {
        return $this->customId;
    }

    public function setCustomId(int $id = null): void
    {
        $this->customId = $id;
    }
}
