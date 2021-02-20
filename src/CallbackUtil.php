<?php
    namespace TornadoRouter;
    /**
    * Clase que sirve para manejar los callback para el proceso de enrutamiento.
    */
    class CallbackUtil {
        /**
         * Metodo que devuelve el callback
         * @param  String $callback nombre del callback
         * @return Array Clase y metodo
         */
        public static function getCallback($callback) {
            if (is_array($callback)) {
                $originalClass = array_shift($callback);
                $method = new \ReflectionMethod($originalClass, array_shift($callback));
                if ($method->isPublic()) {
                    if ($method->isStatic()) {
                        $callback = array($originalClass, $method->name);
                    } else {
                        $callback = array(new $originalClass, $method->name);
                    }
                }
            }
            if (!is_callable($callback)) {
                throw new TornadoRouterException('Invalid callback');
            }
            return $callback;
        }

}