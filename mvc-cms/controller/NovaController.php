<?php
namespace App\Controller;

use App\Helper\ViewHelper;
use App\Helper\DbHelper;
use App\Model\Nova;


class NovaController
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

    //Listado de novas
    public function index(){

        //Permisos
        $this->view->permisos("novas");

        //Recojo las novas de la base de datos
        $rowset = $this->db->query("SELECT * FROM novas ORDER BY datat DESC");

        //Asigno resultados a un array de instancias del modelo
        $novas = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($novas,new Nova($row));
        }

        $this->view->vista("admin","novas/index", $novas);

    }

    //Para activar o desactivar
    public function activar($id){

        //Permisos
        $this->view->permisos("novas");

        //Obtengo la nova
        $rowset = $this->db->query("SELECT * FROM novas WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $nova = new Nova($row);

        if ($nova->activo == 1){

            //Desactivo la nova
            $consulta = $this->db->exec("UPDATE novas SET activo=0 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/novas","green","La nova <strong>$nova->titulo</strong> se ha desactivado correctamente.") :
                $this->view->redireccionConMensaje("admin/novas","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Activo la nova
            $consulta = $this->db->exec("UPDATE novas SET activo=1 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/novas","green","La nova <strong>$nova->titulo</strong> se ha activado correctamente.") :
                $this->view->redireccionConMensaje("admin/novas","red","Hubo un error al guardar en la base de datos.");
        }

    }

    //Para mostrar o no en la home
    public function home($id){

        //Permisos
        $this->view->permisos("novas");

        //Obtengo la nova
        $rowset = $this->db->query("SELECT * FROM novas WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $nova = new Nova($row);

        if ($nova->home == 1){

            //Quito la nova de la home
            $consulta = $this->db->exec("UPDATE novas SET home=0 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/novas","green","La nova <strong>$nova->titulo</strong> ya no se muestra en la home.") :
                $this->view->redireccionConMensaje("admin/novas","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Muestro la nova en la home
            $consulta = $this->db->exec("UPDATE novas SET home=1 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/novas","green","La nova <strong>$nova->titulo</strong> ahora se muestra en la home.") :
                $this->view->redireccionConMensaje("admin/novas","red","Hubo un error al guardar en la base de datos.");
        }

    }

    public function borrar($id){

        //Permisos
        $this->view->permisos("novas");

        //Obtengo la nova
        $rowset = $this->db->query("SELECT * FROM novas WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $nova = new Nova($row);

        //Borro la nova
        $consulta = $this->db->exec("DELETE FROM novas WHERE id='$id'");

        //Borro la imaxe asociada
        $archivo = $_SESSION['public']."img/".$nova->imaxe;
        $texto_imaxe = "";
        if (is_file($archivo)){
            unlink($archivo);
            $texto_imaxe = " y se ha borrado la imaxe asociada";
        }

        //Mensaje y redirección
        ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
            $this->view->redireccionConMensaje("admin/novas","green","La nova se ha borrado correctamente$texto_imaxe.") :
            $this->view->redireccionConMensaje("admin/novas","red","Hubo un error al guardar en la base de datos.");

    }

    public function crear(){

        //Permisos
        $this->view->permisos("novas");

        //Creo unha nova nova baleira
        $nova = new Nova();

        //Llamo a la ventana de edición
        $this->view->vista("admin","novas/editar", $nova);

    }

    public function editar($id){

        //Permisos
        $this->view->permisos("novas");

        //Si ha pulsado el botón de guardar
        if (isset($_POST["guardar"])){

            //Recupero los datos del formulario
            $titulo = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_STRING);
            $extracto = filter_input(INPUT_POST, "extracto", FILTER_SANITIZE_STRING);
            $autor = filter_input(INPUT_POST, "autor", FILTER_SANITIZE_STRING);
            $datat = filter_input(INPUT_POST, "datat", FILTER_SANITIZE_STRING);
            $texto = filter_input(INPUT_POST, "texto", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            //Formato de data para SQL
            $datat = \DateTime::createFromFormat("d-m-Y", $datat)->format("Y-m-d H:i:s");

            //Xerar slug (url amigable)
            $slug = $this->view->getSlug($titulo);

            //Imaxe
            $imaxe_recibida = $_FILES['imaxe'];
            $imaxe = ($_FILES['imaxe']['name']) ? $_FILES['imaxe']['name'] : "";
            $imaxe_subida = ($_FILES['imaxe']['name']) ? '/var/www/html'.$_SESSION['public']."img/".$_FILES['imaxe']['name'] : "";
            $texto_img = ""; //Para a mensaxe

            if ($id == "nuevo"){

                //Creo unha nova nova
                $consulta = $this->db->exec("INSERT INTO novas 
                    (titulo, extracto, autor, datat, texto, slug, imaxe) VALUES 
                    ('$titulo','$extracto','$autor','$datat','$texto','$slug','$imaxe')");

                //Subo la imaxe
                if ($imaxe){
                    if (is_uploaded_file($imaxe_recibida['tmp_name']) && move_uploaded_file($imaxe_recibida['tmp_name'], $imaxe_subida)){
                        $texto_img = " La imaxe se ha subido correctamente.";
                    }
                    else{
                        $texto_img = " Hubo un problema al subir la imaxe.";
                    }
                }

                //Mensaje y redirección
                ($consulta > 0) ?
                    $this->view->redireccionConMensaje("admin/novas","green","La nova <strong>$titulo</strong> se creado correctamente.".$texto_img) :
                    $this->view->redireccionConMensaje("admin/novas","red","Hubo un error al guardar en la base de datos.");
            }
            else{

                //Actualizo a nova
                $this->db->exec("UPDATE novas SET 
                    titulo='$titulo',extracto='$extracto',autor='$autor',
                    datat='$datat',texto='$texto',slug='$slug' WHERE id='$id'");

                //Subo e actualizo a imaxe
                if ($imaxe){
                    if (is_uploaded_file($imaxe_recibida['tmp_name']) && move_uploaded_file($imaxe_recibida['tmp_name'], $imaxe_subida)){
                        $texto_img = " La imaxe se ha subido correctamente.";
                        $this->db->exec("UPDATE novas SET imaxe='$imaxe' WHERE id='$id'");
                    }
                    else{
                        $texto_img = " Hubo un problema al subir la imaxe.";
                    }
                }

                //Mensaxe e redirección
                $this->view->redireccionConMensaje("admin/novas","green","La nova <strong>$titulo</strong> se guardado correctamente.".$texto_img);

            }
        }

        //Si non, obteño nova e amoso a xanela de edición
        else{

            //Obteño a nova
            $rowset = $this->db->query("SELECT * FROM novas WHERE id='$id' LIMIT 1");
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $nova = new Nova($row);

            //Chamo á xanela de edición
            $this->view->vista("admin","novas/editar", $nova);
        }

    }

}