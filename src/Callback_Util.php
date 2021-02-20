<?php
namespace TornadoRouter;
/**
* Clase que sirve para manejar los callback para el proceso de enrutamiento.
*/
class Callback_Util {
    /**
     * Metodo que valida que los archivos existan
     */
    private static function loadFile($file) {
        if (file_exists($file)) {
            include_once($file);
        } else {
            throw new Exceptions\CallbackFileNotFoundException('Controller file not found');
        }
    }
    /**
    * Metodo que devuelve el callback
    * @param  String $callback nombre del callback
    * @return Array Clase y metodo
    */
    public static function getCallback($callback, $file = null) {
        if ($file) {
            self::loadFile($file);
        }
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
            throw new Exceptions\InvalidCallbackException('Invalid callback');    
        }
        return $callback;
    }
}