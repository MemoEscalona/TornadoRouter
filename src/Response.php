<?php
    namespace TornadoRouter;
    /**
     * Clase que modela el objeto response
     */
    class Response {
        public $chunks = array();
        public $code = 200;
        private $format;
        private $req;
        private $headers = array();
        /**
         * Constructur de la clase
         * @param Request $request Se añade el objeto request del response
         */
        function __construct($request = null) {
            $this->req = $request;
        }

        /**
         * Añede un string al buffer de salida
         */
        public function add($out) {
            $this->chunks[]  = $out;
            return $this;
        }
        /**
         * Permite agregar un código y el formato y termina la ejecurción del script
         * @param  Int $code Código HTTP que se va enviar en la respuesta
         * @param  String $format Fromato de la respuesta
         */
        public function send($code = null, $format = null) {
            $this->flush($code, $format);
            exit(); 
        }
        /**
         * Envía el output al cliente sin acabar el script
         *  @param integer $code HTTP response Code. Defaults es 200
         *  @param string $format MimtType de salida. Defaults el formato request
         *  @return Response current respons object, so you can chain method calls on a response object.
         */
        public function flush($code = null, $format = null) {
            $this->verifyResponseCode($code);
            if (!empty($format)) {
                if (headers_sent()) {
                    throw new \Exception("Response format already sent: {$this->format}");
                }
                $this->setFormat($format);
            }
            if (empty($this->format)) { $this->format = $this->req->format; }
            if (empty($this->code)) { $this->code = 200; }
            $this->sendHeaders();
            foreach (Router::$middleware as $m) {
                if ($m->shouldRun()) {
                    $m->prerender($this->chunks);
                }
            }
            $out = implode('', $this->chunks);
            $this->chunks = array();
            echo ($out);
            return $this;
        }
        /**
         * Verifica el codigo http
         * @param  Int $code descripción
         */
        protected function verifyResponseCode($code) {
            if (!empty($code)) {
                if (headers_sent()) {
                    throw new \Exception("Response code already sent: {$this->code}");
                }
                $codes = $this->codes();
                if (array_key_exists($code, $codes)) {
                    //$protocol = $this->req->protocol;
                    $this->code = $code;
                } else {
                    throw new \Exception("Invalid Response Code: $code");
                }
            }
        }
        /**
         * Establece el formato de salida
         */
        public function setFormat($format) {
            $aliases = $this->req->common_aliases();
            if (array_key_exists($format, $aliases)) {
                $format = $aliases[$format];
            }
            $this->format = $format;
            return $this;
        }
        /**
         * Devuelve el formato actual
         * @return String formato
         */
        public function getFormat() {
            return $this->format;
        }

        /**
        * Agrega una cabecer HTTP key/value pair
        * $key string
        * $val string
        */
        public function addHeader($key, $val) {
            if (is_array($val)) {
                $val = implode(", ", $val);
            }
            $this->headers[] = "{$key}: $val";
            return $this;
        }

        /**
         * Envía las cabeceras al navegaadro de no hacer cache de este contenido
         * See http://stackoverflow.com/a/2068407
         */
        public function disableBrowserCache() {
            $this->headers[] = 'Cache-Control: no-cache, no-store, must-revalidate'; // HTTP 1.1.
            $this->headers[] = 'Pragma: no-cache'; // HTTP 1.0.
            $this->headers[] = 'Expires: Thu, 26 Feb 1970 20:00:00 GMT'; // Proxies.
            return $this;
        }
        /**
         *  Envía toda la cabeceras si no han sido enviadas
         */
        public function sendHeaders($noContentType = false) {
            if (!headers_sent()) {
                foreach ($this->headers as $header) {
                    header($header);
                }
                if ($noContentType == false) {
                    header("Content-Type: $this->format;", true, $this->code);
                }
            }
        }
        /**
         * Devuelve un arreglo con los codigos http
         * @return Array
         */
        private function codes() {
            return array(
                '100' => 'Continue',
                '101' => 'Switching Protocols',
                '200' => 'OK',
                '201' => 'Created',
                '202' => 'Accepted',
                '203' => 'Non-Authoritative Information',
                '204' => 'No Content',
                '205' => 'Reset Content',
                '206' => 'Partial Content',
                '300' => 'Multiple Choices',
                '301' => 'Moved Permanently',
                '302' => 'Found',
                '303' => 'See Other',
                '304' => 'Not Modified',
                '305' => 'Use Proxy',
                '307' => 'Temporary Redirect',
                '400' => 'Bad Request',
                '401' => 'Unauthorized',
                '402' => 'Payment Required',
                '403' => 'Forbidden',
                '404' => 'Not Found',
                '405' => 'Method Not Allowed',
                '406' => 'Not Acceptable',
                '407' => 'Proxy Authentication Required',
                '408' => 'Request Timeout',
                '409' => 'Conflict',
                '410' => 'Gone',
                '411' => 'Length Required',
                '412' => 'Precondition Failed',
                '413' => 'Request Entity Too Large',
                '414' => 'Request-URI Too Long',
                '415' => 'Unsupported Media Type',
                '416' => 'Requested Range Not Satisfiable',
                '417' => 'Expectation Failed',
                '429' => 'Too Many Requests',
                '500' => 'Internal Server Error',
                '501' => 'Not Implemented',
                '502' => 'Bad Gateway',
                '503' => 'Service Unavailable',
                '504' => 'Gateway Timeout',
                '505' => 'HTTP Version Not Supported',
            );
        }
    }