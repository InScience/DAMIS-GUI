<?php

namespace Damis\ExperimentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;

class ExperimentHistoryController extends Controller
{

    /**
     * Lists all User entities.
     *
     * @Route("experiments.html", name="experiments_history")
     */
    public function indexAction()
    {
        $source = new Entity('DamisExperimentBundle:Experiment');

        /* @var $grid \APY\DataGridBundle\Grid\Grid */
        $grid = $this->get('grid');

        $grid->setSource($source);
        $grid->setLimits(25);
        $grid->setNoResultMessage($this->get('translator')->trans('No data'));

        //custom colums config
        //$grid->hideColumns('id');

        //add actions column
//        $rowAction = new RowAction($this->get('translator')->trans('Edit'), 'user_edit');
//        $actionsColumn2 = new ActionsColumn('info_column', $this->get('translator')->trans('Actions'), array($rowAction), "<br/>");
//        $grid->addColumn($actionsColumn2);

        return $grid->getGridResponse('BaseUserBundle::User\index.html.twig');
    }

}
