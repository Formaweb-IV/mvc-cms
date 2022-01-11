<?php
namespace App;

//Inicializo sesión para poder traspasar variables entre páginas
session_start();

//Incluyo los controladores que voy a utilizar para que seran cargados por Autoload
use App\Controller\AppController;
use App\Controller\NovaController;
use App\Controller\UsuarioController;

/*
 * Asigno a sesión las rutas de las carpetas public y home, necesarias tanto para las rutas como para
 * poder enlazar imágenes y archivos css, js
 */
$_SESSION['public'] = '/';
$_SESSION['home'] = $_SESSION['public'].'index.php/';

//Defino y llamo a la función que autocargará las clases cuando se instancien
spl_autoload_register('App\autoload');

function autoload($clase,$dir=null){

    //Directorio raíz de mi proyecto
    if (is_null($dir)){
        $dirname = str_replace('\public', '', dirname(__FILE__));
        $dir = realpath($dirname);
    }

    //Escaneo en busca da clase de forma recursiva
    foreach (scandir($dir) as $file){
        //Si é un directorio (e non é de sistema) acceder e
        //buscar a clase dentro del
        if (is_dir($dir."/".$file) AND substr($file, 0, 1) !== '.'){
            autoload($clase, $dir."/".$file);
        }
        //Si é un arquivo e o seu nome coincide co da clase
        else if (is_file($dir."/".$file) AND $file == substr(strrchr($clase, "\\"), 1).".php"){
            require($dir."/".$file);
        }
    }

}

//Para invocar ao controlador en cada ruta
function controller($nombre=null){

    switch($nombre){
        default: return new AppController;
        case "novas": return new NovaController;
        case "usuarios": return new UsuarioController;
    }

}

//Quitar a ruta da home da que se está pidiendo
$ruta = str_replace($_SESSION['home'], '', $_SERVER['REQUEST_URI']);

//Encamiñar cada ruta ao controlador e acción correspondentes
switch ($ruta){

    //Front-end
    case "":
    case "/":
        controller()->index();
        break;
    case "acercade":
        controller()->acercade();
        break;
    case "contacto":
        controller()->contacto();
        break;
    case "novas":
        controller()->novas();
        break;
    case (strpos($ruta,"nova/") === 0):
        controller()->nova(str_replace("nova/","",$ruta));
        break;

    //Back-end
    case "admin":
    case "admin/entrar":
        controller("usuarios")->entrar();
        break;
    case "admin/salir":
        controller("usuarios")->salir();
        break;
    case "admin/usuarios":
        controller("usuarios")->index();
        break;
    case "admin/usuarios/crear":
        controller("usuarios")->crear();
        break;
    case (strpos($ruta,"admin/usuarios/editar/") === 0):
        controller("usuarios")->editar(str_replace("admin/usuarios/editar/","",$ruta));
        break;
    case (strpos($ruta,"admin/usuarios/activar/") === 0):
        controller("usuarios")->activar(str_replace("admin/usuarios/activar/","",$ruta));
        break;
    case (strpos($ruta,"admin/usuarios/borrar/") === 0):
        controller("usuarios")->borrar(str_replace("admin/usuarios/borrar/","",$ruta));
        break;
    case "admin/novas":
        controller("novas")->index();
        break;
    case "admin/novas/crear":
        controller("novas")->crear();
        break;
    case (strpos($ruta,"admin/novas/editar/") === 0):
        controller("novas")->editar(str_replace("admin/novas/editar/","",$ruta));
        break;
    case (strpos($ruta,"admin/novas/activar/") === 0):
        controller("novas")->activar(str_replace("admin/novas/activar/","",$ruta));
        break;
    case (strpos($ruta,"admin/novas/home/") === 0):
        controller("novas")->home(str_replace("admin/novas/home/","",$ruta));
        break;
    case (strpos($ruta,"admin/novas/borrar/") === 0):
        controller("novas")->borrar(str_replace("admin/novas/borrar/","",$ruta));
        break;
    case (strpos($ruta,"admin/") === 0):
        controller("usuarios")->entrar();
        break;

    //Resto de rutas
    default:
        controller()->index();

}