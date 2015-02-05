<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new BCC\CronManagerBundle\BCCCronManagerBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Base\MainBundle\BaseMainBundle(),
            new Base\UserBundle\BaseUserBundle(),
            new Damis\EntitiesBundle\DamisEntitiesBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Damis\ExperimentBundle\DamisExperimentBundle(),
            new APY\DataGridBundle\APYDataGridBundle(),
            new Base\ConvertBundle\BaseConvertBundle(),
            new Base\StaticBundle\BaseStaticBundle(),
            new Damis\DatasetsBundle\DamisDatasetsBundle(),
            new Damis\AlgorithmBundle\DamisAlgorithmBundle(),
            new Base\LogBundle\BaseLogBundle(),
            new Iphp\FileStoreBundle\IphpFileStoreBundle(),
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
            new Bazinga\Bundle\JsTranslationBundle\BazingaJsTranslationBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
