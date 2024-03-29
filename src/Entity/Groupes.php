<?php

namespace App\Entity;

use App\Repository\GroupesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupesRepository::class)]
class Groupes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private ?string $nom = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $acronyme = null;

    #[ORM\Column(length: 34)]
    private ?string $statut = null;

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'responsable_de')]
    private ?Employe $responsable = null;

    #[ORM\ManyToMany(targetEntity: Employe::class, inversedBy: 'adjoint_de')]
    private Collection $adjoints;

    #[ORM\ManyToMany(targetEntity: Employe::class, mappedBy: 'groupes_secondaires')]
    private Collection $employe_grp_secondaires;

    #[ORM\OneToMany(targetEntity: Employe::class, mappedBy: 'groupe_principal')]
    private Collection $employes_grp_principaux;

    #[ORM\ManyToOne(inversedBy: 'groupes')]
    private ?Departement $departement = null;

    #[ORM\OneToMany(targetEntity: Requetes::class, mappedBy: 'groupe_principal')]
    private Collection $requetes;

    public function __construct()
    {
        $this->adjoints = new ArrayCollection();
        $this->employe_grp_secondaires = new ArrayCollection();
        $this->employes_grp_principaux = new ArrayCollection();
        $this->requetes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getAcronyme(): ?string
    {
        return $this->acronyme;
    }

    public function setAcronyme(?string $acronyme): static
    {
        $this->acronyme = $acronyme;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * @return Collection<int, Employe>
     */
    public function getAdjoints(): Collection
    {
        return $this->adjoints;
    }

    public function addAdjoint(Employe $adjoint): static
    {
        if (!$this->adjoints->contains($adjoint)) {
            $this->adjoints->add($adjoint);
        }

        return $this;
    }

    public function removeAdjoint(Employe $adjoint): static
    {
        $this->adjoints->removeElement($adjoint);

        return $this;
    }

    /**
     * @return Collection<int, Employe>
     */
    public function getEmployeGrpSecondaires(): Collection
    {
        return $this->employe_grp_secondaires;
    }

    public function addEmployeGrpSecondaire(Employe $employeGrpSecondaire): static
    {
        if (!$this->employe_grp_secondaires->contains($employeGrpSecondaire)) {
            $this->employe_grp_secondaires->add($employeGrpSecondaire);
            $employeGrpSecondaire->addGroupesSecondaire($this);
        }

        return $this;
    }

    public function removeEmployeGrpSecondaire(Employe $employeGrpSecondaire): static
    {
        if ($this->employe_grp_secondaires->removeElement($employeGrpSecondaire)) {
            $employeGrpSecondaire->removeGroupesSecondaire($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Employe>
     */
    public function getEmployesGrpPrincipaux(): Collection
    {
        return $this->employes_grp_principaux;
    }

    public function addEmployesGrpPrincipaux(Employe $employesGrpPrincipaux): static
    {
        if (!$this->employes_grp_principaux->contains($employesGrpPrincipaux)) {
            $this->employes_grp_principaux->add($employesGrpPrincipaux);
            $employesGrpPrincipaux->setGroupePrincipal($this);
        }

        return $this;
    }

    public function removeEmployesGrpPrincipaux(Employe $employesGrpPrincipaux): static
    {
        if ($this->employes_grp_principaux->removeElement($employesGrpPrincipaux)) {
            // set the owning side to null (unless already changed)
            if ($employesGrpPrincipaux->getGroupePrincipal() === $this) {
                $employesGrpPrincipaux->setGroupePrincipal(null);
            }
        }

        return $this;
    }

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(?Departement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    /**
     * @return Collection<int, Requetes>
     */
    public function getRequetes(): Collection
    {
        return $this->requetes;
    }

    public function addRequete(Requetes $requete): static
    {
        if (!$this->requetes->contains($requete)) {
            $this->requetes->add($requete);
            $requete->setGroupePrincipal($this);
        }

        return $this;
    }

    public function removeRequete(Requetes $requete): static
    {
        if ($this->requetes->removeElement($requete)) {
            // set the owning side to null (unless already changed)
            if ($requete->getGroupePrincipal() === $this) {
                $requete->setGroupePrincipal(null);
            }
        }

        return $this;
    }

    public function getResponsable(): ?Employe
    {
        return $this->responsable;
    }

    public function setResponsable(?Employe $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }
}
