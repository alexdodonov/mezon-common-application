<?php
namespace Mezon\Application\Tests;

use PHPUnit\Framework\TestCase;
use Mezon\Conf\Conf;

/**
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class UnexistingActionMessageUnitTest extends TestCase
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
