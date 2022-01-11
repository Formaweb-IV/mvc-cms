<?php
namespace App\Model;

class Usuario {
    
    //Variables o atributos
    var $id;
    var $usuario;
    var $contrasinal;
    var $data_acceso;
    var $activo;
    var $usuarios;
    var $novas;

    function __construct($datat=null){
        
        $this->id = ($datat) ? $datat->id : null;
        $this->usuario = ($datat) ? $datat->usuario : null;
        $this->contrasinal = ($datat) ? $datat->contrasinal : null;
        $this->data_acceso = ($datat) ? $datat->data_acceso : null;
        $this->activo = ($datat) ? $datat->activo : null;
        $this->usuarios = ($datat) ? $datat->usuarios : null;
        $this->novas = ($datat) ? $datat->novas : null;
        
    }

}