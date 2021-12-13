<?php
namespace Mezon\Application\Tests;

use PHPUnit\Framework\TestCase;

/**
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class HandleExceptionWithHostOutputUnitTest extends TestCase
{

    /**
     * Testing handleException method
     */
    public function testHandleExceptionWithHost(): void
    {
        // setup
        $application = new TestCommonApplication();
        $output = '';
        $_SERVER['HTTP_HOST'] = 'some host';
        $_SERVER['REQUEST_URI'] = '/some uri';

        // test body
        ob_start();
        $application->handleException(new \Exception('', 0));
        $output = ob_get_contents();
        ob_end_clean();

        // assertions
        $output = json_decode(str_replace('<pre>', '', $output), true);
        $this->assertEquals('some host/some uri', $output['host']);
    }

    /**
     * Testing handleException method without method
     * 
     * @psalm-suppress RedundantCondition
     */
    public function testHandleExceptionWithoutHost(): void
    {
        // setup
        $application = new TestCommonApplication();
        $output = '';

        if (isset($_SERVER)) {
            unset($_SERVER['HTTP_HOST']);
            unset($_SERVER['REQUEST_URI']);
        }

        // test body
        ob_start();
        $application->handleException(new \Exception('', 0));
        $output = ob_get_contents();
        ob_end_clean();

        // assertions
        $output = json_decode(str_replace('<pre>', '', $output), true);
        $this->assertEquals('undefined', $output['host']);
    }
}
