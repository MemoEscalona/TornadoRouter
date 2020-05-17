<?php
    namespace TornadoRouter;
    /**
     * Clase que permite realizar middlewares y procesar información en los distintos estados
     * de la petición.
     */
    abstract class BaseMiddleware {
        const ALL_METHODS = '*';
        public $scope = array();
        public static $context = array();
        public static $routes = array();
        /**
         * Enlace de Middleware a ciertas rutas y métodos HTTP.
         * No hay una retricción en este metodo.
         * Simplemente añadimos agregamos el array de $methods al array $scope.
         * @param $methods Arreglo de metodos HTTP que son permitidos
         * @param $route Ruta para restringir el middleware. Esta ruta debe estar añadida con addRoute, no cualquier ruta
         * @return $this la instancia de la clase
         */
        public function restrict($methods, $route) {
            $this->scope[$route] = $methods;
            return $this;
        }
        /**
         *  Determina si la ruta actual tiene alguna restriccion para este middleware.
         *  BaseMiddleware debe de tener self::$context['pattern'] y self::$context['http_method'] establecidos.
         *  @return bool Positivo si debe correr o no
         */
        public function shouldRun() {
            // Sin restricciones
            if (empty($this->scope)) return true; 
            // si hay restricciones
            if (array_key_exists(self::$context['pattern'], $this->scope)) {
                // se obtiene los metodos
                $methods = $this->scope[self::$context['pattern']];
                // si los metodos coinciden con *, puede correr
                if ($methods == self::ALL_METHODS) {
                    return true;
                }
                // si los metodos no es un arreglo, no puede ejecutarse
                if (!is_array($methods)) {
                    return false;
                }
                // si el metodo http actual no esta en el listado de metodos permitidos, no puede ejecutarse
                if (!in_array(strtolower(self::$context['http_method']), array_map('strtolower', $methods))) {
                    return false;
                }
            } else {
                return false;
            }
            return true;
        }
        /**
         * PreProccess Aquí es donde puedes agregar rutas
         * @param  Router &$router puntero al router
         */
        public function preprocess(&$router) {}
        /**
         * Preroute. Aquí es donde puedes alterar los requests o implementar cosas como seguridad, filtros, etc.
         * @param  Request &$req Puntero del objeto request
         * @param  Response &$res Puntero del objeto response
         */
        public function preroute(&$req, &$res) {}
        /** This is your chance to override output. It can be called multiple times for each ->flush() invocation! **/
        /**
         * Aquí es donde tienes oportunidad de sobrescribier el output. Se puede llavar multiples veces con la invocación ->flush()
         * @param  Buffer &$buffer Puntero del buffer
         */
        public function prerender(&$buffer) {}
    }