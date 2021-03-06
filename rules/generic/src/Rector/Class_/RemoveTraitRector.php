<?php

declare(strict_types=1);

namespace Rector\Generic\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;
use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use Rector\Core\PhpParser\Node\Manipulator\ClassManipulator;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\ConfiguredCodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\NodeTypeResolver\Node\AttributeKey;

/**
 * @see \Rector\Generic\Tests\Rector\Class_\RemoveTraitRector\RemoveTraitRectorTest
 */
final class RemoveTraitRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var string
     */
    public const TRAITS_TO_REMOVE = '$traitsToRemove';

    /**
     * @var bool
     */
    private $classHasChanged = false;

    /**
     * @var string[]
     */
    private $traitsToRemove = [];

    /**
     * @var ClassManipulator
     */
    private $classManipulator;

    public function __construct(ClassManipulator $classManipulator)
    {
        $this->classManipulator = $classManipulator;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Remove specific traits from code', [
            new ConfiguredCodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    use SomeTrait;
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class SomeClass
{
}
CODE_SAMPLE
,
                [
                    self::TRAITS_TO_REMOVE => ['TraitNameToRemove'],
                ]
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, Trait_::class];
    }

    /**
     * @param Class_|Trait_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $usedTraits = $this->classManipulator->getUsedTraits($node);
        if ($usedTraits === []) {
            return null;
        }

        $this->classHasChanged = false;
        $this->removeTraits($usedTraits);

        // invoke re-print
        if ($this->classHasChanged) {
            $node->setAttribute(AttributeKey::ORIGINAL_NODE, null);
        }

        return $node;
    }

    public function configure(array $configuration): void
    {
        $this->traitsToRemove = $configuration[self::TRAITS_TO_REMOVE] ?? [];
    }

    /**
     * @param Name[] $usedTraits
     */
    private function removeTraits(array $usedTraits): void
    {
        foreach ($usedTraits as $usedTrait) {
            foreach ($this->traitsToRemove as $traitToRemove) {
                if ($this->isName($usedTrait, $traitToRemove)) {
                    $this->removeNode($usedTrait);
                    $this->classHasChanged = true;
                    continue 2;
                }
            }
        }
    }
}
