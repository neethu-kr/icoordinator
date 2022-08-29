<?php

namespace iCoordinator\Entity\CustomerSpecific\Norway;

use Carbon\Carbon;
use iCoordinator\Entity\AbstractEntity;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;

/**
 * @Entity
 * @Table(name="fdv_entries", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class FDVEntry extends AbstractEntity
{
    const ENTITY_NAME = 'entity:CustomerSpecific\Norway\FDVEntry';

    const RESOURCE_ID = 'fdventry';

    /**
     * @var Portal
     * @ManyToOne(targetEntity="iCoordinator\Entity\Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var Workspace
     * @ManyToOne(targetEntity="iCoordinator\Entity\Workspace")
     * @JoinColumn(name="workspace_id", referencedColumnName="id")
     */
    protected $workspace;

    /**
     * @var User
     * @ManyToOne(targetEntity="iCoordinator\Entity\User")
     * @JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $created_by;

    /**
     * @var string
     * @Column(type="string", length=15, nullable=true)
     */
    protected $selskapsnr;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $selskapsnavn;

    /**
     * @var string
     * @Column(type="string", length=15, nullable=true)
     */
    protected $gnrbnr;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $eiendomnavn;

    /**
     * @var string
     * @Column(type="string", length=15, nullable=true)
     */
    protected $bygningsnr;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $bygning;

    /**
     * @var string
     * @Column(type="string", length=15, nullable=true)
     */
    protected $bygningsdel;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $systemnavn;

    /**
     * @var string
     * @Column(type="string", length=15, nullable=true)
     */
    protected $systemtypenr;

    /**
     * @var string
     * @Column(type="string", length=15, nullable=true)
     */
    protected $komponentnr;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $komponentnavn;

    /**
     * @var string
     * @Column(type="string", length=15, nullable=true)
     */
    protected $komponenttypenr;

    /**
     * @var string
     * @Column(type="string", length=15, nullable=true)
     */
    protected $komponentkategorinr;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $fabrikat;

    /**
     * @var string
     * @Column(type="string", length=50, nullable=true)
     */
    protected $typebetegnelse;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $systemleverandor;

    /**
     * @var Carbon
     * @Column(type="date")
     */
    protected $installdato;

    /**
     * @var string
     * @Column(type="string", length=200, nullable=true)
     */
    protected $notat;
    /**
     * @var Carbon
     * @Column(type="date")
     */
    protected $garanti;

    /**
     * @var int
     * @Column(type="integer", nullable=true)
     */
    protected $antal_service_per_ar;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $tfm;


    public function __construct()
    {
    }
    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize()
    {
        return [
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'portal' => $this->getPortal()->jsonSerialize(true),
            'workspace' => $this->getWorkspace()->jsonSerialize(true),
            'created_by' => $this->getCreatedBy()->jsonSerialize(true),
            'selskapsnr' => $this->getSelskapsNr(),
            'selskapsnavn' => $this->getSelskapsNavn(),
            'gnrbnr' => $this->getGnrBnr(),
            'eiendomnavn' => $this->getEiendomNavn(),
            'bygningsnr' => $this->getBygningsNr(),
            'bygning' => $this->getBygning(),
            'bygningsdel' => $this->getBygningsdel(),
            'systemnavn' => $this->getSystemNavn(),
            'systemtypenr' => $this->getSystemTypeNr(),
            'komponentnr' => $this->getKomponentNr(),
            'komponentnavn' => $this->getKomponentNavn(),
            'komponenttypenr' => $this->getKomponentTypeNr(),
            'komponentkategorinr' => $this->getKomponentKategoriNr(),
            'fabrikat' => $this->getFabrikat(),
            'typebetegnelse' => $this->getTypeBetegnelse(),
            'systemleverandor' => $this->getSystemLeverandor(),
            'installdato' => ($this->getInstallDato()) ?
                $this->getInstallDato()->toDateString() : null,
            'garanti' => ($this->getGaranti()) ?
                $this->getGaranti()->toDateString() : null,
            'notat' => $this->getNotat(),
            'antal_service_per_ar' => $this->getAntalServicePerAr(),
            'tfm' => $this->getTfm()
        ];
    }

    public function getCSV()
    {
        return
            $this->getWorkspace()->getName() . "\t" .
            $this->getSelskapsNr() . "\t" .
            $this->getSelskapsNavn() . "\t" .
            $this->getGnrBnr() . "\t" .
            $this->getEiendomNavn() . "\t" .
            $this->getBygningsNr() . "\t" .
            $this->getBygning() . "\t" .
            $this->getBygningsdel() . "\t" .
            $this->getSystemNavn() . "\t" .
            $this->getSystemTypeNr() . "\t" .
            $this->getKomponentNr() . "\t" .
            $this->getKomponentNavn() . "\t" .
            $this->getKomponentTypeNr() . "\t" .
            $this->getKomponentKategoriNr() . "\t" .
            $this->getFabrikat() . "\t" .
            $this->getTypeBetegnelse() . "\t" .
            $this->getSystemLeverandor() . "\t" .
            (($this->getInstallDato()) ?
                $this->getInstallDato()->toDateString() : '') . "\t" .
            $this->getNotat() . "\t" .
            (($this->getGaranti()) ?
                $this->getGaranti()->toDateString() : '') . "\t" .
            $this->getAntalServicePerAr(). "\t" .
            $this->getTfm()
        ;
    }

    /**
     * @return Portal
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * @param $portal
     * @return $this
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
        return $this;
    }

    /**
     * @return \iCoordinator\Entity\Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @param $workspace
     * @returns File
     */
    public function setWorkspace($workspace)
    {
        $this->workspace = $workspace;
        return $this;
    }

    /**
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @param $created_by
     * @return $this
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    /**
     * @return string
     */
    public function getSelskapsNr()
    {
        return $this->selskapsnr;
    }

    /**
     * @param $selskapsnr
     * @return $this
     */
    public function setSelskapsNr($selskapsnr)
    {
        $this->selskapsnr = $selskapsnr;
        return $this;
    }

    /**
     * @return string
     */
    public function getSelskapsNavn()
    {
        return $this->selskapsnavn;
    }

    /**
     * @param $selskapsnavn
     * @return $this
     */
    public function setSelskapsNavn($selskapsnavn)
    {
        $this->selskapsnavn = $selskapsnavn;
        return $this;
    }

    /**
     * @return string
     */
    public function getGnrBnr()
    {
        return $this->gnrbnr;
    }

    /**
     * @param $gnrbnr
     * @return $this
     */
    public function setGnrBnr($gnrbnr)
    {
        $this->gnrbnr = $gnrbnr;
        return $this;
    }

    /**
     * @return string
     */
    public function getEiendomNavn()
    {
        return $this->eiendomnavn;
    }

    /**
     * @param $eiendomnavn
     * @return $this
     */
    public function setEiendomNavn($eiendomnavn)
    {
        $this->eiendomnavn = $eiendomnavn;
        return $this;
    }
    /**
     * @return string
     */
    public function getBygningsNr()
    {
        return $this->bygningsnr;
    }

    /**
     * @param $bygningsnr
     * @return $this
     */
    public function setBygningsNr($bygningsnr)
    {
        $this->bygningsnr = $bygningsnr;
        return $this;
    }
    /**
     * @return string
     */
    public function getBygning()
    {
        return $this->bygning;
    }

    /**
     * @param $bygning
     * @return $this
     */
    public function setBygning($bygning)
    {
        $this->bygning = $bygning;
        return $this;
    }

    /**
     * @return string
     */
    public function getBygningsdel()
    {
        return $this->bygningsdel;
    }

    /**
     * @param $bygningsdel
     * @return $this
     */
    public function setBygningsdel($bygningsdel)
    {
        $this->bygningsdel = $bygningsdel;
        return $this;
    }

    /**
     * @return string
     */
    public function getSystemNavn()
    {
        return $this->systemnavn;
    }

    /**
     * @param $systemnavn
     * @return $this
     */
    public function setSystemNavn($systemnavn)
    {
        $this->systemnavn = $systemnavn;
        return $this;
    }

    /**
     * @return string
     */
    public function getSystemTypeNr()
    {
        return $this->systemtypenr;
    }

    /**
     * @param $systemtypenr
     * @return $this
     */
    public function setSystemTypeNr($systemtypenr)
    {
        $this->systemtypenr = $systemtypenr;
        return $this;
    }

    /**
     * @return string
     */
    public function getKomponentNr()
    {
        return $this->komponentnr;
    }

    /**
     * @param $komponentnr
     * @return $this
     */
    public function setKomponentNr($komponentnr)
    {
        $this->komponentnr = $komponentnr;
        return $this;
    }
    /**
     * @return string
     */
    public function getKomponentNavn()
    {
        return $this->komponentnavn;
    }

    /**
     * @param $komponentnavn
     * @return $this
     */
    public function setKomponentNavn($komponentnavn)
    {
        $this->komponentnavn = $komponentnavn;
        return $this;
    }

    /**
     * @return string
     */
    public function getKomponentTypeNr()
    {
        return $this->komponenttypenr;
    }

    /**
     * @param $komponenttypenr
     * @return $this
     */
    public function setKomponentTypeNr($komponenttypenr)
    {
        $this->komponenttypenr = $komponenttypenr;
        return $this;
    }
    /**
     * @return string
     */
    public function getKomponentKategoriNr()
    {
        return $this->komponentkategorinr;
    }

    /**
     * @param $komponentkategorinr
     * @return $this
     */
    public function setKomponentKategoriNr($komponentkategorinr)
    {
        $this->komponentkategorinr = $komponentkategorinr;
        return $this;
    }

    /**
     * @return string
     */
    public function getFabrikat()
    {
        return $this->fabrikat;
    }

    /**
     * @param $fabrikat
     * @return $this
     */
    public function setFabrikat($fabrikat)
    {
        $this->fabrikat = $fabrikat;
        return $this;
    }
    /**
     * @return string
     */
    public function getTypeBetegnelse()
    {
        return $this->typebetegnelse;
    }

    /**
     * @param $typebetegnelse
     * @return $this
     */
    public function setTypeBetegnelse($typebetegnelse)
    {
        $this->typebetegnelse = $typebetegnelse;
        return $this;
    }

    /**
     * @return string
     */
    public function getSystemLeverandor()
    {
        return $this->systemleverandor;
    }

    /**
     * @param $systemleverandor
     * @return $this
     */
    public function setSystemLeverandor($systemleverandor)
    {
        $this->systemleverandor = $systemleverandor;
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getInstallDato()
    {
        return $this->installdato;
    }

    /**
     * @param $installdato
     * @return $this
     */
    public function setInstallDato($installdato)
    {
        $this->installdato = $installdato;
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getGaranti()
    {
        return $this->garanti;
    }

    /**
     * @param $garanti
     * @return $this
     */
    public function setGaranti($garanti)
    {
        $this->garanti = $garanti;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotat()
    {
        return $this->notat;
    }

    /**
     * @param $notat
     * @return $this
     */
    public function setNotat($notat)
    {
        $this->notat = $notat;
        return $this;
    }

    /**
     * @return int
     */
    public function getAntalServicePerAr()
    {
        return $this->antal_service_per_ar;
    }

    /**
     * @param int $antal_service_per_ar
     * @return File
     */
    public function setAntalServicePerAr($antal_service_per_ar)
    {
        $this->antal_service_per_ar = $antal_service_per_ar;
        return $this;
    }

    /**
     * @return string
     */
    public function getTfm()
    {
        return $this->tfm;
    }

    /**
     * @param $tfm
     * @return $this
     */
    public function setTfm($tfm)
    {
        $this->tfm = $tfm;
        return $this;
    }
}
