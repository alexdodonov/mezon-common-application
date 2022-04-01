<?php
namespace Mezon\Application;

use Mezon\HtmlTemplate\HtmlTemplate;
use Mezon\Router\Utils;
use Mezon\Functional\Fetcher;

class ActionBuilder
{

    /**
     * List of loaded actions
     *
     * @var array
     */
    private static $loadedActions = [];

    /**
     * Ignore config key
     *
     * @return callable factory method
     */
    private static function ignoreKey(): callable
    {
        return function (): void {
            // do nothing
        };
    }

    /**
     * Method resets layout
     *
     * @param HtmlTemplate $template
     *            template
     * @param string $value
     *            new layout
     * @return callable factory method
     */
    private static function resetLayout(HtmlTemplate $template, string $value): callable
    {
        return function () use ($template, $value) {
            $template->resetLayout($value);
        };
    }

    /**
     * Method returns list of
     *
     * @param string $name
     *            action name
     * @return array|object action config
     */
    private static function getLoadedActionByName(string $name)
    {
        $name = basename($name, '.json');

        foreach (static::$loadedActions as $actionName => $action) {
            if ($actionName === $name) {
                return $action;
            }
        }

        throw (new \Exception('Overriding action with name "' . $name . '" was not found', - 1));
    }

    /**
     * Overriding defined config
     *
     * @param array $config
     *            config itself
     */
    private static function constructOverrideHandler(&$config): void
    {
        if (isset($config['override'])) {
            $baseConfig = static::getLoadedActionByName($config['override']);

            $config = array_merge($baseConfig, $config);
        }
    }

    /**
     * Method returns fabric method for action processing
     *
     * @param CommonApplication $app
     *            application
     * @param string $key
     *            config key name
     * @param mixed $value
     *            config key value
     * @return callable|NULL callback
     */
    private static function getActionBuilderMethod(CommonApplication $app, string $key, $value): ?callable
    {
        if ($key === 'override') {
            return ActionBuilder::ignoreKey();
        } elseif ($key === 'layout') {
            return ActionBuilder::resetLayout($app->getTemplate(), $value);
        }

        return null;
    }

    /**
     * Constructing view
     *
     * @param CommonApplication $app
     *            application
     * @param array $result
     *            compiled result
     * @param string $key
     *            config key
     * @param mixed $value
     *            config value
     * @param array $views
     *            list of views
     */
    private static function constructOtherView(
        CommonApplication $app,
        array &$result,
        string $key,
        $value,
        array &$views): void
    {
        // any other view
        if (isset($value['name'])) {
            $views[$key] = new $value['class']($app->getTemplate(), $value['name']);
        } else {
            $views[$key] = new $value['class']($app->getTemplate());
        }

        foreach ($value as $configKey => $configValue) {
            if (! in_array($configKey, [
                'class',
                'name',
                'placeholder'
            ])) {
                $views[$key]->setViewParameter($configKey, $configValue, true);
            }
        }

        $result[$value['placeholder']] = $views[$key];
    }

    /**
     * Method returns action body
     *
     * @param CommonApplication $app
     *            application object
     * @param
     *            object|array settings object
     * @return array ($result, $presenter)
     */
    private static function getActionBodyFromSettingsObject(CommonApplication $app, $settings): array
    {
        $result = [];
        $presenter = null;
        $views = [];

        ActionBuilder::constructOverrideHandler($settings);

        foreach ($settings as $key => $value) {
            $callback = ActionBuilder::getActionBuilderMethod($app, $key, $value);

            if ($callback !== null) {
                $callback();
            } elseif (is_string($value)) {
                // string content
                $result[$key] = $value;
            } elseif ($key === 'presenter') {
                $presenter = new $value['class'](
                    isset($value['view']) && isset($views[$value['view']]) ? $views[$value['view']] : null,
                    $value['name'],
                    $app->getRequestParamsFetcher());
            } else {
                ActionBuilder::constructOtherView($app, $result, $key, $value, $views);
            }
        }

        return [
            $result,
            $presenter
        ];
    }

    /**
     * Method loads route for the application
     *
     * @param CommonApplication $app
     * @param string $method
     */
    private static function loadRouteForApp(CommonApplication $app, string $method): void
    {
        $app->loadRoute(
            [
                'route' => Utils::convertMethodNameToRoute($method),
                'callback' => [
                    $app,
                    $method
                ],
                'method' => [
                    'GET',
                    'POST'
                ]
            ]);
    }

    /**
     * Method creates action from JSON config
     *
     * @param string $path
     *            path to JSON config
     */
    public static function createActionFromJsonConfig(CommonApplication $app, string $path): void
    {
        $settings = json_decode(file_get_contents($path), true);

        static::createActionFromSettingsObject($app, $settings, basename($path, '.json'));
    }

    /**
     * Method creates action from settings object
     *
     * @param CommonApplication $app
     *            application object
     * @param array|object $settings
     *            action settings
     * @param string $method
     *            method name
     */
    public static function createActionFromSettingsObject(CommonApplication $app, $settings, string $method = ''): void
    {
        if ($method === '') {
            $method = Fetcher::getField($settings, 'name', false);
        }

        // we load actions when parsing configs
        // but compile action while running getActionBodyFromSettingsObject within $app->$method
        static::$loadedActions[$method] = $settings;

        $app->$method = function () use ($settings, $app): array {
            list ($result, $presenter) = self::getActionBodyFromSettingsObject($app, $settings);

            $app->result($presenter);

            return $result;
        };

        static::loadRouteForApp($app, $method);
    }
}
