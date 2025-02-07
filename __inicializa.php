<?php

use base\conexion;
use gamboamartin\errores\errores;

$_SESSION['usuario_id'] = 2;

require "init.php";
require 'vendor/autoload.php';

$con = new conexion();
$link = conexion::$link;

$link->beginTransaction();

$administrador = new \gamboamartin\administrador\instalacion\instalacion();

$instala = $administrador->instala(link: $link);

if(errores::$error){
    $link->rollBack();
    $error = (new errores())->error(mensaje: 'Error al instalar administrador', data: $instala);
    print_r($error);
    exit;
}

$direccion_postal = new gamboamartin\direccion_postal\instalacion\instalacion();

$instala = $direccion_postal->instala(link: $link);

if(errores::$error){
    $link->rollBack();
    $error = (new errores())->error(mensaje: 'Error al instalar direccion_postal', data: $instala);
    print_r($error);
    exit;
}

print_r($instala);

$cat_sat = new gamboamartin\cat_sat\instalacion\instalacion(link: $link);

$instala = $cat_sat->instala(link: $link);

if(errores::$error){
    $link->rollBack();
    $error = (new errores())->error(mensaje: 'Error al instalar cat_sat', data: $instala);
    print_r($error);
    exit;
}

print_r($instala);



$link->commit();


