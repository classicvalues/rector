<?php declare(strict_types=1);

namespace Rector\Tests\Reconstructor\DependencyInjection;

use Rector\Reconstructor\DependencyInjection\InjectAnnotationToConstructorReconstructor;
use Rector\Testing\PHPUnit\AbstractReconstructorTestCase;

final class InjectAnnotationToConstructorReconstructorTest extends AbstractReconstructorTestCase
{
    public function test(): void
    {
        $this->doTestFileMatchesExpectedContent(__DIR__ . '/wrong/wrong.php.inc', __DIR__ . '/correct/correct.php.inc');
    }

    protected function getReconstructorClass(): string
    {
        return InjectAnnotationToConstructorReconstructor::class;
    }
}
