<?php
declare(strict_types=1);

namespace noFlash\Owl\DependencyInjection\Compiler;

use noFlash\Owl\Message\MessageInterface;
use noFlash\Owl\Message\MessageSerializer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterMessageTypesPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $serializer = $container->findDefinition(MessageSerializer::class);

        foreach ($container->getDefinitions() as $definition) {
            $fqcn = $definition->getClass();

            $implements = @class_implements($fqcn, false); //Some classes are non-loadable and it will explode w/o @

            if ($fqcn === null || !$implements || !in_array(MessageInterface::class, $implements)) {
                continue;
            }

            $name = $fqcn::getName();
            $container->log($this, sprintf('Registering message "%s" with class "%s"', $name, $fqcn));
            $serializer->addMethodCall('addType', [$name, $fqcn]);
        }
    }
}
