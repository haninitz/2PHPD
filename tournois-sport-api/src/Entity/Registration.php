<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'registration',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_registration_player_tournament', columns: ['player_id', 'tournament_id'])],
    indexes: [
        new ORM\Index(name: 'idx_registration_tournament_status', columns: ['tournament_id', 'status']),
        new ORM\Index(name: 'idx_registration_player_status', columns: ['player_id', 'status']),
    ]
)]
#[UniqueEntity(fields: ['player', 'tournament'], message: 'Ce joueur est deja inscrit a ce tournoi.')]
class Registration
{
    public const STATUS_EN_ATTENTE = 'en_attente';
    public const STATUS_CONFIRMEE = 'confirmee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?User $player = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class, inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Tournament $tournament = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $registrationDate;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_EN_ATTENTE, self::STATUS_CONFIRMEE])]
    private string $status = self::STATUS_EN_ATTENTE;

    public function __construct()
    {
        $this->registrationDate = new DateTimeImmutable('today');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function setPlayer(User $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(Tournament $tournament): self
    {
        $this->tournament = $tournament;

        return $this;
    }

    public function getRegistrationDate(): DateTimeImmutable
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(DateTimeImmutable $registrationDate): self
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}