<?php
namespace Mezon\Application\Tests;

use PHPUnit\Framework\TestCase;
use Mezon\Conf\Conf;

/**
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RestExceptionUnitTest extends TestCase
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
}
