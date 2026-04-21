<?php
/**
 * Query-string 기반 라우터
 * ?route=controller/action 형식
 */
class Router
{
    private $routes = [];

    /**
     * 라우트 등록
     */
    public function add($route, $controller, $action)
    {
        $this->routes[$route] = [
            'controller' => $controller,
            'action'     => $action,
        ];
    }

    /**
     * 라우트 디스패치
     */
    public function dispatch($route)
    {
        if (!isset($this->routes[$route])) {
            http_response_code(404);
            echo '<h1>404 Not Found</h1><p>요청하신 페이지를 찾을 수 없습니다.</p>';
            return;
        }

        $entry = $this->routes[$route];
        $controllerName = $entry['controller'];
        $actionName = $entry['action'];

        $controllerFile = BASE_PATH . '/controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            http_response_code(500);
            echo '<h1>500 Error</h1><p>컨트롤러를 찾을 수 없습니다.</p>';
            return;
        }

        require_once $controllerFile;
        $controller = new $controllerName();

        if (!method_exists($controller, $actionName)) {
            http_response_code(404);
            echo '<h1>404 Not Found</h1><p>액션을 찾을 수 없습니다.</p>';
            return;
        }

        $controller->$actionName();
    }
}
