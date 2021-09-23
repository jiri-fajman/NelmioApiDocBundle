<?php


namespace Nelmio\ApiDocBundle\Model\Naming;

use \Symfony\Component\PropertyInfo\Type;

/**
 * Converts model fully qualified class name to a short name without namespace.
 * Example: 'App\\Model\\ExampleModel' => 'ExampleModel'
 */
final class DiscardNamespaceModelNamingStrategy implements ModelNamingStrategyInterface
{
    public function getTypeName(Type $type): string
    {
        $parts = explode('\\', $type->getClassName());

        return end($parts);
    }
}
