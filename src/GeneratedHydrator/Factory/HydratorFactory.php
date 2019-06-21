<?php

declare(strict_types=1);

namespace GeneratedHydrator\Factory;

use CodeGenerationUtils\Exception\InvalidGeneratedClassesDirectoryException;
use CodeGenerationUtils\Visitor\ClassRenamerVisitor;
use GeneratedHydrator\Configuration;
use PhpParser\NodeTraverser;
use ReflectionClass;
use function class_exists;

/**
 * Factory responsible of producing hydrators
 */
class HydratorFactory
{
    /** @var Configuration */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = clone $configuration;
    }

    /**
     * Retrieves the generated hydrator FQCN
     *
     * @throws InvalidGeneratedClassesDirectoryException
     */
    public function getHydratorClass(bool $force = false) : string
    {
        $inflector         = $this->configuration->getClassNameInflector();
        $realClassName     = $inflector->getUserClassName($this->configuration->getHydratedClassName());
        $hydratorClassName = $inflector->getGeneratedClassName($realClassName, ['factory' => static::class]);

        if (($force || !class_exists($hydratorClassName)) && $this->configuration->doesAutoGenerateProxies()) {
            $generator     = $this->configuration->getHydratorGenerator();
            $originalClass = new ReflectionClass($realClassName);
            $generatedAst  = $generator->generate($originalClass);
            $traverser     = new NodeTraverser();

            $traverser->addVisitor(new ClassRenamerVisitor($originalClass, $hydratorClassName));

            $this->configuration->getGeneratorStrategy()->generate($traverser->traverse($generatedAst));
            $this->configuration->getGeneratedClassAutoloader()->__invoke($hydratorClassName);
        }

        return $hydratorClassName;
    }
}
