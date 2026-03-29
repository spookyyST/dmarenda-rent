<?php

declare(strict_types=1);

namespace Rent\Http;

use Closure;

class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, handler:Closure}> */
    private array $routes = [];

    public function add(string $method, string $pattern, Closure $handler): void
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, Closure $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, Closure $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (!preg_match($route['regex'], $request->path(), $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            $handler = $route['handler'];
            $reflection = new \ReflectionFunction($handler);
            $paramsCount = $reflection->getNumberOfParameters();

            if ($paramsCount >= 2) {
                return $handler($request, $params);
            }
            if ($paramsCount === 1) {
                return $handler($request);
            }

            return $handler();
        }

        return Response::html('<h1>404 Not Found</h1>', 404);
    }
}
