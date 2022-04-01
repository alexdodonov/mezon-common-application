<?php
namespace Mezon\Application\Tests;

use PHPUnit\Framework\TestCase;
use Mezon\Tests\TestingView;
use Mezon\Conf\Conf;

/**
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ActionsUnitTest extends TestCase
{

    /**
     *
     * {@inheritdoc}
     * @see TestCase::setUp()
     * @psalm-suppress RedundantCondition
     */
    protected function setUp(): void
    {
        if (isset($_GET)) {
            unset($_GET['error-message']);
            unset($_GET['success-message']);
            unset($_GET['action-message']);
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';

        Conf::setConfigStringValue('headers/layer', 'mock');
    }

    /**
     * Asserting that page was generated
     *
     * @param string $result
     *            page generation result
     * @param string $layout
     *            substring to be found
     */
    public function assertCommonCall(string $result, string $layout): void
    {
        $this->assertStringContainsString('Some title', $result);
        $this->assertStringContainsString('Main From Config', $result);
        $this->assertStringContainsString($layout, $result);
    }

    /**
     * Data provider for the test testActionsJson
     *
     * @return array testing data
     */
    public function actionsJsonDataProvider(): array
    {
        return [
            // #0, default behaviour, layout is not set, extra view variable is set
            [
                'from-config',
                function (string $result) {
                    $this->assertCommonCall($result, '<!-- index1 -->');
                    $this->assertTrue(TestingPresenter::$actionPresenterFromConfigWasCalled);
                    $this->assertStringContainsString('someVarValue', $result);
                }
            ],
            // #1, default behaviour, layout is set, no name is defined for the other-view
            [
                'from-config2',
                function (string $result) {
                    $this->assertCommonCall($result, '<!-- index2 -->');
                    $this->assertTrue(TestingPresenter::$actionPresenterFromConfigWasCalled);
                    $this->assertTrue(TestingView::$defaultViewWasRendered);
                }
            ],
            // #2, inherit from from-config2
            [
                'from-config3',
                function (string $result) {
                    $this->assertCommonCall($result, '<!-- index1 -->');
                    $this->assertTrue(TestingPresenter::$actionPresenterFromConfigWasCalled);
                    $this->assertTrue(TestingView::$defaultViewWasRendered);
                }
            ],
            // #3, calling config wich is loaded from other dir
            [
                'from-config-from-other-dir',
                function (string $result) {
                    $this->assertCommonCall($result, '<!-- index1 -->');
                    $this->assertTrue(TestingPresenter3::$fromConfigFromOtherDirWasCalled);
                }
            ]

            // FromConfigFromOtherDir
        ];
    }

    /**
     * Running with actions
     *
     * @param string $page
     *            - loading page
     * @param callable $asserter
     *            asserting method
     * @dataProvider actionsJsonDataProvider
     */
    public function testActionsJson(string $page, callable $asserter): void
    {
        // setup
        $_GET['r'] = $page;
        $application = new TestCommonApplication();

        // test body
        ob_start();
        $application->run();
        $result = ob_get_flush();
        ob_clean();

        // assertions
        $asserter($result);
    }
}
