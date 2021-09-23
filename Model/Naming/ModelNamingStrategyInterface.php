<?php

namespace Nelmio\ApiDocBundle\Model\Naming;

use Symfony\Component\PropertyInfo\Type;

interface ModelNamingStrategyInterface
{
    public function getTypeName(Type $type): string;
}
