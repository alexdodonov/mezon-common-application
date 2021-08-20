<?php
namespace Mezon\Application\Tests;

use Mezon\Application\View;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ResultUnitTest extends TestCase
{

    /**
     * Data provider
     *
     * @return array data provider
     */
    public function resultMethodDataProvider(): array
    {
        $presenter = new TestingPresenter(new View(), 'Result');
        return [
            [ // #0 testing presenter
                function (): TestCommonApplication {
                    unset($_GET['action-message']);
                    return new TestCommonApplication();
                },
                $presenter,
                function (array $params) {
                    $this->assertTrue($params[0]->wasCalled);
                }
            ],
            [ // #1 testing action message setup
                function (): TestCommonApplication {
                    unset($_GET['success-message']);
                    unset($_GET['error-message']);
                    $_GET['action-message'] = 'test-error';
                    return new TestCommonApplication();
                },
                $presenter,
                function (array $params): void {
                    $this->assertEquals('message', $params[1]->getTemplate()
                        ->getPageVar('action-message'));
                }
            ],
            [ // #2 testing error message setup
                function (): TestCommonApplication {
                    unset($_GET['action-message']);
                    unset($_GET['success-message']);
                    $_GET['error-message'] = 'test-error';
                    return new TestCommonApplication();
                },
                $presenter,
                function (array $params): void {
                    $this->assertEquals('message', $params[1]->getTemplate()
                        ->getPageVar('action-message'));
                }
            ],
            [ // #3 testing success message setup
                function (): TestCommonApplication {
                    unset($_GET['action-message']);
                    unset($_GET['error-message']);
                    $_GET['success-message'] = 'test-error';
                    return new TestCommonApplication();
                },
                $presenter,
                function (array $params): void {
                    $this->assertEquals('message', $params[1]->getTemplate()
                        ->getPageVar('action-message'));
                }
            ],
            [ // #4 no file with the messages
                function (): TestCommonApplication {
                    unset($_GET['success-message']);
                    unset($_GET['error-message']);
                    $_GET['action-message'] = 'test-error';
                    $application = new TestCommonApplication();
                    $application->hasMessages = false;
                    return $application;
                },
                $presenter,
                function (array $params): void {
                    $this->assertEquals('', $params[1]->getTemplate()
                        ->getPageVar('action-message'));
                }
            ]
        ];
    }

    /**
     * Testing result() method
     *
     * @param callable $setup
     *            setup of the test
     * @param object $handler
     *            controller or presenter
     * @param callable $assert
     *            asserter
     * @dataProvider resultMethodDataProvider
     */
    public function testResultMethod(callable $setup, object $handler, callable $assert = null): void
    {
        // setup
        $application = $setup();

        // test body
        $application->result($handler);

        // assertions
        if ($assert !== null) {
            $assert([
                $handler,
                $application
            ]);
        }
    }
}
