<?php

namespace Damis\EntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pvalueoutpvaluein
 *
 * @ORM\Table(name="pvalueoutpvaluein", indexes={@ORM\Index(name="FK_PVALUEOUT_PARAMETERVALUE", columns={"OutParameterValueID"})})
 * @ORM\Entity
 */
class Pvalueoutpvaluein
{
    /**
     * @var \Damis\EntitiesBundle\Entity\Parametervalue
     *
     * @ORM\OneToOne(targetEntity="Damis\EntitiesBundle\Entity\Parametervalue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="InParameterValueID", referencedColumnName="ParameterValueID", onDelete="CASCADE")
     * })
     */
    private $inparametervalue;

    /**
     * @var \Damis\EntitiesBundle\Entity\Parametervalue
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @ORM\ManyToOne(targetEntity="Damis\EntitiesBundle\Entity\Parametervalue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="OutParameterValueID", referencedColumnName="ParameterValueID", onDelete="CASCADE")
     * })
     */
    private $outparametervalue;

    /**
     * Set inparametervalue
     *
     * @param \Damis\EntitiesBundle\Entity\Parametervalue $inparametervalue
     * @return Pvalueoutpvaluein
     */
    public function setInparametervalue(\Damis\EntitiesBundle\Entity\Parametervalue $inparametervalue)
    {
        $this->inparametervalue = $inparametervalue;

        return $this;
    }

    /**
     * Get inparametervalue
     *
     * @return \Damis\EntitiesBundle\Entity\Parametervalue
     */
    public function getInparametervalue()
    {
        return $this->inparametervalue;
    }

    /**
     * Set outparametervalue
     *
     * @param \Damis\EntitiesBundle\Entity\Parametervalue $outparametervalue
     * @return Pvalueoutpvaluein
     */
    public function setOutparametervalue(\Damis\EntitiesBundle\Entity\Parametervalue $outparametervalue = null)
    {
        $this->outparametervalue = $outparametervalue;

        return $this;
    }

    /**
     * Get outparametervalue
     *
     * @return \Damis\EntitiesBundle\Entity\Parametervalue
     */
    public function getOutparametervalue()
    {
        return $this->outparametervalue;
    }
}
