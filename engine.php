<?php

$uri = $_SERVER['REQUEST_URI'];
$partes = array_filter( explode('/',$uri) );
print_r( $partes );