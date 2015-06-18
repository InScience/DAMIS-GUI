<?php

namespace Damis\ExperimentBundle\Grid\Column;

use APY\DataGridBundle\Grid\Column\Column;

class StatusColumn extends Column
{
    public function __initialize(array $params)
    {
        parent::__initialize($params);
    }

    public function getType()
    {
        return 'status';
    }
}
