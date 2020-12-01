<?php

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use League\Period\Period;
use SportsHelpers\Identifiable;
use Sports\Team\Player;

class Person implements Identifiable
{
    /**
     * @var int|string
     */
    protected $id;
    protected string $firstName;
    /**
     * @var string|null
     */
    protected $nameInsertion;
    protected string $lastName;
    /**
     * @var \DateTimeImmutable|null
     */
    protected $dateOfBirth;
    /**
     * @var ArrayCollection|Player[]
     */
    protected $players;
    /**
     * @var string|null
     */
    protected $imageUrl;
    
    const MIN_LENGTH_FIRSTNAME = 2;
    const MAX_LENGTH_FIRSTNAME = 50;
    const MIN_LENGTH_NAMEINSERTION = 1;
    const MAX_LENGTH_NAMEINSERTION = 10;
    const MIN_LENGTH_LASTNAME = 2;
    const MAX_LENGTH_LASTNAME = 50;
    
    public function __construct(string $firstName, string $nameInsertion = null, string $lastName)
    {
        $this->setFirstName($firstName);
        $this->setNameInsertion($nameInsertion);
        $this->setLastName($lastName);
        $this->players = new ArrayCollection();
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

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName)
    {
        if (strlen($firstName) === 0) {
            throw new \InvalidArgumentException("de voornaam moet gezet zijn", E_ERROR);
        }

        if (strlen($firstName) < static::MIN_LENGTH_FIRSTNAME or strlen($firstName) > static::MAX_LENGTH_FIRSTNAME) {
            throw new \InvalidArgumentException("de voornaam moet minimaal ".static::MIN_LENGTH_FIRSTNAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_FIRSTNAME." karakters bevatten", E_ERROR);
        }
        $this->firstName = $firstName;
    }

    public function getNameInsertion(): ?string
    {
        return $this->nameInsertion;
    }

    public function setNameInsertion(string $nameInsertion = null)
    {
        if (strlen($nameInsertion) === 0) {
            $nameInsertion = null;
        }

        if (strlen($nameInsertion) > static::MAX_LENGTH_NAMEINSERTION) {
            throw new \InvalidArgumentException("het tussenvoegsel mag maximaal ".static::MAX_LENGTH_NAMEINSERTION." karakters bevatten", E_ERROR);
        }
        $this->nameInsertion = $nameInsertion;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName)
    {
        if (strlen($lastName) === 0) {
            throw new \InvalidArgumentException("de achternaam moet gezet zijn", E_ERROR);
        }

        if (strlen($lastName) < static::MIN_LENGTH_LASTNAME or strlen($lastName) > static::MAX_LENGTH_LASTNAME) {
            throw new \InvalidArgumentException("de achternaam moet minimaal ".static::MIN_LENGTH_LASTNAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_LASTNAME." karakters bevatten", E_ERROR);
        }
        $this->lastName = $lastName;
    }

    public function getName(): string {
        $name = $this->getFirstName();
        if( strlen( $this->getNameInsertion() ) > 0 ) {
            if( strlen( $name ) > 0 ) {
                $name .= " ";
            }
            $name .= $this->getNameInsertion();
        }
        if( strlen( $this->getLastName() ) > 0 ) {
            if( strlen( $name ) > 0 ) {
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

    public function setDateOfBirth(\DateTimeImmutable $dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * @return Player[] | Collection
     */
    public function getPlayers( Team $team = null, Period $period = null, int $line = null ): Collection
    {
        $filters = [];
        if( $team !== null ) {
            $filters[] = function ( Player $player ) use ($team): bool {
               return $player->getTeam() === $team;
            };
        }
        if( $period !== null ) {
            $filters[] = function ( Player $player ) use ($period): bool {
                return $player->getPeriod()->overlaps( $period );
            };
        }
        if( $line !== null ) {
            $filters[] = function ( Player $player ) use ($line): bool {
                return $player->getLine() === $line;
            };
        }
        if( count($filters) === 0 ) {
            return $this->players;
        }
        return $this->players->filter( function ( Player $player ) use ($filters): bool {
            foreach( $filters as $filter ) {
                if( !$filter( $player ) ) {
                    return false;
                }
            }
            return true;
        } );
    }

    public function getPlayer( Team $team, \DateTimeImmutable $dateTime = null ): ?Player
    {
        if( $dateTime === null) {
            $dateTime = new \DateTimeImmutable();
        }
        $filters = [
            function ( Player $player ) use ($team): bool {
                return $player->getTeam() === $team;
            },
            function ( Player $player ) use ($dateTime): bool {
                return $player->getPeriod()->contains( $dateTime );
            }
        ];
        $filteredPlayers = $this->players->filter( function ( Player $player ) use ($filters): bool {
            foreach( $filters as $filter ) {
                if( !$filter( $player ) ) {
                    return false;
                }
            }
            return true;
        } );
        return $filteredPlayers->count() > 0 ? $filteredPlayers->first() : null;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl = null)
    {
        if (strlen($imageUrl) === 0) {
            $imageUrl = null;
        }

        if (strlen($imageUrl) > Team::MAX_LENGTH_IMAGEURL) {
            throw new \InvalidArgumentException("de imageUrl mag maximaal ".Team::MAX_LENGTH_IMAGEURL." karakters bevatten", E_ERROR);
        }
        $this->imageUrl = $imageUrl;
    }
}
