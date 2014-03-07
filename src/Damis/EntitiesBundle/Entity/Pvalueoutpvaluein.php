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
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Damis\EntitiesBundle\Entity\Parametervalue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="InParameterValueID", referencedColumnName="ParameterValueID")
     * })
     */
    private $inparametervalueid;

    /**
     * @var \Damis\EntitiesBundle\Entity\Parametervalue
     *
     * @ORM\ManyToOne(targetEntity="Damis\EntitiesBundle\Entity\Parametervalue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="OutParameterValueID", referencedColumnName="ParameterValueID")
     * })
     */
    private $outparametervalueid;



    /**
     * Set inparametervalueid
     *
     * @param \Damis\EntitiesBundle\Entity\Parametervalue $inparametervalueid
     * @return Pvalueoutpvaluein
     */
    public function setInparametervalueid(\Damis\EntitiesBundle\Entity\Parametervalue $inparametervalueid)
    {
        $this->inparametervalueid = $inparametervalueid;

        return $this;
    }

    /**
     * Get inparametervalueid
     *
     * @return \Damis\EntitiesBundle\Entity\Parametervalue 
     */
    public function getInparametervalueid()
    {
        return $this->inparametervalueid;
    }

    /**
     * Set outparametervalueid
     *
     * @param \Damis\EntitiesBundle\Entity\Parametervalue $outparametervalueid
     * @return Pvalueoutpvaluein
     */
    public function setOutparametervalueid(\Damis\EntitiesBundle\Entity\Parametervalue $outparametervalueid = null)
    {
        $this->outparametervalueid = $outparametervalueid;

        return $this;
    }

    /**
     * Get outparametervalueid
     *
     * @return \Damis\EntitiesBundle\Entity\Parametervalue 
     */
    public function getOutparametervalueid()
    {
        return $this->outparametervalueid;
    }
}
