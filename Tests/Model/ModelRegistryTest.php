<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Model;

use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\Model\Naming\DiscardNamespaceModelNamingStrategy;
use OpenApi\Annotations as OA;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;

class ModelRegistryTest extends TestCase
{
    public function testNameAliasingNotAppliedForCollections()
    {
        $alternativeNames = [
            'Foo1' => [
                'type' => self::class,
                'groups' => ['group1'],
            ],
        ];
        $registry = new ModelRegistry([], new OA\OpenApi([]), new DiscardNamespaceModelNamingStrategy(), $alternativeNames);
        $type = new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true);

        $this->assertEquals('#/components/schemas/array', $registry->register(new Model($type, ['group1'])));
    }

    public function testNameCollisionsAreLogged()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'Can not assign a name for the model, the name "ModelRegistryTest" has already been taken.', [
                'model' => [
                    'type' => [
                        'class' => 'Nelmio\\ApiDocBundle\\Tests\\Model\\ModelRegistryTest',
                        'built_in_type' => 'object',
                        'nullable' => false,
                        'collection' => false,
                        'collection_key_types' => null,
                        'collection_value_types' => null,
                    ],
                    'options' => null,
                    'groups' => ['group2'],
                ],
                'taken_by' => [
                    'type' => [
                        'class' => 'Nelmio\\ApiDocBundle\\Tests\\Model\\ModelRegistryTest',
                        'built_in_type' => 'object',
                        'nullable' => false,
                        'collection' => false,
                        'collection_key_types' => null,
                        'collection_value_types' => null,
                    ],
                    'options' => null,
                    'groups' => ['group1'],
                ],
            ]);

        $registry = new ModelRegistry([], new OA\OpenApi([]), new DiscardNamespaceModelNamingStrategy(), []);
        $registry->setLogger($logger);

        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, self::class);
        $registry->register(new Model($type, ['group1']));
        $registry->register(new Model($type, ['group2']));
    }

    public function testNameCollisionsAreLoggedWithAlternativeNames()
    {
        $ref = new \ReflectionClass(self::class);
        $alternativeNames = [
            $ref->getShortName() => [
                'type' => $ref->getName(),
                'groups' => ['group1'],
            ],
        ];
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'Can not assign a name for the model, the name "ModelRegistryTest" has already been taken.', [
                'model' => [
                    'type' => [
                        'class' => 'Nelmio\\ApiDocBundle\\Tests\\Model\\ModelRegistryTest',
                        'built_in_type' => 'object',
                        'nullable' => false,
                        'collection' => false,
                        'collection_key_types' => null,
                        'collection_value_types' => null,
                    ],
                    'options' => null,
                    'groups' => ['group2'],
                ],
                'taken_by' => [
                    'type' => [
                        'class' => 'Nelmio\\ApiDocBundle\\Tests\\Model\\ModelRegistryTest',
                        'built_in_type' => 'object',
                        'nullable' => false,
                        'collection' => false,
                        'collection_key_types' => null,
                        'collection_value_types' => null,
                    ],
                    'options' => null,
                    'groups' => ['group1'],
                ],
            ]);

        $registry = new ModelRegistry([], new OA\OpenApi([]), new DiscardNamespaceModelNamingStrategy(), $alternativeNames);
        $registry->setLogger($logger);

        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, self::class);
        $registry->register(new Model($type, ['group2']));
    }

    /**
     * @dataProvider getNameAlternatives
     *
     * @param $expected
     */
    public function testNameAliasingForObjects(string $expected, $groups, array $alternativeNames)
    {
        $registry = new ModelRegistry([], new OA\OpenApi([]), new DiscardNamespaceModelNamingStrategy(), $alternativeNames);
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, self::class);

        $this->assertEquals($expected, $registry->register(new Model($type, $groups)));
    }

    public function getNameAlternatives()
    {
        return [
            [
                '#/components/schemas/ModelRegistryTest',
                null,
                [
                    'Foo1' => [
                        'type' => self::class,
                        'groups' => ['group1'],
                    ],
                ],
            ],
            [
                '#/components/schemas/Foo1',
                ['group1'],
                [
                    'Foo1' => [
                        'type' => self::class,
                        'groups' => ['group1'],
                    ],
                ],
            ],
            [
                '#/components/schemas/Foo1',
                ['group1', 'group2'],
                [
                    'Foo1' => [
                        'type' => self::class,
                        'groups' => ['group1', 'group2'],
                    ],
                ],
            ],
            [
                '#/components/schemas/ModelRegistryTest',
                null,
                [
                    'Foo1' => [
                        'type' => self::class,
                        'groups' => [],
                    ],
                ],
            ],
            [
                '#/components/schemas/Foo1',
                [],
                [
                    'Foo1' => [
                        'type' => self::class,
                        'groups' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider unsupportedTypesProvider
     */
    public function testUnsupportedTypeException(Type $type, string $stringType)
    {
        $this->expectException('\LogicException');
        $this->expectExceptionMessage(sprintf('Schema of type "%s" can\'t be generated, no describer supports it.', $stringType));

        $registry = new ModelRegistry([], new OA\OpenApi([]), new DiscardNamespaceModelNamingStrategy());
        $registry->register(new Model($type));
        $registry->registerSchemas();
    }

    public function unsupportedTypesProvider()
    {
        return [
            [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true), 'mixed[]'],
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, self::class), self::class],
        ];
    }
}
