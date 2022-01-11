<?php
namespace App\Model;

class Nova
{
    //Variables o atributos
    var $id;
    var $titulo;
    var $slug;
    var $extracto;
    var $texto;
    var $activo;
    var $home;
    var $datat;
    var $autor;
    var $imaxe;

    function __construct($datat=null){

        $this->id = ($datat) ? $datat->id : null;
        $this->titulo = ($datat) ? $datat->titulo : null;
        $this->slug = ($datat) ? $datat->slug : null;
        $this->extracto = ($datat) ? $datat->extracto : null;
        $this->texto = ($datat) ? $datat->texto : null;
        $this->activo = ($datat) ? $datat->activo : null;
        $this->home = ($datat) ? $datat->home : null;
        $this->datat = ($datat) ? $datat->datat : null;
        $this->autor = ($datat) ? $datat->autor : null;
        $this->imaxe = ($datat) ? $datat->imaxe : null;

    }

}