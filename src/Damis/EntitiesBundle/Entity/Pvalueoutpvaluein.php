<?php

namespace Damis\EntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pvalueoutpvaluein
 */
#[ORM\Table(name: 'pvalueoutpvaluein')]
#[ORM\Index(name: 'FK_PVALUEOUT_PARAMETERVALUE', columns: ['InParameterValueID'])]
#[ORM\Entity]
class Pvalueoutpvaluein
{
    /**
     * @var Parametervalue
     *
     *
     */
    #[ORM\JoinColumn(name: 'InParameterValueID', referencedColumnName: 'ParameterValueID', onDelete: 'CASCADE')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\OneToOne(targetEntity: Parametervalue::class)]
    private $inparametervalue;

    /**
     * @var Parametervalue
     */
    #[ORM\JoinColumn(name: 'OutParameterValueID', referencedColumnName: 'ParameterValueID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Parametervalue::class)]
    private $outparametervalue;

    /**
     * Set inparametervalue
     *
     * @param Parametervalue $inparametervalue
     * @return Pvalueoutpvaluein
     */
    public function setInparametervalue(Parametervalue $inparametervalue)
    {
        $this->inparametervalue = $inparametervalue;

        return $this;
    }

    /**
     * Get inparametervalue
     *
     * @return Parametervalue
     */
    public function getInparametervalue()
    {
        return $this->inparametervalue;
    }

    /**
     * Set outparametervalue
     *
     * @param Parametervalue $outparametervalue
     * @return Pvalueoutpvaluein
     */
    public function setOutparametervalue(Parametervalue $outparametervalue = null)
    {
        $this->outparametervalue = $outparametervalue;

        return $this;
    }

    /**
     * Get outparametervalue
     *
     * @return Parametervalue
     */
    public function getOutparametervalue()
    {
        return $this->outparametervalue;
    }
}
