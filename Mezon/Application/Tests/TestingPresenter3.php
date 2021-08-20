<?php
namespace Mezon\Application\Tests;

use Mezon\Application\Presenter;

/**
 * Presenter class for testing purposes
 *
 * @author Dodonov A.A.
 */
class TestingPresenter3 extends Presenter
{

    /**
     * Flag
     *
     * @var boolean
     */
    public static $fromConfigFromOtherDirWasCalled = false;

    public function presenterFromConfigFromOtherDir(): void
    {
        self::$fromConfigFromOtherDirWasCalled = true;
    }
}
