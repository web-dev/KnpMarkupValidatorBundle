<?php

namespace Knplabs\MarkupValidatorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Markup validator extension
 *
 * In your config.yml:
 *
 *      markup_validator:
 *          default_validator:  tidy
 *          validators:
 *              tidy:
 *                  processor:  tidy
 *              w3c:
 *                  processor:  w3c
 *
 * The extension creates a service for each defined validator. The service is
 * named as bellow:
 *
 *      markup_validator.{{ name }}_validator
 *
 * The "default_validator" key indicates for which of the defined validadators
 * the "markup_validator" service alias will be created.
 *
 * To register a processor, you simply need to define a service for a class
 * implementing the Knplabs\MarkupValidatorBundle\Validation\ProcessorInterface
 * with the tag "markup_validator.processor" and the wanted alias. The
 * extension will create a service for each validator named as below:
 *
 *      markup_validator.{{ alias }}_processor
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class MarkupValidatorExtension extends Extension
{
    /**
     * Load configuration
     *
     * @param  array            $configs
     * @param  ContainerBuilder $container
     */
    public function configLoad(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, __DIR__.'/../Resources/config');

        $configs = $this->mergeConfigs($configs);

        $default_validator = $configs['default_validator'];
        $validators = $configs['validators'];

        foreach ($validators as $name => $options) {
            if (!isset($options['processor'])) {
                throw new \InvalidArgumentException(sprintf('You must define a processor for the \'%s\' validator.', $name));
            }

            $validatorDef = new Definition($container->getParameter('markup_validator.validator.class'));
            $validatorDef->addArgument(new Reference(sprintf('markup_validator.%s_processor', $options['processor'])));

            $container->setDefinition(sprintf('markup_validator.%s_validator', $name), $validatorDef);
        }

        // create alias for the default validator
        if (!empty($default_validator)) {
            if (!isset($validators[$default_validator])) {
                throw new \InvalidArgumentException(sprintf('Invalid default validator: there is no \'%s\' validator defined.', $default_validator));
            }

            $container->setAlias('default_validator', sprintf('markup_validator.%s_validator', $default_validator));
        }
    }

    /**
     * Merges the given configs
     *
     * @param  array $configs
     */
    protected function mergeConfigs(array $configs)
    {
        $mergedConfigs = array(
            'default_validator' => null
        );

        foreach ($configs as $config) {
            if (isset($config['default-validator'])) {
                $mergedConfigs['default_validator'] = $config['default-validator'];
            } else if (isset($config['default_validator'])) {
                $mergedConfigs['default_validator'] = $config['default_validator'];
            }

            if (isset($config['validators']) && is_array($config['validators'])) {
                foreach ($config['validators'] as $name => $validator) {
                    if (isset($mergedConfigs['validators'][$name])) {
                        $mergedConfigs['validators'][$name] = array_merge($mergedConfigs['validators'][$name], $validator);
                    } else {
                        $mergedConfigs['validators'][$name] = $validator;
                    }
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/menu';
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return 'markup_validator';
    }
}