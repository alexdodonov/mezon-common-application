<?php
namespace Mezon\Application\Tests;

use PHPUnit\Framework\TestCase;
use Mezon\Conf\Conf;

/**
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CommonApplicationUnitTest extends TestCase
{

    /**
     *
     * {@inheritdoc}
     * @see TestCase::setUp()
     */
    protected function setUp(): void
    {
        // setup context
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Conf::setConfigStringValue('headers/layer', 'mock');
    }

    /**
     * Running with complex router result
     */
    public function testComplexRouteResult(): void
    {
        $application = new TestCommonApplication();

        $_GET['r'] = '/array-result/';

        ob_start();
        $application->run();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertTrue(strpos($output, 'Array result') !== false, 'Template compilation failed (1)');
        $this->assertTrue(strpos($output, 'Route main') !== false, 'Template compilation failed (2)');
    }

    /**
     * Compiling page with functional view
     */
    public function testComplexViewRenderring(): void
    {
        // setup
        $application = new TestCommonApplication();

        $_GET['r'] = '/view-result/';
        $_GET['redirect-to'] = 'redirectTo';

        // test body
        ob_start();
        $application->run();
        $output = ob_get_contents();
        ob_end_clean();

        // assertions
        $this->assertStringContainsString('Page title', $output);
        $this->assertStringContainsString('View rendered content', $output);
        $this->assertStringContainsString('redirectTo', $output);
    }

    /**
     * Testing exception throwing after invalid route handling
     */
    public function testInvalidRouteException(): void
    {
        // setup and assertions
        $_GET['r'] = 'invalid';
        $application = $this->getMockBuilder(TestCommonApplication::class)
            ->onlyMethods([
            'handleException'
        ])
            ->getMock();
        $application->expects($this->once())
            ->method('handleException');

        // test body
        $application->run();
    }
}
