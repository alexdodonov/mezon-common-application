<?php
namespace Mezon\Application\Tests;

use Mezon\Rest;
use PHPUnit\Framework\TestCase;

/**
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CommonApplicationUnitTest extends TestCase
{

    /**
     *
     * {@inheritdoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        // setup context
        $_SERVER['REQUEST_METHOD'] = 'GET';
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
     * Method asserts exception field
     *
     * @param string $output
     *            textual representation of the exception
     */
    protected function assertExceptionFields(string $output): void
    {
        $this->assertStringContainsString('"message"', $output);
        $this->assertStringContainsString('"code"', $output);
        $this->assertStringContainsString('"call_stack"', $output);
        $this->assertStringContainsString('"host"', $output);
    }

    /**
     * Testing handleException method
     */
    public function testHandleException(): void
    {
        // setup
        $application = new TestCommonApplication();
        $output = '';
        $e = new \Exception('', 0);

        // test body
        ob_start();
        $application->handleException($e);
        $output = ob_get_contents();
        ob_end_clean();

        // assertions
        $this->assertExceptionFields($output);
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

    /**
     * Testing exception throwing after invalid route handling
     */
    public function testRestException(): void
    {
        // setup and assertions
        $_GET['r'] = 'rest';
        $application = $this->getMockBuilder(TestCommonApplication::class)
            ->onlyMethods([
            'handleRestException'
        ])
            ->getMock();
        $application->expects($this->once())
            ->method('handleRestException');

        // test body
        $application->run();
    }

    /**
     * Testing exception wile action-message parsing
     */
    public function testUnexistingException(): void
    {
        // assertions
        $this->expectException(\Exception::class);

        // setup
        $_GET['action-message'] = 'unexisting-message';
        unset($_GET['error-message']);
        unset($_GET['success-message']);
        $application = new TestCommonApplication();

        // test body
        $application->result();
    }
}
