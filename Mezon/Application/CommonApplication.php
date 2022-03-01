<?php
namespace Mezon\Application;

use Mezon\HtmlTemplate\HtmlTemplate;
use Mezon\Rest;
use Mezon\ViewInterface;
use Mezon\Utils\Fs;

/**
 * Class CommonApplication
 *
 * @package Mezon
 * @subpackage CommonApplication
 * @author Dodonov A.A.
 * @version v.1.0 (2019/08/07)
 * @copyright Copyright (c) 2019, aeon.org
 */

/**
 * Common application with any available template
 *
 * To load routes from the config call $this->load_routes_from_config('./Conf/routes.json');
 *
 * The format of the *.json config must be like this:
 *
 * [
 * {
 * "route" : "/route1" ,
 * "callback" : "callback1" ,
 * "method" : "POST"
 * } ,
 * {
 * "route" : "/route2" ,
 * "callback" : "callback2" ,
 * "method" : ["GET" , "POST"]
 * }
 * ]
 */
class CommonApplication extends Application
{

    /**
     * Application's template
     *
     * @var ?HtmlTemplate
     */
    private $template = null;

    /**
     * Constructor
     *
     * @param HtmlTemplate $template
     *            Template
     */
    public function __construct(HtmlTemplate $template)
    {
        parent::__construct();

        $this->template = $template;

        $this->getRouter()->setNoProcessorFoundErrorHandler([
            $this,
            'noRouteFoundErrorHandler'
        ]);

        $this->loadActionsFromConfig();
    }

    /**
     * Method handles 404 errors
     *
     * @codeCoverageIgnore
     */
    public function noRouteFoundErrorHandler(): void
    {
        $this->redirectTo('/404');
    }

    /**
     * Method renders common parts of all pages.
     *
     * @return array List of common parts.
     */
    public function crossRender(): array
    {
        return [];
    }

    /**
     * Method formats exception object
     *
     * @param \Exception $e
     *            Exception
     * @return object Formatted exception object
     */
    protected function baseFormatter(\Exception $e): object
    {
        $error = new \stdClass();
        $error->message = $e->getMessage();
        $error->code = $e->getCode();

        if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
            $error->host = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $error->host = 'undefined';
        }
        return $error;
    }

    /**
     * Method formats raw body
     *
     * @param string $body
     *            raw body
     * @return mixed formatted body or raw if it was not parsed
     */
    protected function formatRawBody(string $body)
    {
        $matches = [];

        // extracting errors from the XDebug output
        // TODO parse non XDebug output
        $return = [];

        if (preg_match('/<th.*\( ! \)<\/span>(.*)<\/th>/mU', $body, $matches)) {
            $return[] = trim($matches[1]);
        } else {
            // encode tags
            return htmlentities($body);
        }

        // extract call trace from the XDebug output

        // cutting the first table
        $strpos = strpos($body, '</table>');
        $body = substr($body, 0, $strpos === false ? null : $strpos);

        // parsing string
        if (preg_match_all("/<tr><td.*<\/td><td.*<\/td><td.*<\/td><td.*<\/td><td.*>(.*)<\/td>/mU", $body, $matches)) {
            // cleaning data
            $matches = $matches[1];

            // cleaning tags
            foreach ($matches as $i => $match) {
                $matches[$i] = strip_tags($match);
                $matches[$i] = str_replace(':', ' (', $matches[$i]) . ')';
            }

            // saving result
            return array_merge($matches, $return);
        } else {
            return $return;
        }
    }

    /**
     * Method formats body
     *
     * @param string $body
     *            body to be formatted
     * @return string|array formatted bodt
     */
    protected function formatBody(string $body)
    {
        $formattedBody = json_decode($body);

        if ($formattedBody === null || $formattedBody === false) {
            return $this->formatRawBody($body);
        } else {
            return $formattedBody;
        }
    }

    /**
     * Method formats REST exception object
     *
     * @param Rest\Exception $e
     *            Exception
     * @return object Formatted exception object
     */
    protected function jsonFormatter(Rest\Exception $e): object
    {
        $error = $this->baseFormatter($e);

        $error->call_stack = $this->formatCallStack($e);

        $error->call_stack[] = $this->formatBody($e->getHttpBody());

        $error->http_body = '&lt;parsed&gt;';

        return $error;
    }

    /**
     * Method processes exception.
     *
     * @param Rest\Exception $e
     *            RestException object.
     */
    public function handleRestException(Rest\Exception $e): void
    {
        $error = $this->jsonFormatter($e);

        print('<pre>' . json_encode($error, JSON_PRETTY_PRINT));
    }

    /**
     * Method processes exception.
     *
     * @param \Exception $e
     *            Exception object.
     */
    public function handleException(\Exception $e): void
    {
        $error = $this->baseFormatter($e);

        $error->call_stack = $this->formatCallStack($e);

        print('<pre>' . json_encode($error, JSON_PRETTY_PRINT));
    }

    /**
     * Method sets GET parameter as template var
     *
     * @param string $fieldName
     *            name of the GET parameter
     */
    protected function setGetVar(string $fieldName): void
    {
        if (isset($_GET[$fieldName])) {
            $this->getTemplate()->setPageVar($fieldName, $_GET[$fieldName]);
        }
    }

    /**
     * Running application.
     */
    public function run(): void
    {
        try {
            $callRouteResult = $this->callRoute();

            if (is_array($callRouteResult) === false) {
                throw (new \Exception('Route was not called properly'));
            }

            $result = array_merge($callRouteResult, $this->crossRender());

            foreach ($result as $key => $value) {
                $content = $value instanceof ViewInterface ? $value->render() : $value;

                $this->getTemplate()->setPageVar($key, $content);
            }

            $this->setGetVar('redirect-to');

            print($this->getTemplate()->compile());
        } catch (Rest\Exception $e) {
            $this->handleRestException($e);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Getting template
     *
     * @return HtmlTemplate Application's template
     * @codeCoverageIgnore
     */
    public function getTemplate(): HtmlTemplate
    {
        if ($this->template === null) {
            throw (new \Exception('Template was not set', - 1));
        }

        return $this->template;
    }

    /**
     * Setting template
     *
     * @param HtmlTemplate $template
     *            Template
     * @codeCoverageIgnore
     */
    public function setTemplate(HtmlTemplate $template): void
    {
        $this->template = $template;
    }

    /**
     * Method compiles result record
     *
     * @param mixed $presenter
     *            main area presenter
     * @return void result record
     */
    public function result($presenter = null): void
    {
        if ($presenter !== null) {
            $presenter->run();
        }

        if (($actionMessage = $this->getTemplate()->getSuccessMessageCode()) !== '') {
            $this->getTemplate()->setSuccessMessage($actionMessage);
        } elseif (($actionMessage = $this->getTemplate()->getErrorMessageCode()) !== '') {
            $this->getTemplate()->setErrorMessage($actionMessage);
        }
    }

    /**
     * Method loads actions from path
     *
     * @param string $path
     */
    public function loadActionsFromDirectory(string $path): void
    {
        if (file_exists($path)) {
            $files = scandir($path);

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    // do nothing
                } elseif (is_file($path . '/' . $file) && strpos($file, '.json') !== false) {
                    ActionBuilder::createActionFromJsonConfig($this, $path . '/' . $file);
                } elseif (is_dir($path . '/' . $file)) {
                    $this->loadActionsFromDirectory($path . '/' . $file);
                }
            }
        }
    }

    /**
     * Method loads actions from directories
     *
     * @param array $paths
     *            to directories with actions
     */
    public function loadActionsFromDirectories(array $paths): void
    {
        foreach ($paths as $path) {
            $this->loadActionsFromDirectory($path);
        }
    }

    /**
     * Method loads all actions from ./Actions directory
     */
    private function loadActionsFromConfig(): void
    {
        $this->loadActionsFromDirectory(Fs::getClassPath($this) . '/Actions/');
    }
}

