<?php

namespace {

    function concat_ws($delimiter, ...$args)
    {
        return implode($delimiter, $args);
    }
}

namespace ns {

    function concat_ws($delimiter, ...$args)
    {
        return implode($delimiter, array_reverse($args));
    }
}
