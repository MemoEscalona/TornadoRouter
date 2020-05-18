<?php
    namespace TornadoRouter;
    /**
     * Clase que modela el objeto request
     */
    class Request {
        public $params;
        public $data;
        public $format;
        public $accepted_formats;
        public $encodings;
        public $charsets;
        public $languages;
        public $version;
        public $method;
        public $clientIP;
        public $userAgent;
        public $protocol;
        /**
         * Constructor del objeto Request
         * Obtiene el metodo del request actual
         * Saca el metadata
         * Obtiene el cuerpo de la peticion, si es GET se asigna Data. Si es de otro se obtiene el input y se le asigna a data
         */
        function __construct() {
            $this->method = Router::getRequestMethod();
            $this->grabRequestMetadata();
            switch ($this->method) {
                case "GET":
                    $this->data = $_GET;
                    break;
                default:
                    $contents = file_get_contents('php://input');
                    $parsed_contents = null;
                    parse_str($contents, $parsed_contents);
                    $this->data = $_GET + $_POST + $parsed_contents;
                    $this->data['_RAW_HTTP_DATA'] = $contents;
            }

        }
        /**
         * Obtiene el metadata de la peticón
         * Ip de cliente
         * user Agent
         * protocol
         * encoddings
         * charset
         * languages
         */
        protected function grabRequestMetadata() {
            $this->clientIP = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
            $this->clientIP = (empty($this->clientIP) && !empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';

            $this->userAgent = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
            $this->protocol = !empty($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : null;

            $this->parse_special('encodings', 'HTTP_ACCEPT_ENCODING', array('utf-8'));
            $this->parse_special('charsets', 'HTTP_ACCEPT_CHARSET', array('text/html'));
            $this->parse_special('accepted_formats', 'HTTP_ACCEPT');
            $this->parse_special('languages', 'HTTP_ACCEPT_LANGUAGE', array('en-US'));
        }
        /**
         * Formato de salida, si lo hay.
         * El formato en la cadena de solicitud de URL tiene prioridad sobre el de los encabezados HTTP, predeterminado en HTML
         */
        protected function contentNego() {
            if (!empty($this->data['format'])) {
                $this->format = $this->data['format'];
                $aliases = $this->common_aliases();
                if (array_key_exists($this->format, $aliases)) {
                    $this->format = $aliases[$this->format];
                }
                unset($this->data['format']);
            } elseif (!empty($this->accepted_formats[0])) {
                $this->format = $this->accepted_formats[0];
                unset ($this->data['format']);
            }
        }

        /**
         * Metodo que checa si el elmento data esta vacio para evitar warnings de si esta basio o no
         * @param $idx nombre del parametro en request.
         */
        public function get_var($idx) {
            return (is_array($this->data) && isset($this->data[$idx])) ? $this->data[$idx] : null;
        }

        /**
         * Metodo que deuvelve arreglo de los tipos más comunes.
         */
        public function common_aliases() {
            return array(
                'html' => 'text/html',
                'txt' => 'text/plain',
                'css' => 'text/css',
                'js' => 'application/x-javascript',
                'xml' => 'application/xml',
                'rss' => 'application/rss+xml',
                'atom' => 'application/atom+xml',
                'json' => 'application/json',
                'jsonp' => 'text/javascript',
            );
        }
        /**
         * [parse_special description]
         * @param  string $varname - alias de la variable que sera añadida al request actual
         * @param  string $argname - nombre del argumento en $_SERVER
         * @param  array  $default - arreglo default para el objeto
         */
        private function parse_special($varname, $argname, $default=array()) {
            $this->$varname = $default;
            if (!empty($_SERVER[$argname])) {
                // parse before the first ";" character
                $truncated = substr($_SERVER[$argname], 0, strpos($_SERVER[$argname], ";", 0));
                $truncated = !empty($truncated) ? $truncated : $_SERVER[$argname];
                $this->$varname = explode(",", $truncated);
            }
        }

    }