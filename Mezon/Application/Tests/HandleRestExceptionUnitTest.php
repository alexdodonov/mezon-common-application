<?php
namespace Mezon\Application\Tests;

use Mezon\Rest;
use PHPUnit\Framework\TestCase;
use Mezon\Conf\Conf;

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
        Conf::setConfigStringValue('headers/layer', 'mock');
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
     * Testing handleRestException method
     */
    public function testHandleRestException(): void
    {
        // setup
        $application = new TestCommonApplication();
        $e = new Rest\Exception(
            '',
            0,
            200,
            '<th>( ! )</span>error!</th>
        <tr><td></td><td></td><td></td><td></td><td>Call 1:100</td>
        <tr><td></td><td></td><td></td><td></td><td>Call 2:200</td></table>');

        // test body
        ob_start();
        $application->handleRestException($e);
        $output = ob_get_contents();
        ob_end_clean();

        // assertions
        $this->assertExceptionFields($output);
        $this->assertStringContainsString('"http_body"', $output);
        $this->assertStringContainsString('error!', $output);
        $this->assertStringContainsString('Call 1 (100)', $output);
        $this->assertStringContainsString('Call 2 (200)', $output);
    }

    /**
     * Testing handleRestException method
     */
    public function testHandleRestExceptionForUndefinedFormat(): void
    {
        // setup
        $application = new TestCommonApplication();
        $e = new Rest\Exception('', 0, 200, '<b>Warning!</b> Some PHP warning');

        // test body
        ob_start();
        $application->handleRestException($e);
        $output = ob_get_contents();
        ob_end_clean();

        // assertions
        $this->assertExceptionFields($output);
        $this->assertStringContainsString('&lt;b&gt;Warning!&lt;\/b&gt; Some PHP warning', $output);
    }

    /**
     * Testing handleRestException method
     */
    public function testHandleRestExceptionForJsonFormat(): void
    {
        // setup
        $application = new TestCommonApplication();
        $e = new Rest\Exception('', 0, 200, '{"call 1": "method 1"}');

        // test body
        ob_start();
        $application->handleRestException($e);
        $output = ob_get_contents();
        ob_end_clean();

        // assertions
        $this->assertExceptionFields($output);
        $this->assertStringContainsString('"call 1": "method 1"', $output);
    }
}
