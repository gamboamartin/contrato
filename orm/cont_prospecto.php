<?php

namespace gamboamartin\contrato\models;
use base\orm\_modelo_parent;
use PDO;

class cont_prospecto extends _modelo_parent {
    public function __construct(PDO $link){
        $tabla = 'cont_prospecto';
        $columnas = array($tabla=>false,'doc_documento'=>$tabla);

        parent::__construct(link: $link,tabla:  $tabla, columnas: $columnas);
        $this->NAMESPACE = __NAMESPACE__;
    }
}