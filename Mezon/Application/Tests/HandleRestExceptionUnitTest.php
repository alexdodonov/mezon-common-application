<?php
namespace Mezon\Application\Tests;

use Mezon\Rest;
use PHPUnit\Framework\TestCase;

/**
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class HandleRestExceptionUnitTest extends TestCase
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
    }

    /**
     * Method asserts exception field
     *
     * @param string $output
     *            textual representation of the exception
     */
    protected function assertExceptionFields(string $output): void
    {
        // TODO remove duplication with CommonApplicationUnitTest.php
        $this->assertStringContainsString('"message"', $output);
        $this->assertStringContainsString('"code"', $output);
        $this->assertStringContainsString('"call_stack"', $output);
        $this->assertStringContainsString('"host"', $output);
    }

    /**
     * Testing handle_rest_exception method
     */
    public function testHandleRestException(): void
    {
        // setup
        $application = new TestCommonApplication();

        $e = new Rest\Exception('', 0, 200, '<th>( ! )</span>error!</th>');
        // test body
        ob_start();
        $application->handleRestException($e);
        $output = ob_get_contents();
        ob_end_clean();

        // assertions
        $this->assertExceptionFields($output);
        $this->assertStringContainsString('"http_body"', $output);
        $this->assertStringContainsString('error!', $output);
    }
}
