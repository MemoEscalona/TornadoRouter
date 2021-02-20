<?php

namespace TornadoRouter\Middleware;
/**
* Clase que sobrescribe el Request methon
*/
class MethodOverride extends \TornadoRouter\BaseMiddleware {
  /**
	* Se ejecuta este segmento de código antes de que se haga la petición
	* Si es Post y HTTP_X_HTTP_METHOD_OVERRIDE
	* @param  Router &$router Puntero
	*/  
  function preprocess(&$router) {
    if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) && \TornadoRouter\Router::getRequestMethod() == "post") {
      $_SERVER['REQUEST_METHOD'] = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
    }
  }
}