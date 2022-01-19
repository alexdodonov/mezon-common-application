<?php
namespace Mezon\Application\Tests;

use PHPUnit\Framework\TestCase;
use Mezon\View;

/**
 *
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
                    return new TestCommonApplication();
                },
                $presenter,
                function (array $params) {
                    $this->assertTrue(TestingPresenter::$wasCalled);
                }
            ],
            [ // #1 testing action message setup
                function (): TestCommonApplication {
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
                    $_GET['action-message'] = 'test-error';
                    $application = new TestCommonApplication();
                    $application->getTemplate()->setPaths([]);
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
     * @psalm-suppress RedundantCondition
     */
    public function testResultMethod(callable $setup, object $handler, callable $assert = null): void
    {
        // setup
        if (isset($_GET)) {
            unset($_GET['error-message']);
            unset($_GET['success-message']);
            unset($_GET['action-message']);
        }
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
