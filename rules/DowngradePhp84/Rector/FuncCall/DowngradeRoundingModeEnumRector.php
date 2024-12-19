<?php

declare(strict_types=1);

namespace Rector\DowngradePhp84\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://wiki.php.net/rfc/new_without_parentheses
 *
 * @see \Rector\Tests\DowngradePhp84\Rector\MethodCall\DowngradeNewMethodCallWithoutParenthesesRector\DowngradeNewMethodCallWithoutParenthesesRectorTest
 */
final class DowngradeRoundingModeEnumRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Expr\FuncCall::class];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace RoundingMode enum to rounding mode constant in round()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
round(1.5, 0, RoundingMode::HalfAwayFromZero);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
round(1.5, 0, PHP_ROUND_HALF_UP);
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node, 'round')) {
            return null;
        }

        if ($node->isFirstClassCallable()) {
            return null;
        }

        $args = $node->getArgs();

        if (count($args) !== 3) {
            return null;
        }

        $modeArg = $args[2]->value;
        if ($modeArg instanceof ClassConstFetch) {
            if ($modeArg->class->name === 'RoundingMode') {
                $constantName = match ($modeArg->name->name) {
                    'HalfAwayFromZero' => 'PHP_ROUND_HALF_UP',
                    'HalfTowardsZero' => 'PHP_ROUND_HALF_DOWN',
                    'HalfEven' => 'PHP_ROUND_HALF_EVEN',
                    'HalfOdd' => 'PHP_ROUND_HALF_ODD',
                    default => null,
                };

                if ($constantName === null) {
                    return null;
                }

                $args[2]->value = new Node\Expr\ConstFetch(new Node\Name($constantName));
            }
        }

        return $node;
    }
}
