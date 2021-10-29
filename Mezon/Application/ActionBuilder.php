<?php
namespace Mezon\Application;

use Mezon\HtmlTemplate\HtmlTemplate;
use Mezon\Router\Utils;

class ActionBuilder
{

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
     * @param HtmlTemplate $template template
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
     * Overriding defined config
     *
     * @param string $path
     *            path to the current config
     * @param array $config
     *            config itself
     */
    private static function constructOverrideHandler(string $path, array &$config): void
    {
        if (isset($config['override'])) {
            $path = pathinfo($path, PATHINFO_DIRNAME);

            $baseConfig = json_decode(file_get_contents($path . '/' . $config['override']), true);

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
    public static function getActionBuilderMethod(CommonApplication $app, string $key, $value): ?callable
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
    public static function constructOtherView(CommonApplication $app, array &$result, string $key, $value, array &$views): void
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
     * @param string $path
     *            path to JSON config
     * @return array ($result, $presenter)
     */
    private static function getActionBody(CommonApplication $app, string $path): array
    {
        $result = [];
        $presenter = null;
        $config = json_decode(file_get_contents($path), true);
        $views = [];

        ActionBuilder::constructOverrideHandler($path, $config);

        foreach ($config as $key => $value) {
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
     * Method creates action from JSON config
     *
     * @param string $path
     *            path to JSON config
     */
    public static function createActionFromJsonConfig(CommonApplication $app, string $path): void
    {
        $method = 'action' . basename($path, '.json');

        $app->$method = function () use ($path, $app): array {
            list ($result, $presenter) = self::getActionBody($app, $path);

            $app->result($presenter);

            return $result;
        };

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
}
