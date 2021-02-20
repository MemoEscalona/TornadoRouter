<?php
namespace TornadoRouter;
/**
* Clase que maneja las rutas de las peticiones
*/
class Router {
    //variable que maneja las rutas
    protected $routes  = array();
    //varible del middleware
    public static $middleware = array();
    //variable que lista el whitelist de los tipos de peticiones que se van a ocupar.
    protected static $methods = array('get', 'post', 'put', 'patch', 'delete', 'head', 'options');
    /**
    * Metodo que permite añadir una ruta al arreglo de rutas.
    * @param Array $params Es un arreglo que debe incluir la propiedad path.
    */
    public function addRoute($params) {
        if (!empty($params['path'])) {
            $template = new Template($params['path']);
            if (!empty($params['handlers'])) {
                foreach ($params['handlers'] as $key => $pattern) {
                    $template->pattern($key, $pattern);
                }
            }
            $methods = array_intersect(self::$methods, array_keys($params));
            foreach ($methods as $method) {
                $this->routes[$method][$params['path']] = array(
                    'template' => $template,
                    'callback' => $params[$method],
                    'file'     => !empty($params['file']) ? $params['file'] : '',
                );
                BaseMiddleware::$routes[$method][$params['path']] = $this->routes[$method][$params['path']];
            }
        }
    }

    /**
	* Agrega un Middleware al listado de middlewares
	* @return instancia del middleeare
	*/
    public function attach() {
        //se obtienen los argumentos de la función
        $args = func_get_args();
        // se obtiene el nombre de las clase quitando el primer elemento del arreglo
        $className = array_shift($args);
        // si el nombre de la clase no extiende de la clase 
        if (!is_subclass_of($className, '\TornadoRouter\BaseMiddleware')) {
            throw new Exceptions\InvalidMiddlewareClass("Middleware class: '$className' does not exist or is not a sub-class of \TornadoRouter\BaseMiddleware" );
        }
        // convert args array to parameter list
        $reflection = new \ReflectionClass($className);
        $instance = $reflection->newInstanceArgs($args);
        self::$middleware[] = $instance;
        return $instance;
    }

    /**
	* Metodo que devuelve el request method en minuscula
	* @return String request method
	*/
    public static function getRequestMethod() {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
    * Metodo que obtiene las rutas.
    * @param  boolean $all Si es true, devuelve todas las rutas, si es false solo aquellas que concuerden con el metodo actual
    * @return Array Rutas
    */
    private function getRoutes($all = false) {
        if ($all) {
            return $this->routes;
        }

        $method = self::getRequestMethod();
        $routes = empty($this->routes[$method]) ? array() : $this->routes[$method];
        return $routes;
    }
    /**
	* Metodo que realiza el proceso de enrutamiento
	* @param  String $uri ruta que se desea ejecutar
	* @return Object resultado de la ejecución del callback
	*/
    public function route($uri = null) {
        if (empty($uri)) {
            // NOTA IMPORTANTE: parse_url no funciona de manera confiable con URI relativos, esta pensando para URL's totalmente calificadas
	        // Nosotros tenemos una URI y podriamos hacer uso de parse_url, pero mejor pretendamos que tenemos una url realizando una concatenación.
            $tokens = parse_url('https://foo.com' . $_SERVER['REQUEST_URI']);
            $uri = rawurldecode($tokens['path']);
        }

        // Ejecutar los preprocesadores implementados en cada middleware
        foreach (self::$middleware as $m) {
            $m->preprocess($this);
        }
        // obtenemos las rutas
        $routes = $this->getRoutes();

        foreach ($routes as $route) {
            //buscamos que coincida
            $params = $route['template']->match($uri);
             //si los parametros no estan vacios
            if (!is_null($params)) {
                //asignamos el template
                BaseMiddleware::$context['pattern'] = $route['template']->getTemplate();
                //asignamos la URI
                BaseMiddleware::$context['request_uri'] = $uri;
                //asignamos el metodo
                BaseMiddleware::$context['http_method'] = self::getRequestMethod();
                //asignamos el callback de la ruta
                BaseMiddleware::$context['callback'] = $route['callback'];
                // se ejecuta el callbak
                $callback = Callback_Util::getCallback($route['callback'], $route['file']);
                return $this->invoke_callback($callback, $params);
            }
        }

        // None of the pre-defined routes matched. Is this a preflight request?
        // pre-flight request:
        if (self::getRequestMethod() == "options") {
            return $this->invoke_preflight($uri);
        }
        throw new Exceptions\InvalidPathException('Invalid path');
    }

    protected function invoke_preflight($uri) {
        $routes = $this->getRoutes(true);

        $allowedRoutes = array();
        foreach ($this->getRoutes(true) as $method => $routes) {
            foreach ($routes as $route) {
                $params = $route['template']->match($uri);
                if (!is_null($params)) {
                    $pattern = $route['template']->getTemplate();
                    $allowedRoutes[$pattern][] = $method;
                }
            }
        }

        foreach($allowedRoutes as $route => $methods) {
            foreach (self::$middleware as $m) {
                BaseMiddleware::$context['pattern'] = $route;
                BaseMiddleware::$context['request_uri'] = $uri;
                BaseMiddleware::$context['http_method'] = $methods;
                if ($m->shouldRun()) {
                    if ($m->preflight() === FALSE) {
                        return; // don't execute any more 'preflight' middleware
                    }
                }
            }
        }
    }

    /**
    * Metodo que permite ejecutar el callback.
    * La principal razón de tener este segmento en ontro metodo, es en caso que el usuario de la biblioteca quiera cambiar
    * la logica de la invocación, sin tener que copyar y pegar el restro de la logica en el metodo route.
	*/
    protected function invoke_callback($callback, $params) {

        $req = new Request();
        $req->params = $params;
        $res = new Response($req);

        // Llamar a los preprocesadores implementados en los middlewares
        foreach (self::$middleware as $m) {
            //si debe ejecutarse
            if ($m->shouldRun()) {
                /* Si el prerout manejo la solicitud y no quiere que el codigo corra
	            * p.ej. si la ruta previa decidió que la sesión no estaba establecida y quiere emitir un 401, o reenviar usando un 302.
	            */
                if( $m->preroute($req,$res) === FALSE) {
                    return;  // no, no hagas nada aquí.
                }
                // continua de manera usual.
            }
        }
        return call_user_func($callback, $req, $res);
    }
}