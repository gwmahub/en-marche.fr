<?php

namespace App\Entity\VotingPlatform;

use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;
use App\Entity\Committee;
use App\Entity\TerritorialCouncil\TerritorialCouncil;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="voting_platform_election_entity")
 *
 * @Algolia\Index(autoIndex=false)
 */
class ElectionEntity
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Committee|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Committee", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $committee;

    /**
     * @var TerritorialCouncil|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TerritorialCouncil\TerritorialCouncil", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $territorialCouncil;

    /**
     * @var Election
     *
     * @ORM\OneToOne(targetEntity="App\Entity\VotingPlatform\Election", inversedBy="electionEntity")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $election;

    public function __construct(Committee $committee = null, TerritorialCouncil $council = null)
    {
        $this->committee = $committee;
        $this->territorialCouncil = $council;
    }

    public function setElection(Election $election): void
    {
        $this->election = $election;
    }

    public function getCommittee(): ?Committee
    {
        return $this->committee;
    }

    public function getTerritorialCouncil(): ?TerritorialCouncil
    {
        return $this->territorialCouncil;
    }
}
