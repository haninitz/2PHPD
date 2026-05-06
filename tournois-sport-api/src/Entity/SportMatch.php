<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'sport_match',
    indexes: [
        new ORM\Index(name: 'idx_match_tournament_status', columns: ['tournament_id', 'status']),
        new ORM\Index(name: 'idx_match_player1', columns: ['player1_id']),
        new ORM\Index(name: 'idx_match_player2', columns: ['player2_id']),
        new ORM\Index(name: 'idx_match_date', columns: ['match_date']),
    ]
)]
class SportMatch
{
    public const STATUS_EN_ATTENTE = 'en_attente';
    public const STATUS_EN_COURS = 'en_cours';
    public const STATUS_TERMINEE = 'terminee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class, inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?User $player1 = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?User $player2 = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private ?DateTimeImmutable $matchDate = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $scorePlayer1 = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $scorePlayer2 = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_EN_ATTENTE, self::STATUS_EN_COURS, self::STATUS_TERMINEE])]
    private string $status = self::STATUS_EN_ATTENTE;

    #[Assert\Expression(
        'this.getPlayer1() === null or this.getPlayer2() === null or this.getPlayer1() !== this.getPlayer2()',
        message: 'Les deux joueurs doivent etre differents.'
    )]
    public function isPlayersPairValid(): bool
    {
        return $this->player1 === null || $this->player2 === null || $this->player1 !== $this->player2;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPlayer1(): ?User
    {
        return $this->player1;
    }

    public function setPlayer1(User $player1): self
    {
        $this->player1 = $player1;

        return $this;
    }

    public function getPlayer2(): ?User
    {
        return $this->player2;
    }

    public function setPlayer2(User $player2): self
    {
        $this->player2 = $player2;

        return $this;
    }

    public function getMatchDate(): ?DateTimeImmutable
    {
        return $this->matchDate;
    }

    public function setMatchDate(DateTimeImmutable $matchDate): self
    {
        $this->matchDate = $matchDate;

        return $this;
    }

    public function getScorePlayer1(): ?int
    {
        return $this->scorePlayer1;
    }

    public function setScorePlayer1(?int $scorePlayer1): self
    {
        $this->scorePlayer1 = $scorePlayer1;

        return $this;
    }

    public function getScorePlayer2(): ?int
    {
        return $this->scorePlayer2;
    }

    public function setScorePlayer2(?int $scorePlayer2): self
    {
        $this->scorePlayer2 = $scorePlayer2;

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