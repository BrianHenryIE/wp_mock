<?php
/**
 * Reimplements functions removed in PHPUnit 10
 */

namespace WP_Mock\Tools;

use Composer\InstalledVersions;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait TestCasePhpUnit9Compat {

    /**
     * TODO:
     * NB: The functionality of {@used-by TestCase::setUpContentFiltering} now has no effect on PHPUnit 10+. The value
     * is being saved also to {@see TestCase::$__contentFilterCallback} but the callback, {@see @see TestCase::stripTabsAndNewlines()}
     * is no longer run.
     *
     * @see \PHPUnit\Framework\TestCase::setOutputCallback
     *
     * @see https://github.com/sebastianbergmann/phpunit/issues/5319
     * @see https://github.com/PHPCSStandards/PHPCSDevTools/pull/162
     *
     * @see https://github.com/sebastianbergmann/phpunit/blob/945d0b7f346a084ce5549e95289962972c4272e5/src/Framework/TestCase.php#L1446-L1452
     * @see https://github.com/sebastianbergmann/phpunit/blob/10.5.60/src/Framework/TestCase.php
     *
     * @param callable $callback
     */
    public function setOutputCallback(callable $callback): void
    {
        if(method_exists(\PHPUnit\Framework\TestCase::class, 'setOutputCallback')) {
            /** @phpstan-ignore-next-line method exists */
            parent::setOutputCallback($callback);
        }
    }

    /**
     * Get the test case name.
     *
     * This is only called internally with `$withDataSet=false`, so we do not need to reimplement the associated logic.
     *
     * @see \PHPUnit\Framework\TestCase::__construct()
     * @see \PHPUnit\Framework\TestCase::getName()
     * @see \PHPUnit\Framework\TestCase::setName()
     * @see https://github.com/sebastianbergmann/phpunit/blob/945d0b7f346a084ce5549e95289962972c4272e5/src/Framework/TestCase.php#L1030-L1042
     * @see https://github.com/sebastianbergmann/phpunit/blob/f2e26f52f80ef77832e359205f216eeac00e320c/src/Framework/TestCase.php#L150-L153
     */
    public function getName(bool $withDataSet = true): string
    {
        if(method_exists(\PHPUnit\Framework\TestCase::class, 'getName')) {
            return parent::getName();
        }

        if($withDataSet===true){
            throw new RuntimeException('\PHPUnit\Framework\TestCase::getName() $withDataSet not implemented in ' . get_class($this));
        }

        $property = new ReflectionProperty(\PHPUnit\Framework\TestCase::class, 'name');
        if(!version_compare(PHP_VERSION, '8.5', '>=')){
            $property->setAccessible(true);
        }

        return $property->getValue($this);
    }

    /**
     *
     * @used-by TestCase::setUpContentFiltering()
     *
     * @see \PHPUnit\Util\Test::parseTestMethodAnnotations
     * @see \PHPUnit\Util\Annotation\DocBlock
     *
     * Copy and paste from the PHPUnit 9 implementation, but incompatible with 9 because the Registry class it uses
     * has moved from `PHPUnit\Util\Annotation\Registry` to `PHPUnit\Metadata\Annotation\Parser\Registry`.
     *
     * @see https://github.com/sebastianbergmann/phpunit/blob/945d0b7f346a084ce5549e95289962972c4272e5/src/Util/Test.php#L332-L354
     */
    protected function parseTestMethodAnnotations(string $className, ?string $methodName = null)
    {
        if ( method_exists(\PHPUnit\Util\Test::class, 'parseTestMethodAnnotations')) {
            return \PHPUnit\Util\Test::parseTestMethodAnnotations(
                $className,
                $methodName
            );
        }

        if(!class_exists(\PHPUnit\Metadata\Annotation\Parser\Registry::class)) {
            $phpunitVersion = InstalledVersions::getVersion('phpunit/phpunit');
            throw new RuntimeException('Expected class \PHPUnit\Metadata\Annotation\Parser\Registry missing, available in PHPUnit 10, found PHPUnit ' . $phpunitVersion);
        }

        $registry = \PHPUnit\Metadata\Annotation\Parser\Registry::getInstance();

        if ($methodName !== null) {
            try {
                return [
                    'method' => $registry->forMethod($className, $methodName)->symbolAnnotations(),
                    'class'  => $registry->forClassName($className)->symbolAnnotations(),
                ];
            } catch (\Exception $methodNotFound) {
                // ignored
            }
        }

        return [
            'method' => null,
            'class'  => $registry->forClassName($className)->symbolAnnotations(),
        ];
    }

    /**
     * PHPUnit's `TestCase::isInIsolation()` method was available in PHPUnit 9 but removed in PHPUnit 10. The instance
     * variable still exists as a private property.
     *
     * @see https://github.com/sebastianbergmann/phpunit/blob/945d0b7f346a084ce5549e95289962972c4272e5/src/Framework/TestCase.php#L1422-L1428
     * @see https://github.com/sebastianbergmann/phpunit/blob/f2e26f52f80ef77832e359205f216eeac00e320c/src/Framework/TestCase.php#L137
     */
    public function isInIsolation(): bool
    {
        if(method_exists(TestCase::class, 'isInisolation')) {
            /** @phpstan-ignore-next-line method exists */
            return parent::isInisolation();
        }

        $property = new ReflectionProperty(TestCase::class, 'inIsolation');
        if(!version_compare(PHP_VERSION, '8.5', '>=')){
            $property->setAccessible(true);
        }

        return (bool) $property->getValue($this);
    }
}
