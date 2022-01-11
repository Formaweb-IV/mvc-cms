<?php
namespace App\Controller;

use App\Helper\ViewHelper;
use App\Helper\DbHelper;
use App\Model\Usuario;


class UsuarioController
{
    var $db;
    var $view;

    function __construct()
    {
        //Conexión a la BBDD
        $dbHelper = new DbHelper();
        $this->db = $dbHelper->db;

        //Instancio el ViewHelper
        $viewHelper = new ViewHelper();
        $this->view = $viewHelper;
    }

    public function admin(){

        //Compruebo permisos
        $this->view->permisos();

        //LLamo a la vista
        $this->view->vista("admin","index");

    }

    public function entrar(){

        //Si xa está autenticado, o levo á páxina de inicio do panel
        if (isset($_SESSION['usuario'])){

            $this->admin();

        }
        //Si ha pulsado el botón de acceder, tramito el formulario
        else if (isset($_POST["acceder"])){

            //Recupero os datos do formulario
            $campo_usuario = filter_input(INPUT_POST, "usuario", FILTER_SANITIZE_STRING);
            $campo_contrasinal = filter_input(INPUT_POST, "contrasinal", FILTER_SANITIZE_STRING);

            //Busco ao usuario na base de datos
            $rowset = $this->db->query("SELECT * FROM usuarios WHERE usuario='$campo_usuario' AND activo=1 LIMIT 1");

            //Asigno o resultado a unha instancia do modelo
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $usuario = new Usuario($row);

            //Si existe o usuario
            if ($usuario){
                //Comprobo o contrasinal
                if (password_verify($campo_contrasinal,$usuario->contrasinal)) {

                    //Asigno o usuario e os permisos da sesión
                    $_SESSION["usuario"] = $usuario->usuario;
                    $_SESSION["usuarios"] = $usuario->usuarios;
                    $_SESSION["novas"] = $usuario->novas;

                    //Gardo a data do último acceso
                    $ahora = new \DateTime("now", new \DateTimeZone("Europe/Madrid"));
                    $fecha = $ahora->format("Y-m-d H:i:s");
                    $this->db->exec("UPDATE usuarios SET data_acceso='$fecha' WHERE usuario='$campo_usuario'");

                    //Redirección con mensaxe
                    $this->view->redireccionConMensaje("admin","green","Benvido ao taboleiro de administración.");
                }
                else{
                    //Redirección con mensaxe
                    $this->view->redireccionConMensaje("admin","red","Contrasenal incorrecto.");
                }
            }
            else{
                //Redirección con mensaxe
                $this->view->redireccionConMensaje("admin","red","No existe ningún usuario con ese nombre.");
            }
        }
        //O levo á páxina de acceso
        else{
            $this->view->vista("admin","usuarios/entrar");
        }

    }

    public function salir(){

        //Borro ao usuario da sesión
        unset($_SESSION['usuario']);

        //Redirección con mensaxe
        $this->view->redireccionConMensaje("admin","green","Te has desconectado con éxito.");

    }

    //Listado de usuarios
    public function index(){

        //Permisos
        $this->view->permisos("usuarios");

        //Recollo os usuarios da base de datos
        $rowset = $this->db->query("SELECT * FROM usuarios ORDER BY usuario ASC");

        //Asigno resultados a un array de instancias do modelo
        $usuarios = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($usuarios,new Usuario($row));
        }

        $this->view->vista("admin","usuarios/index", $usuarios);

    }

    //Para activar ou desactivar
    public function activar($id){

        //Permisos
        $this->view->permisos("usuarios");

        //Obtengo el usuario
        $rowset = $this->db->query("SELECT * FROM usuarios WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $usuario = new Usuario($row);

        if ($usuario->activo == 1){

            //Desactivo o usuario
            $consulta = $this->db->exec("UPDATE usuarios SET activo=0 WHERE id='$id'");

            //Mensaxe e redirección
            ($consulta > 0) ? //Comprobo consulta para ver que non houbo erros
                $this->view->redireccionConMensaje("admin/usuarios","green","O usuario <strong>$usuario->usuario</strong> desactivouse correctamente.") :
                $this->view->redireccionConMensaje("admin/usuarios","red","Houbo un erro ao guardar na base de datos.");
        }

        else{

            //Activo o usuario
            $consulta = $this->db->exec("UPDATE usuarios SET activo=1 WHERE id='$id'");

            //Mensaxe e redirección
            ($consulta > 0) ? //Comprobo consulta para ver que non houbo erros
                $this->view->redireccionConMensaje("admin/usuarios","green","El usuario <strong>$usuario->usuario</strong> se ha activado correctamente.") :
                $this->view->redireccionConMensaje("admin/usuarios","red","Hubo un error al guardar en la base de datos.");
        }

    }

    public function borrar($id){

        //Permisos
        $this->view->permisos("usuarios");

        //Borro el usuario
        $consulta = $this->db->exec("DELETE FROM usuarios WHERE id='$id'");

        //Mensaje y redirección
        ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
            $this->view->redireccionConMensaje("admin/usuarios","green","El usuario se ha borrado correctamente.") :
            $this->view->redireccionConMensaje("admin/usuarios","red","Hubo un error al guardar en la base de datos.");

    }

    public function crear(){

        //Permisos
        $this->view->permisos("usuarios");

        //Creo un nuevo usuario vacío
        $usuario = new Usuario();

        //Llamo a la ventana de edición
        $this->view->vista("admin","usuarios/editar", $usuario);

    }

    public function editar($id){

        //Permisos
        $this->view->permisos("usuarios");

        //Si pulsaches o botón de gardar
        if (isset($_POST["guardar"])){

            //Recupero os datos do formulario
            $usuario = filter_input(INPUT_POST, "usuario", FILTER_SANITIZE_STRING);
            $contrasinal = filter_input(INPUT_POST, "contrasinal", FILTER_SANITIZE_STRING);
            $usuarios = (filter_input(INPUT_POST, 'usuarios', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;
            $novas = (filter_input(INPUT_POST, 'novas', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;
            $cambiar_contrasinal = (filter_input(INPUT_POST, 'cambiar_contrasinal', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;

            //Encripto o contrasinal
            $contrasinal_encriptada = ($contrasinal) ? password_hash($contrasinal,  PASSWORD_BCRYPT, ['cost'=>12]) : "";

            if ($id == "nuevo"){

                //Creo un novo usuario
                $this->db->exec("INSERT INTO usuarios (usuario, contrasinal, novas, usuarios) VALUES ('$usuario','$contrasinal_encriptada',$novas,$usuarios)");

                //Mensaxe e redirección
                $this->view->redireccionConMensaje("admin/usuarios","green","O usuario <strong>$usuario</strong> creouse correctamente.");
            }
            else{

                //Actualizo o usuario
                ($cambiar_contrasinal) ?
                    $this->db->exec("UPDATE usuarios SET usuario='$usuario',contrasinal='$contrasinal_encriptada',novas=$novas,usuarios=$usuarios WHERE id='$id'") :
                    $this->db->exec("UPDATE usuarios SET usuario='$usuario',novas=$novas,usuarios=$usuarios WHERE id='$id'");

                //Mensaxe e redirección
                $this->view->redireccionConMensaje("admin/usuarios","green","O usuario <strong>$usuario</strong> actualizouse correctamente.");
            }
        }

        //Si non, obteño usuario e mostro a ventana de edición
        else{

            //Obteño o usuario
            $rowset = $this->db->query("SELECT * FROM usuarios WHERE id='$id' LIMIT 1");
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $usuario = new Usuario($row);

            //Chamo á xanela de edición
            $this->view->vista("admin","usuarios/editar", $usuario);
        }

    }


}