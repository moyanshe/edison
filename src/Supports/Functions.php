<?php

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

if (!function_exists('dd')) {
    function dd($value) {
        if (class_exists(CliDumper::class)) {
            $dumper = 'cli' === PHP_SAPI ? new CliDumper : new HtmlDumper();

            $dumper->dump((new VarCloner)->cloneVar($value));
        } else {
            var_dump($value);
        }
        exit();
    }
}