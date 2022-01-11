<?php
namespace App\Controller;

use App\Model\Nova;
use App\Helper\ViewHelper;
use App\Helper\DbHelper;


class AppController
{
    var $db;
    var $view;

    function __construct()
    {
        //Conexión coa BBDD
        $dbHelper = new DbHelper();
        $this->db = $dbHelper->db;

        //Instanciar ViewHelper
        $viewHelper = new ViewHelper();
        $this->view = $viewHelper;
    }

    public function index(){

        //Consultar á bbdd
        $rowset = $this->db->query("SELECT * FROM novas WHERE activo=1 AND home=1 ORDER BY datat DESC");

        //Asignar resultados a un array de instancias do modelo
        $novas = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($novas,new Nova($row));
        }

        //Chamar á vista
        $this->view->vista("app", "index", $novas);
    }

    public function acercade(){

        //Chamar á vista
        $this->view->vista("app", "acercade");

    }

    public function contacto(){

        //Chamar á  vista
        $this->view->vista("app", "contacto");

    }

    public function novas(){

        //Consultar á bbdd
        $rowset = $this->db->query("SELECT * FROM novas WHERE activo=1 ORDER BY datat DESC");

        //Asignar os resultados a un array de instancias do modelo
        $novas = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($novas,new Nova($row));
        }

        //Chamar á vista
        $this->view->vista("app", "novas", $novas);

    }

    public function nova($slug){

        //Consultar á bbdd
        $rowset = $this->db->query("SELECT * FROM novas WHERE activo=1 AND slug='$slug' LIMIT 1");

        //Asignar o resultado a unha instancia do modelo
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $nova = new Nova($row);

        //Chamar á vista
        $this->view->vista("app", "nova", $nova);

    }
}