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

        $tableAlias = $source->getTableAlias();
        $userId = $this->get('security.context')->getToken()->getUser()->getId();

        $source->manipulateQuery(
            function ($query) use ($tableAlias, $userId)
            {
                $query->andWhere($tableAlias . '.user = :user');
                $query->setParameter('user', $userId);
            }
        );

        /* @var $grid \APY\DataGridBundle\Grid\Grid */
        $grid = $this->get('grid');

        $grid->setSource($source);
        $grid->setLimits(25);
        $grid->setNoResultMessage($this->get('translator')->trans('No data'));

        //custom colums config
        $grid->hideColumns('id');
        $grid->setDefaultOrder('id', 'DESC');

        /* @var $column \APY\DataGridBundle\Grid\Column\Column */
        $column = $grid->getColumn('name');
        $column->setOperators(array('like'));
        $column->setOperatorsVisible(false);
        $column->setDefaultOperator('like');
        $column->setSortable(false);
        $column->setTitle($this->get('translator')->trans('Experiment name', array(), 'ExperimentBundle'));

        $column = $grid->getColumn('status.experimentstatus');
        $column->setFilterType('select');
        $column->setOperators(array('like'));
        $column->setOperatorsVisible(false);
        $column->setDefaultOperator('like');
        $column->setSelectFrom('source');
        $column->setSortable(false);
        $column->setTitle($this->get('translator')->trans('Experiment status', array(), 'ExperimentBundle'));

        //add actions column
        $rowAction = new RowAction($this->get('translator')->trans('Edit'), 'edit_experiment');
        $rowAction->setRouteParameters(array('id'));
        $rowAction->setRouteParametersMapping(array('id' => 'id'));

        $rowAction2 = new RowAction($this->get('translator')->trans('Show'), 'see_experiment');
        $rowAction2->setRouteParameters(array('id'));
        $rowAction2->setRouteParametersMapping(array('id' => 'id'));

        $actionsColumn2 = new ActionsColumn('info_column', $this->get('translator')->trans('Actions'), array($rowAction2, $rowAction), "<br/>");
        $grid->addColumn($actionsColumn2);

        return $grid->getGridResponse('DamisExperimentBundle::ExperimentHistory\index.html.twig');
    }

}
