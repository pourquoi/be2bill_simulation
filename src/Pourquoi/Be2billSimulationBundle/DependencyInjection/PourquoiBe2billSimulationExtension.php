<?php

namespace Pourquoi\Be2billSimulationBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PourquoiBe2billSimulationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('be2bill.identifier', $config['identifier']);
        $container->setParameter('be2bill.password', $config['password']);
        $container->setParameter('be2bill.notification_url', $config['notification_url']);
        $container->setParameter('be2bill.template_url', $config['template_url']);
        if( !$config['template_mobile_url'] )
            $container->setParameter('be2bill.template_mobile_url', $config['template_url']);
        else
            $container->setParameter('be2bill.template_mobile_url', $config['template_mobile_url']);
        $container->setParameter('be2bill.return_url', $config['return_url']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
