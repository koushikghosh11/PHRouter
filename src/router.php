<?php

namespace PHRouter;

require_once __DIR__ . "/Response.php";

use Exception;

class Router
{
    //Method of Request
    private ?string $method = null;
    //Route Array
    private array $routes;
    //Callback error function
    private $errorFunction = null;
    //Request Array to store Request related information
    private ?array $request = null;
    //Current Request PATH
    private ?string $currentPath = null;
    //Object of class Response
    private Response $response;
    //regex for path pattern match
//    private string $pattern = '/(:([a-zA-Z0-9_]+))(([.-])(:([a-zA-Z0-9_]+))(?<optional>\?)?)?/';
    private string $pattern = '@(/:([a-zA-Z0-9_]+))((?<opt1>\?$)|((.|-):([a-zA-Z0-9_]+)(?<opt2>\?)?))?@';

    // associative array of query string parameters
    private function queryArr($string)
    {
        if ($string == null || $string == "") return "";
        $qArr = array();
        foreach (explode("&", $string) as $item) {
            $i = explode("=", $item);
            $qArr[$i[0]] = $i[1];
        }
        return $qArr;
    }
    //preg_replace_callback function
    private function regexReplaceCallBack($matches): string
    {
        if (isset($matches['opt2']) && $matches['opt2'] != null){
            $nStr = "(/(?<$matches[2]>[0-9a-zA-Z]+)(\\$matches[6](?<$matches[7]>[0-9a-zA-Z]+)?)?)";
        }elseif(isset($matches[7]) && !isset($matches['opt2'])){
            $nStr = "(/(?<$matches[2]>[0-9a-zA-Z]+)\\$matches[6](?<$matches[7]>[0-9a-zA-Z]+))";
        }elseif (isset($matches['opt1']) && $matches['opt1']!= null){
            $nStr = "(/(?<$matches[2]>[0-9a-zA-Z]*))?";
        }else{
            $nStr = "(/(?<$matches[2]>[0-9a-zA-Z]+))";
        }
        return $nStr;
    }

    /**
     * Default Constructor: Initializing all variables
     * @method __construct
     */
    public function __construct()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                $this->method = $_SERVER['REQUEST_METHOD'];
                $this->request["method"] = $_SERVER['REQUEST_METHOD'];
            }
            $this->request["header"] = $this->getHTTPHeaders();
            if (isset($_SERVER['REQUEST_URI'])) {
                $this->currentPath = rtrim(explode("?", $_SERVER['REQUEST_URI'])[0], ".\/");
            }
        }
        if (isset($_POST)) {
            $this->request["body"] = $_POST;
            $this->request["raw"] = file_get_contents('php://input');
        }
        if (isset($_GET)) {
            $this->request["params"] = $_GET;
        }
        if (isset($_FILES)) {
            $this->request["files"] = $_FILES;
        }
        if (isset($_COOKIE)) {
            $this->request["cookies"] = $_COOKIE;
        }
        if (isset($_SERVER['QUERY_STRING'])) {
            $this->request["query"] = self::queryArr($_SERVER['QUERY_STRING']);
        }
        $this->response = new Response();
        $this->routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => [], 'PATCH' => [], 'ANY' => []];
    }

    /**
     * Function to get headers related to HTTP,PHP_AUTH and REQUEST from $_SERVER
     * @method getHTTPHeaders
     * @return array         returns a containing all information related to HTTP,PHP_AUTH and REQUEST from $_SERVER
     */
    private function getHTTPHeaders(): array
    {
        $header = array();
        foreach ($_SERVER as $name => $value) {
            if (preg_match('/^HTTP_/', $name)||preg_match('/^PHP_AUTH_/', $name)||preg_match('/^REQUEST_/', $name)) {
                $header[$name] = $value;
            }
        }
        return $header;
    }

    /**
     * Turns given path into regular expression for comparison in complex routing
     * @method getRegexRepresentation
     * @param string $path Route
     * @return bool|string Turns route into a regex
     */
    private function getRegexRepresentation(string $path)
    {
        //Check for invalid pattern
        if (preg_match('/[^-:.?\/_{}()a-zA-Z\d]/', $path)) {
            return false;
        }
        // Turn "(/)" into "/?"
        $path = preg_replace('#\(/\)#', '/?', $path);
        //Replace parameters
        // $path = preg_replace('/:(' . $this->CharsAllowed . ')/', '(?<$1>' . $this->CharsAllowed . ')', $path);
        //$path = preg_replace('/{(' . $this->CharsAllowed . ')}/', '(?<$1>' . $this->CharsAllowed . ')', $path);
//        $path = preg_replace_callback($this->pattern, array($this, 'regexReplaceCallBack'), $path); // this can be used too
        $path = preg_replace_callback($this->pattern, 'self::regexReplaceCallBack', $path);
        // Add start and end matching
        return '@^' . $path . '$@D';
    }

    /**
     * Add the given route to 'GET' array for lookup
     * @method get
     * @param string|array $path Route
     * @param callable $callback Function to be called when the current equates the provided route; The callback must take request array and response object as parameters
     * @return Router
     */
    public function get($path, callable $callback): Router
    {
        if (is_array($path)){
            foreach ($path as $item){
                $this->routes['GET'][$this->getRegexRepresentation(rtrim($item, ".\/"))] = $callback;
            }
        }else{
            $this->routes['GET'][$this->getRegexRepresentation(rtrim($path, ".\/"))] = $callback;
        }
        return $this;
    }

    /**
     * Add the given route to 'POST' array for lookup
     * @method post
     * @param string $path Route
     * @param callable $callback Function to be called when the current equates the provided route; The callback must take request array and response object as parameters
     * @return Router
     */
    public function post(string $path, callable $callback): Router
    {
        $this->routes['POST'][$this->getRegexRepresentation(rtrim($path, ".\/"))] = $callback;
        return $this;
    }

    /**
     * Add the given route to 'PUT' array for lookup
     * @method put
     * @param string $path Route
     * @param callable $callback Function to be called when the current equates the provided route; The callback must take request array and response object as parameters
     * @return Router
     */
    public function put(string $path, callable $callback): Router
    {
        $this->routes['PUT'][$this->getRegexRepresentation(rtrim($path, ".\/"))] = $callback;
        return $this;
    }

    /**
     * Add the given route to 'PATCH' array for lookup
     * @method patch
     * @param string $path Route
     * @param callable $callback Function to be called when the current equates the provided route; The callback must take request array and response object as parameters
     * @return Router
     */
    public function patch(string $path, callable $callback): Router
    {
        $this->routes['PATCH'][$this->getRegexRepresentation(rtrim($path, ".\/"))] = $callback;
        return $this;
    }

    /**
     * Add the given route to 'DELETE' array for lookup
     * @method delete
     * @param string $path Route
     * @param callable $callback Function to be called when the current equates the provided route; The callback must take request array and response object as parameters
     * @return Router
     */
    public function delete(string $path, callable $callback): Router
    {
        $this->routes['DELETE'][$this->getRegexRepresentation(rtrim($path, ".\/"))] = $callback;
        return $this;
    }

    /**
     * Add the given route to 'ANY' array for lookup. ANY can be any REQUEST_METHOD
     * @method any
     * @param string $path Route
     * @param callable $callback Function to be called when the current equates the provided route; The callback must take request array and response object as parameters
     * @return Router
     */
    public function any(string $path, callable $callback): Router
    {
        $this->routes['ANY'][$this->getRegexRepresentation(rtrim($path, ".\/"))] = $callback;
        return $this;
    }

    /**
     * @param $ext
     * @return void
     */
    public function engineExtension($ext)
    {
        $this->response->engineExt($ext);
    }

    /**
     * Error Handler to set handler to be called when no routes are found
     * @method error
     * @param callable $function A callback function that takes request array and response object
     * @return Router
     */
    public function error(callable $function): Router
    {
        $this->errorFunction = $function;
        return $this;
    }

    /**
     * Function to get appropriate callback for the current PATH_INFO based on REQUEST_METHOD
     * @method getCallback
     * @param string $method REQUEST_METHOD as string
     * @return callable|null The callback function
     */
    private function getCallback(string $method): ?callable
    {
        if (!isset($this->routes[$method])) {
            return null;
        }
        foreach (array_keys($this->routes[$method]) as $name) {
            if (preg_match_all($name, $this->currentPath, $matches, PREG_SET_ORDER)) {
                // Get elements with string keys from matches
                $params = array_filter($matches[0], 'is_string', ARRAY_FILTER_USE_KEY);
                foreach ($params as $key => $value) {
                    $this->request["params"][$key] = $value;
                }
                return $this->routes[$method][$name];
            }
        }
        return null;
    }

    /**
     * Starts the routing process by matching current PATH_INFO to available routes in array $routes
     * @method start
     * @return callable|null  Returns callback function of the appropriate route or returns callback function of the error handler
     */
    public function start(): ?callable
    {
        $callback = $this->getCallBack('ANY');
        if ($callback) {
            return $callback($this->request, $this->response);
        }
        $callback = $this->getCallBack($this->method);
        if ($callback) {
            return $callback($this->request, $this->response);
        }
        if (isset($this->errorFunction)) {
            return ($this->errorFunction)(new Exception("Path not found!", 404), $this->response);
        }
        return null;
    }
}
