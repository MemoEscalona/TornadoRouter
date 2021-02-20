<?php

namespace TornadoRouter\Middleware;

/**
 * Class TornadoRouterCORS
 * @package TornadoRouter\BaseMiddleware
 *
 * Clase que permite agregar cors al API
 * Usage:
 * $router->attach('TornadoRouterCORS', '*')
 *        ->restrict('GET', '/users')
 */
class CORS extends \TornadoRouter\BaseMiddleware {
  private $domain;
  function __construct($domain = '*') {
    $this->domain = $domain;
  }
  function preflight() {
    $allowedMethods = self::$context['http_method'];
    $req = new \TornadoRouter\Request();
    $res = new \TornadoRouter\Response($req);
    $res->addHeader("Access-Control-Allow-Origin", $this->domain);
    $res->setFormat("text/plain");
    $headers = array (
        "Access-Control-Allow-Methods" => $allowedMethods,
        "Access-Control-Allow-Headers" => array (
            "origin", "accept", "content-type", "authorization",
            "x-http-method-override", "x-pingother", "x-requested-with",
            "if-match", "if-modified-since", "if-none-match", "if-unmodified-since"
        ),
        "Access-Control-Expose-Headers" => array (
            "tag", "link",
            "X-RateLimit-Limit", "X-RateLimit-Remaining", "X-RateLimit-Reset",
            "X-OAuth-Scopes", "X-Accepted-OAuth-Scopes"
        )
    );
    foreach ($headers as $key => $vals) {
      $res->addHeader($key, $vals);
    }
    $res->sendHeaders();
    exit(0);
  }
}
