<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TokenRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Token
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $creation_date;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="tokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $customer;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $expiration_date;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $activate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreationDate(): ?string
    {
        return $this->creation_date;
    }

    public function setCreationDate(string $creation_date): self
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getExpirationDate(): ?string
    {
        return $this->expiration_date;
    }

    public function setExpirationDate(string $expiration_date): self
    {
        $this->expiration_date = $expiration_date;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreationDatePersist()
    {
        $date = new \DateTime("now", new \DateTimeZone('America/Caracas') );
        $this->creation_date = $date->format('Y-m-d H:i:s');
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getActivate(): ?string
    {
        return $this->activate;
    }

    public function setActivate(string $activate): self
    {
        $this->activate = $activate;

        return $this;
    }

}
