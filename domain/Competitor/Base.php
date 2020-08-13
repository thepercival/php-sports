<?php

namespace Sports\Competitor;

trait Base
{
    protected $max_length_info = 200;

    protected bool $registered = false;
    /**
     * @var string|null
     */
    protected $info;
    /**
     * @var int
     */
    protected $pouleNr;
    /**
     * @var int
     */
    protected $placeNr;

    public function __construct()
    {
        $this->setRegistered(false);
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

    public function getRegistered(): bool
    {
        return $this->registered;
    }

    public function setRegistered(bool $registered)
    {
        $this->registered = $registered;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(string $info = null)
    {
        if (strlen($info) === 0) {
            $info = null;
        }

        if (strlen($info) > $this->max_length_info ) {
            throw new \InvalidArgumentException("de extra-info mag maximaal ".$this->max_length_info." karakters bevatten", E_ERROR);
        }
        $this->info = $info;
    }

    public function getPouleNr(): int
    {
        return $this->pouleNr;
    }

    public function setPouleNr(int $pouleNr): void
    {
        $this->pouleNr = $pouleNr;
    }

    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }

    public function setPlaceNr(int $placeNr): void
    {
        $this->placeNr = $placeNr;
    }
}
