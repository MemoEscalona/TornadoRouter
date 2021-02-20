<?php
namespace TornadoRouter;
/**
* Implementación de comparador y analizador generico de URI.
*/
class Template {

    private static $globalQueryParams = array();
    private $patterns = array();

    private $template  = null;
    private $params    = array();
    private $callbacks = array();

    /**
    * Contructor de la clase
    * @param String $path ruta agregada a la clase
    */
    public function __construct($path) {
        if ($path{0} != '/') {
            $path = "/$path";
        }
        $this->template = rtrim($path, '\/');
    }
    /**
    * Metodo que devuelve el valor del path
    * @return String Template
    */
    public function getTemplate() {
        return $this->template;
    }
    /**
    * Metodo que devuelve la expresion regular con el formato ~^cadena&~
    * donde ~ es el delimitador de la expresión regular
    * donde ^ indica como debe de comenzar
    * donde $ indica como debe de terminar
    * @return String expresión regular
    */
    public function getExpression() {
        $expression = $this->template;
        if (preg_match_all('~(?P<match>\{(?P<name>.+?)\})~', $expression, $matches)) {
            $expressions = array_map(array($this, 'pattern'), $matches['name']);
            $expression  = str_replace($matches['match'], $expressions, $expression);
        }

        return sprintf('~^%s$~', $expression);
    }
    /**
    * Obtiene el patron para la expresión regular
    * @param  String $token nombre del parametro en la uri {hola} siguiendo con el ejemplo de arriba
    * @param  String $pattern si el path tiene algun tipo de restriccuón el pattern trae dicha restriccion
    * @return String patron para la expresión regular del parametro asociado
    */
    public function pattern($token, $pattern = null) {
        if ($pattern) {
            if (!isset($this->patterns[$token])) {
                $this->patterns[$token] = $pattern;
            }
        } else {

            if (isset($this->patterns[$token])) {
                $pattern = $this->patterns[$token];
            } else {
                $pattern = Constants::PATTERN_ANY;
            }

            if ((is_string($pattern) && is_callable($pattern)) || is_array($pattern)) {
                $this->callbacks[$token] = $pattern;
                $this->patterns[$token] = $pattern = Constants::PATTERN_ANY;
            }
            return sprintf($pattern, $token);
        }
    }
    /**
    * Añade parametros a la ruta
    * @param String $name nombre del path
    * @param string $pattern expresion regular que debe cumplir
    * @param Object $defaultValue valor por defecto que toma el paramtro
    */
    public function addQueryParam($name, $pattern = '', $defaultValue = null) {
        if (!$pattern) {
            $pattern = Constants::PATTERN_ANY;
        }
        $this->params[$name] = (object) array(
            'pattern' => sprintf($pattern, $name),
            'value'   => $defaultValue
        );
    }
    /**
	*Añade parametros globales a la ruta
	*@param string $name nombre del path
	*@param string $pattern expresion regular que debe cumplir
	*@param [type] $defaultValue valor por defecto que toma el paramtro
	*/
    public static function addGlobalQueryParam($name, $pattern = '', $defaultValue = null) {
        if (!$pattern) {
            $pattern = Constants::PATTERN_ANY;
        }
        self::$globalQueryParams[$name] = (object) array(
            'pattern' => sprintf($pattern, $name),
            'value'   => $defaultValue
        );
    }
    /**
    * Metodo que realiza la busqueda de la URI con el path buscado
    * @param  String $uri uri buscada
    * @return Array arreglo de los datos resultantes
    */
    public function match($uri) {

        $uri = rtrim($uri, '\/');
        $match_found = preg_match($this->getExpression(), $uri, $matches);
        // si no hubo coincidencias se retorna al router
        if (! $match_found) return;

        foreach($matches as $k => $v) {
            // si es númerico se elimna de las coincidencias
            if (is_numeric($k)) {
                unset($matches[$k]);
            } else {
                if (isset($this->callbacks[$k])) {
                    // si existen callbacks asociados a la ruta, se ejecutarán.
	                // si hay alguna restricción que no se cumpla se lanzará una excepción.
                    $callback = Callback_Util::getCallback($this->callbacks[$k]);
                    $value    = call_user_func($callback, $v);
                    if ($value) {
                        $matches[$k] = $value;
                    } else {
                        throw new InvalidURIParameterException('Invalid parameters detected');
                    }
                }
                 //si el valor contiene una diagonal,se hace un explode y se agrega al arreglo
                if (strpos($v, '/') !== false) {
                    $matches[$k] = explode('/', trim($v, '\/'));
                }
            }
        }
        //sea gregan los patrametros del path de busqueda, con los parametros
        $params = array_merge(self::$globalQueryParams, $this->params);
        if (!empty($params)) {
            $this->enforceParamMatching($params);
        }
         //se regresan las coincidenas
        return $matches;
    }
    /**
	* Metodo que forza la coincidencia de los parametros
	* @param  Arrays $params parrametros
	*/
    protected function enforceParamMatching($params) {
        foreach($params as $name => $param) {
            if (!isset($_GET[$name]) && $param->value) {
                $_GET[$name] = $param->value;
                $matched = true;
            } else if ($param->pattern && isset($_GET[$name])) {
                $result = preg_match(sprintf('~^%s$~', $param->pattern), $_GET[$name]);
                if (!$result && $param->value) {
                    $_GET[$name] = $param->value;
                    $result = true;
                }
                $matched = $result;
            } else {
                $matched = false;
            }
            if ($matched == false) {
                throw new Exception('Request does not match');
            }
        }
    }
    /**
	* Regrsa la expresion regular asociada del patron
	* @param  String $pattern patron
	* @return String expresion regular del patron
	*/
    public static function regex($pattern) {
        return "(?P<%s>$pattern)";
    }
}
