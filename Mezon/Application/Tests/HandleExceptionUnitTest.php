<?php
namespace Mezon\Application\Tests;

use PHPUnit\Framework\TestCase;

/**
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class HandleExceptionUnitTest extends TestCase
{

    /**
     * Method asserts exception field
     *
     * @param string $output
     *            textual representation of the exception
     */
    protected function assertExceptionFields(string $output): void
    {
        // TODO remove duplication with another unit-tests wich test exception
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
}
