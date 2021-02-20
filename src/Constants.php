<?php

namespace TornadoRouter;

/**
* Clase que almacena las constantes.
* Las constantes de esta biblioteca son las expresiones regulares más comunes para los parametros de las URIS.
*/
final class Constants {
    const PATTERN_ARGS       = '?(?P<%s>(?:/.+)+)';
    const PATTERN_ARGS_ALPHA = '?(?P<%s>(?:/[-\w]+)+)';
    const PATTERN_WILD_CARD  = '(?P<%s>.*)';
    const PATTERN_ANY        = '(?P<%s>(?:/?[^/]*))';
    const PATTERN_ALPHA      = '(?P<%s>(?:/?[-\w]+))';
    const PATTERN_NUM        = '(?P<%s>\d+)';
    const PATTERN_DIGIT      = '(?P<%s>\d+)';
    const PATTERN_YEAR       = '(?P<%s>\d{4})';
    const PATTERN_MONTH      = '(?P<%s>\d{1,2})';
    const PATTERN_DAY        = '(?P<%s>\d{1,2})';
    const PATTERN_MD5        = '(?P<%s>[a-z0-9]{32})';
}