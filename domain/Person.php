<?php

declare(strict_types=1);

namespace Sports;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use League\Period\Period;
use Sports\Team\Player;
use SportsHelpers\Identifiable;

/**
 * @api
 */
class Person extends Identifiable
{
    protected string $firstName;
    protected string|null $nameInsertion;
    protected string $lastName;
    protected DateTimeImmutable|null $dateOfBirth = null;
    /**
     * @var Collection<int|string, Player>
     */
    protected Collection $players;

    public const int MIN_LENGTH_FIRSTNAME = 2;
    public const int MAX_LENGTH_FIRSTNAME = 50;
    public const int MIN_LENGTH_NAMEINSERTION = 1;
    public const int MAX_LENGTH_NAMEINSERTION = 10;
    public const int MIN_LENGTH_LASTNAME = 2;
    public const int MAX_LENGTH_LASTNAME = 50;

    public function __construct(string $firstName, string|null $nameInsertion, string $lastName)
    {
        $this->setFirstName($firstName);
        $this->setNameInsertion($nameInsertion);
        $this->setLastName($lastName);
        $this->players = new ArrayCollection();
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string|null $firstName = null): void
    {
        if ($firstName === null || strlen($firstName) === 0) {
            throw new \InvalidArgumentException("de voornaam moet gezet zijn", E_ERROR);
        }

        if (strlen($firstName) < self::MIN_LENGTH_FIRSTNAME or strlen($firstName) > self::MAX_LENGTH_FIRSTNAME) {
            throw new \InvalidArgumentException("de voornaam moet minimaal ".self::MIN_LENGTH_FIRSTNAME." karakters bevatten en mag maximaal ".self::MAX_LENGTH_FIRSTNAME." karakters bevatten", E_ERROR);
        }
        $this->firstName = $firstName;
    }

    public function getNameInsertion(): string|null
    {
        return $this->nameInsertion;
    }

    public function setNameInsertion(string|null $nameInsertion): void
    {
        if ($nameInsertion !== null && strlen($nameInsertion) === 0) {
            $nameInsertion = null;
        }

        if ($nameInsertion !== null && strlen($nameInsertion) > self::MAX_LENGTH_NAMEINSERTION) {
            throw new \InvalidArgumentException("het tussenvoegsel mag maximaal ".self::MAX_LENGTH_NAMEINSERTION." karakters bevatten", E_ERROR);
        }
        $this->nameInsertion = $nameInsertion;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        if (strlen($lastName) === 0) {
            throw new \InvalidArgumentException("de achternaam moet gezet zijn", E_ERROR);
        }

        if (strlen($lastName) < self::MIN_LENGTH_LASTNAME or strlen($lastName) > self::MAX_LENGTH_LASTNAME) {
            throw new \InvalidArgumentException("de achternaam moet minimaal ".self::MIN_LENGTH_LASTNAME." karakters bevatten en mag maximaal ".self::MAX_LENGTH_LASTNAME." karakters bevatten", E_ERROR);
        }
        $this->lastName = $lastName;
    }

    public function getName(bool $firstNameAbbr = false): string
    {
        $name = $this->getFirstName();
        if( $firstNameAbbr ) {
            $name = strtoupper(mb_substr($name,0, 1));
        }
        $nameInsertion = $this->getNameInsertion();
        if ($nameInsertion !== null) {
            if (strlen($name) > 0) {
                $name .= " ";
            }
            $name .= $nameInsertion;
        }
        if (strlen($this->getLastName()) > 0) {
            if (strlen($name) > 0) {
                $name .= " ";
            }
            $name .= $this->getLastName();
        }
        return $name;
    }

    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(\DateTimeImmutable $dateOfBirth): void
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * @param Team|null $team
     * @param Period|null $period
     * @param int|null $line
     * @return Collection<int|string, Player>
     */
    public function getPlayers(
        Team|null $team = null,
        Period|null $period = null,
        int|null $line = null
    ): Collection {
        $filters = [];
        if ($team !== null) {
            $filters[] = function (Player $player) use ($team): bool {
                return $player->getTeam() === $team;
            };
        }
        if ($period !== null) {
            $filters[] = function (Player $player) use ($period): bool {
                return $player->getPeriod()->overlaps($period);
            };
        }
        if ($line !== null) {
            $filters[] = function (Player $player) use ($line): bool {
                return $player->getLine() === $line;
            };
        }
        if (count($filters) === 0) {
            return $this->players;
        }
        return $this->players->filter(function (Player $player) use ($filters): bool {
            foreach ($filters as $filter) {
                if (!$filter($player)) {
                    return false;
                }
            }
            return true;
        });
    }

    public function getPlayer(Team $team, \DateTimeImmutable|null $dateTime = null): Player|null
    {
        if ($dateTime === null) {
            $dateTime = new \DateTimeImmutable();
        }
        $filters = [
            function (Player $player) use ($team): bool {
                return $player->getTeam() === $team;
            },
            function (Player $player) use ($dateTime): bool {
                return $player->getPeriod()->contains($dateTime);
            }
        ];
        $filteredPlayers = $this->players->filter(function (Player $player) use ($filters): bool {
            foreach ($filters as $filter) {
                if (!$filter($player)) {
                    return false;
                }
            }
            return true;
        });
        $filteredPlayer = $filteredPlayers->first();
        return $filteredPlayer !== false ? $filteredPlayer : null;
    }
}
