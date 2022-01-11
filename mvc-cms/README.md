# Crear un pequeno CMS con PHP

0. **Contidos**

1. **Obxectivos**

2. **Estrutura**

3. **A base de datos**

4. **O controlador central**

5. **O cartafol ``helper``**

6. **O cartafol ``model``**

7. **``AppController``**

8. **Vistas do front-end**

9. **``UsuarioController``**

10. **Vistas do back-end (usuarios)**

11. **``NoticiaController``**

12. **Vistas do back-end (novas)**

13. **Conclusións**

14. **Práctica: CMS desde 0**

15. **Apéndice I**

    

## 0. Obxectivos

Crear un pequeno blog. O sitio debe ter:

1. Unha zona pública ou *front-end* coas seguintes características:

   - Layout con cabeceira, menú e pé de páxina común a tódalas páxinas.

   - Páxina principal ou *Home* con contido estático e novas destacadas.
   - Páxina de listado de novas.

   - Páxina de noticia individual con título, ectracto, data, autor, texto e imaxe asociada.

   - Páxina estática de *Acerca de*.

2. Un panel de control o *back-end* coas seguintes características:

   - Acceso restringido mediante usuario e contraseña.

   - Layout con cabeceira, menú e pé de páxina común a tódalas páxinas (excepto á de acceso)

   - Xestión de usuarios.

   - Xestión de noticias.

## 1. Estrutura

   Para organizar os arquivos e traballar segundo o patrón MVC (**modelo vista controlador**) creamos un cartafol **``cms``** e nel os subcartafoles ``controller``,  ``model`` e ``view``, asemade do cartafol ``helper``.

   **IMPORTANTE**: É recomendable que a cartafol **cms** e todos os arquivos que conteña (ou alo menos a carpeta **public/img**) no pertenezcan a **root** senon ao usuario **www-data** do grupo **www-data** para evitar problemas de permisos cando queiramos subir imaxes desde o panel de administración.

   Supoñendo que a carpeta do proxecto se chama **cms**, se beberían executar os comandos seguintes:

   ```bash
   cd /var/www/html
   sudo chown -R www-data:www-data cms/public/img
   ```

   NOTA: É recomendable empregar un control de versións como Git/GitHub.

   O esquema do sitio trá un aspecto así:

   <img src="C:\laragon\www\mvc-cms\assets\1545.jpg" alt="Arquitectura do sitio" style="zoom: 33%;" />

## 2. A base de datos

A base de datos que precisa o noso CMS ten dúas táboas: unha para os **usuarios** e outra para as novas que se vaian publicar. 

### A táboa `usuarios`

Almacenará os datos dos usuarios do CMS, así como os permisos concedidos a cada un deles:

- **id**: é a clave primaria numérica que identifica de maneira única a cada usuario

- **usuario**: é o nome do usuario e tamén será único

- **contrasinal**: é o contrasinal de acceso, almacenado mediante un hash de cadea password_hash() dun só sentido. Inicialmente estará baleiro, pero non se permitirá crear un novo usuario a menos que se complete dito eido.

- **data_acceso**: almacena a data do último acceso do usuario

- **activo**: indica si o usuario está activo (1) ou inactivo (0)

- **usuarios**: indica si o usuario ten acceso (1) ou non (0) á xestión de usuarios

- **novas**: indica si o usuario ten acceso (1) ou non (0) á xestión de novas

A seguir o código SQL para a creación da táboa:

```mysql
USE cms; /* nome da túa bbdd */
CREATE TABLE `usuarios` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(16) NOT NULL,
  `contrasinal` varchar(64) NOT NULL,
  `data_acceso` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '0',
  `usuarios` tinyint(1) NOT NULL DEFAULT '0',
  `novas` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
```

### A táboa `novas`

Almacenará os datos de cada noticia do CMS:

- **id:** é a clave primaria numérica e identifica de maneira única cada noticia

- **titulo:** cadea de texto para o título da noticia

- **slug:** cadea de texto para el slug o enlace personalizado de la noticia

- **extracto**: cadena de texto para la extracto de la noticia.

- **texto**: texto da noticia

- **activo**: indica si a noticia está activa (1) o inactiva (0)

- **home**: indica si a noticia se mostra na home (1) o no (0)

- **datapublicacion**: indica a data de publicación de la noticia (`data` é unha palabra reservada polo que se desaconsella usala en certos contextos)

- **autor**: indica o nome do autor da nova

- **imaxe**: indica a id da imaxe da nova

A seguir o código SQL para a creación da táboa `novas`:

```mysql
USE cms;
CREATE TABLE `novas` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(32) NOT NULL DEFAULT '',
  `slug` varchar(36) DEFAULT '',
  `extracto` varchar(128) DEFAULT '',
  `texto` longtext,
  `activo` tinyint(1) NOT NULL DEFAULT '0',
  `home` tinyint(1) NOT NULL DEFAULT '0',
  `datapublicacion` datetime DEFAULT NULL,
  `autor` varchar(64) DEFAULT NULL,
  `imaxe` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
```

> **Truco**: Lista dos activos dun directorio: desde a consola 
>
> - ``dir /A:D /B > listaCartafol.txt``
> - ``dir /A:D /B /S > listaCartafol.txt`` para listar tamén os subcartafoles - *:eye: con algunhas estruturas se poden xerar arquivos enormes!!*

## 3. O controlador central

As peticións ao sitio web deberán pasar todas polo arquivo ``public/index.php``, pois será o encargado de encamiñar as peticións ás accións e vistas correspondentes mediante un sistema de toma de decisións.

Adicionalmente, este arquivo realizará o autoload ou pre-carga de clases necesarias e definirá unha serie de variables de sesión para o seu uso ao longo das distintas páxinas.

### Rutas

Este arquivo  debe ser capaz de *enrutar* as seguintes peticións para o **front-end**:

- **``/``**: Inicio ou home (http://ruta-ata-o-cartafol-raiz/index.php) cunha presentación da web e un listado de novas destacadas. O estilo será similar ao da páxina do listado de novas.

- **``/acercade``**: Páxina de información do sitio (autor, empresa, etc.)

- **``/novas``**: Listado de novas, ordenado pola data máis recente, con título, extracto, data de publicación, imaxe e enlace á noticia individual.

- **``/nova/slug-da-nova``**: Nova individual coa noticia completa e a imaxe en grande.

- **``calquera-outra-ruta``**: Redirección á ``home``.


E as seguintes para o **back-end**:

- **``/admin``**: Páxina de inicio do panel de administración (unha vez autenticado) con enlaces ás diferentes seccións en función dos permisos. Se o usuario non está autenticado, se o redirixe á páxina de acceso.

- **``/admin/entrar``**: Páxina con formulario acceso ao panel de administración (usuario e contrasinal)

/admin/salir: Ruta para desconectar del panel de administración que después redireccionará a la páxina de acceso.


/admin/usuarios: Listado de usuarios ordenado alfabéticamente e con enlaces a crear, editar, activar e borrar.

/admin/usuarios/crear: Vista de formulario de creación de nuevo usuario con opciones de guardar e descartar.

/admin/usuarios/editar/id: Vista de formulario de edición de usuario con opciones de guardar e descartar.

/admin/usuarios/activar/id: Enlace a activar/desactivar usuario e posterior redirección a listado de usuarios con mensaje de éxito.

/admin/usuarios/borrar/id: Enlace a borrar usuario (previa confirmación) e posterior redirección a listado de usuarios con mensaje de éxito.


/admin/noticias: Listado de noticias ordenado por fecha más reciente, con miniatura e enlaces a crear, editar, activar e borrar.

/admin/noticias/crear: Vista de formulario de creación de nueva noticia con opciones de guardar e descartar.

/admin/noticias/editar/id: Vista de formulario de edición de noticia con opciones de guardar e descartar.

/admin/noticias/activar/id: Enlace a activar/desactivar noticia e posterior redirección a listado de noticias con mensaje de éxito.

/admin/noticias/home/id: Enlace a destacar o no la noticia en la home e posterior redirección a listado de noticias con mensaje de éxito.

/admin/noticias/borrar/id: Enlace a borrar noticia (previa confirmación) e posterior redirección a listado de noticias con mensaje de éxito.


/admin/cualquier-otra-ruta: Redirección a /admin.


Todas as rutas do front-end compartirán o mesmo layout (head, header, nav e footer), ao igual que todas as rutas do back-end, que tamén compartirán o seu propio layout.


Código

Con todo lo anterior, el código del fichero public/index.php podría ser similar al seguinte:

```
<?php
namespace App;

//Inicializo sesión para poder traspasar variables entre páxinas
session_start();

//Incluyo los controladores que voy a utilizar para que seran cargados por Autoload
use App\Controller\AppController;
use App\Controller\NoticiaController;
use App\Controller\UsuarioController;

/*
 * Asigno a sesión las rutas de las carpetas public e home, necesarias tanto para las rutas como para
 * poder enlazar imágenes e arquivo s css, js
 */
$_SESSION['public'] = '/formacion/cms/public/';
$_SESSION['home'] = $_SESSION['public'].'index.php/';

//Defino e llamo a la función que autocargará las clases cuando se instancien
spl_autoload_register('App\autoload');

function autoload($clase,$dir=null){

    //Directorio raíz de mi proyecto
    if (is_null($dir)){
        $dirname = str_replace('/public', '', dirname(__FILE__));
        $dir = realpath($dirname);
    }

    //Escaneo en busca de la clase de forma recursiva
    foreach (scandir($dir) as $file){
        //Si es un directorio (y no es de sistema) accedo y
        //busco la clase dentro de él
        if (is_dir($dir."/".$file) AND substr($file, 0, 1) !== '.'){
            autoload($clase, $dir."/".$file);
        }
        //Si es un fichero e el nombr conicide con el de la clase
        else if (is_file($dir."/".$file) AND $file == substr(strrchr($clase, "\\"), 1).".php"){
            require($dir."/".$file);
        }
    }

}

//Para invocar al controlador en cada ruta
function controller($nombre=null){

    switch($nombre){
        default: return new AppController;
        case "noticias": return new NoticiaController;
        case "usuarios": return new UsuarioController;
    }

}

//Quito la ruta de la home a la que me están pidiendo
$ruta = str_replace($_SESSION['home'], '', $_SERVER['REQUEST_URI']);

//Encamino cada ruta al controlador e acción correspondientes
switch ($ruta){

    //Front-end
    case "":
    case "/":
        controlador()->index();
        break;
    case "acerca-de":
        controlador()->acercade();
        break;
    case "noticias":
        controlador()->noticias();
        break;
    case (strpos($ruta,"noticia/") === 0):
        controlador()->noticia(str_replace("noticia/","",$ruta));
        break;

    //Back-end
    case "admin":
    case "admin/entrar":
        controlador("usuarios")->entrar();
        break;
    case "admin/salir":
        controlador("usuarios")->salir();
        break;
    case "admin/usuarios":
        controlador("usuarios")->index();
        break;
    case "admin/usuarios/crear":
        controlador("usuarios")->crear();
        break;
    case (strpos($ruta,"admin/usuarios/editar/") === 0):
        controlador("usuarios")->editar(str_replace("admin/usuarios/editar/","",$ruta));
        break;
    case (strpos($ruta,"admin/usuarios/activar/") === 0):
        controlador("usuarios")->activar(str_replace("admin/usuarios/activar/","",$ruta));
        break;
    case (strpos($ruta,"admin/usuarios/borrar/") === 0):
        controlador("usuarios")->borrar(str_replace("admin/usuarios/borrar/","",$ruta));
        break;
    case "admin/noticias":
        controlador("noticias")->index();
        break;
    case "admin/noticias/crear":
        controlador("noticias")->crear();
        break;
    case (strpos($ruta,"admin/noticias/editar/") === 0):
        controlador("noticias")->editar(str_replace("admin/noticias/editar/","",$ruta));
        break;
    case (strpos($ruta,"admin/noticias/activar/") === 0):
        controlador("noticias")->activar(str_replace("admin/noticias/activar/","",$ruta));
        break;
    case (strpos($ruta,"admin/noticias/home/") === 0):
        controlador("noticias")->home(str_replace("admin/noticias/home/","",$ruta));
        break;
    case (strpos($ruta,"admin/noticias/borrar/") === 0):
        controlador("noticias")->borrar(str_replace("admin/noticias/borrar/","",$ruta));
        break;
    case (strpos($ruta,"admin/") === 0):
        controlador("usuarios")->entrar();
        break;

    //Resto de rutas
    default:
        controlador()->index();

}
```
view rawcreacion-de-un-cms-006.php hosted with ❤ by GitHub


IMPORTANTE: En caso de usar XAMPP en Windows la línea $dirname = str_replace('/public', '', dirname(__FILE__)); se debe sustituir por $dirname = str_replace('\public', '', dirname(__FILE__));, ya que el sistema de gestión de arquivo s de Windows es diferente que el de Linux.

Nota: Se podrían refactorizar e simplificar las tomas de decisiones de rutas, pero se ha preferido dejarlo así para mejorar la comprensión del funcionamiento.

Puesto que de momento las clases de los controladores están vacías, de momento no podemos comprobar el funcionamiento, si bien podrías crear ya los métodos correspondientes a las acciones e hacer simples echo para comprobar que todo funciona correctamente.

En el seguinte apartado crearemos los dos helpers que vamos a necesitar, uno para la conexión con la base de datos e otro para gestionar las vistas.

## 5 
Introducción

Este directorio contendrá las clases DbHelper e ViewHelper encargadas, respectivamente, de gestionar la conexión con la base de de datos e la llamada a las vistas correspondientes.


DbHelper

Esta clase solo tendrá un construct encargado de generar una conexión a la base de datos que luego podremos utilizar para las diferentes consultas.

Importante: Puesto que va a incluir datos sensibles, es muy importante si utilizamos un sistema de control de versiones acordarnos de ignorarlo para evitar compartir datos sensibles (otra opción sería leer dichos datos de un arquivo  de claves que previamente hubiéramos indicado al CVS que ignorara).

El código podría ser similar al seguinte:

```<?php

namespace App\Helper;

class DbHelper {
    
    var $db;
    
    function __construct(){
        
        //Conexión mediante PDO
        $opciones = [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"];
        try {
            $this->db = new \PDO(
                'mysql:host=localhost;dbname=cms',
                'usuario-cms',
                'introduce-tu-contrasena',
            $opciones);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            echo 'Falló la conexión: ' . $e->getMessage();
        }
        
    }
    
}```
view rawcreacion-de-un-cms-007.php hosted with ❤ by GitHub



ViewHelper

Esta clase se encargará de gestionar las vistas de las diferentes acciones, así como de pasar los datos de los modelos en caso necesario. Adicionalmente, incluirá las llamadas al layout correspondiente.

En este caso, lo realizaremos mediante un método llamado vista que recibirá la carpeta que contendrá las vistas (app o admin) así como el arquivo  de vista a incluir e los datos de los modelos si fuera necesario.

El código sería el seguinte:

<?php
namespace App\Helper;

class ViewHelper {

    function vista($carpeta,$arquivo ,$datos=null){

        //Llamo a la cabecera
        require("../view/$carpeta/partials/header.php");

        //Llamo al contenido
        require ("../view/$carpeta/$arquivo .php");

        //Llamo al pie
        require("../view/$carpeta/partials/footer.php");

    }
    
}
view rawcreacion-de-un-cms-008.php hosted with ❤ by GitHub


En el seguinte apartado generaremos los dos modelos que vamos a utilizar Noticia e Usuario, e que se corresponderán con las tablas de la base de datos noticias e usuarios, respectivamente.

## 6
Introducción

Esta carpeta contendrá las clases correspondientes a los dos modelos, Noticia e Usuario.

En ambos cosas consistirán en un construct que recogerá los datos de la consulta a la base de datos (o null si estoy creando uno nuevo) e los asignará a las distintas variables.


Usuario

<?php
namespace App\Model;

class Usuario {
    
    //Variables o atributos
    var $id;
    var $usuario;
    var $clave;
    var $data_acceso;
    var $activo;
    var $usuarios;
    var $noticias;

    function __construct($datat=null){
        
        $this->id = ($datat) ? $datat->id : null;
        $this->usuario = ($datat) ? $datat->usuario : null;
        $this->clave = ($datat) ? $datat->clave : null;
        $this->data_acceso = ($datad) ? $datad->data_acceso : null;
        $this->activo = ($datad) ? $datad->activo : null;
        $this->usuarios = ($datad) ? $datad->usuarios : null;
        $this->noticias = ($datad) ? $datad->noticias : null;
        
    }

}
view rawcreacion-de-un-cms-009.php hosted with ❤ by GitHub



Noticia

<?php
namespace App\Model;

class Noticia
{
    //Variables o atributos
    var $id;
    var $titulo;
    var $slug;
    var $entradilla;
    var $texto;
    var $activo;
    var $home;
    var $fecha;
    var $autor;
    var $imagen;

    function __construct($datat=null){

        $this->id = ($datat) ? $datat->id : null;
        $this->titulo = ($datat) ? $datat->titulo : null;
        $this->slug = ($datat) ? $datat->slug : null;
        $this->entradilla = ($datat) ? $datat->entradilla : null;
        $this->texto = ($datat) ? $datat->texto : null;
        $this->activo = ($datat) ? $datat->activo : null;
        $this->home = ($datat) ? $datat->home : null;
        $this->fecha = ($datat) ? $datat->fecha : null;
        $this->autor = ($datat) ? $datat->autor : null;
        $this->imagen = ($datat) ? $datat->imagen : null;

    }

}
view rawcreacion-de-un-cms-010.php hosted with ❤ by GitHub


En el seguinte apartado vamos a generar los métodos de las acciones del controlador del front-end (AppController).


## 7
Introducción

En este apartado generaremos las vistas del front-end así como el layout correspondiente.

Adicionalmente incluiremos Materialize para generar la interfaz de usuario, ajustando los estilos necesarios a nuestro caso particular, así como jQuery para crear interactividad.


Contenido de ejemplo

Para poder comprobar el funcionamiento del front-end, dado que aún no tenemos panel de administración, necesitamos generar contenido de ejemplo en la base de datos, algo que podemos hacer de forma sencilla desde la consola de mysql mediante el seguinte comando:

use cms-mvc;
INSERT INTO noticias VALUES (1,
                             "Gryffindor",
                             "gryffindor",
                             "La Casa Gryffindor fue fundada por el célebre mago Godric Gryffindor.",
                             "Godric solo aceptaba en su casa a aquellos magos e brujas que tenían valentía, disposición e coraje, ya que estas son las cualidades de un auténtico Gryffindor. Los colores de esta casa son el dorado e el escarlata e su símbolo es un león. La reliquia más preciada de la casa es la espada de Godric Gryffindor, perteneciente, como su nombre indica, al fundador de la casa. Los estudiantes de esta casa pasan la mayor parte del tiempo en la Torre de Gryffindor, ubicada en el séptimo piso del Castillo de Hogwarts.",
                             1,
                             1,
                             "2019-07-22 00:00:00",
                             "https://harrypotter.fandom.com",
                             "gryffindor.jpg"),
                            (2,
                             "Hufflepuff",
                             "hufflepuff",
                             "La Casa Hufflepuff se encuentra en una bodega en el mismo pasillo subterráneo que en el de la cocina.",
                             "Hufflepuff anteriormente buscaba alumnos que quisieran pertenecer a esa casa de puro consentimiento, aunque actualmente busca alumnos leales, honestos, que no temen al trabajo pesado. La fundadora es nada menos que la bruja, amiga de toda la vida de Rowena Ravenclaw, Helga Hufflepuff. Helga, fue una bruja muy noble, amigable e la principal impulsora de que Hogwarts aceptase a alumnos nacidos de muggles. La principal reliquia de la casa es la copa de Helga Hufflepuff. El símbolo de la casa es un tejón negro e sus colores representativos son el amarillo e el negro carbón.",
                             1,
                             1,
                             "2019-07-23 00:00:00",
                             "https://harrypotter.fandom.com",
                             "hufflepuff.jpg"),
                            (3,
                             "Ravenclaw",
                             "ravenclaw",
                             "La Casa Ravenclaw se encuentra en una torre en el ala oeste del castillo.",
                             "Sus colores son el azul e el bronce. Ravenclaw busca alumnos académicos, estudiosos e que siempre sepan lo que hay que hacer. Fue fundada por la bruja, nacida en la Canada, Rowena Ravenclaw. Supuestamente la principal inventora del nombre, lugar e formato de Hogwarts. Ella misma es la causante de que las escaleras se muevan. Su principal reliquia es la diadema de Rowena Ravenclaw. El símbolo de la casa es el águila, aunque en alguna versión del escudo es un cuervo.",
                             1,
                             0,
                             "2019-07-24 00:00:00",
                             "https://harrypotter.fandom.com",
                             "ravenclaw.jpg"),
                            (4,
                             "Slytherin",
                             "slytherin",
                             "La Casa Slytherin se caracteriza principalmente por la ambición e la astucia.",
                             "Fue fundada por el mago Salazar Slytherin. La Sala Común de esta casa está situada en las mazmorras, pasando por un serie de numerosos pasillos subterráneos. Posiblemente se llega a ellos a través del Vestíbulo de Hogwarts . Específicamente se encuentra debajo del Lago Negro, haciendo que la sala común sea fría e con una tonalidad verdosa, ya que hay ventanas que dan a las aguas. Se accedea ella por una puerta altamente disimulada en un muro de piedra, diciendo una contraseña requerida. La única conocida es Sangre Pura. Su principal reliquia es el guardapelo de Salazar Slytherin. El animal representativo es la serpiente, sus colores son verde e plateado e el elemento es el agua asociada con la astucia e frialdad.",
                             0,
                             0,
                             "2019-07-25 00:00:00",
                             "https://harrypotter.fandom.com",
                             "slytherin.jpg")
;
view rawcreacion-de-un-cms-012.sql hosted with ❤ by GitHub


Adicionalmente, necesitarás añadir  ESTAS 5 IMÁGENES, correspondientes al logo e a las cuatro noticias de demo dentro de la carpeta public/img.


Header

El arquivo  view/app/partials/header.php incluirá lo seguinte:


Etiqueta html (apertura).

Etiqueta head con todas las etiquetas e llamadas a estilos necesarias.

Etiqueta body (apertura).

Etiqueta nav para el menú de navegación e el logo

Etiqueta main(apertura).

Etiqueta header con título e subtítulo.

Etiqueta section(apertura).



```<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Noticias de Harry Potter</title>

        <!--CSS-->
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
        <link rel="stylesheet" type="text/css" href="<?php echo $_SESSION['public'] ?>css/app.css">

    </head>

    <body>
        <nav>
            <div class="nav-wrapper">
                <!--Logo-->
                <a href="<?php echo $_SESSION['home'] ?>" class="brand-logo" title="Inicio">
                    <img src="<?php echo $_SESSION['public'] ?>img/logo.svg" alt="Logo Harry Potter">
                </a>

                <!--Botón menú móviles-->
                <a href="#" data-target="mobile-demo" class="sidenav-trigger"><i class="material-icons">menu</i></a>

                <!--Menú de navegación-->
                <ul id="nav-mobile" class="right hide-on-med-and-down">
                    <li>
                        <a href="<?php echo $_SESSION['home'] ?>" title="Inicio">Inicio</a>
                    </li>
                    <li>
                        <a href="<?php echo $_SESSION['home'] ?>noticias" title="Noticias">Noticias</a>
                    </li>
                    <li>
                        <a href="<?php echo $_SESSION['home'] ?>acerca-de" title="Acerca de">Acerca de</a>
                    </li>
                    <li>
                        <a href="<?php echo $_SESSION['home'] ?>admin" title="Panel de administración"
                           target="_blank" class="grey-text">
                            Admin
                        </a>
                    </li>
                </ul>

            </div>
        </nav>

        <!--Menú de navegación móvil-->
        <ul class="sidenav" id="mobile-demo">
            <li>
                <a href="<?php echo $_SESSION['home'] ?>" title="Inicio">Inicio</a>
            </li>
            <li>
                <a href="<?php echo $_SESSION['home'] ?>noticias" title="Noticias">Noticias</a>
            </li>
            <li>
                <a href="<?php echo $_SESSION['home'] ?>acerca-de" title="Acerca de">Acerca de</a>
            </li>
            <li>
                <a href="<?php echo $_SESSION['home'] ?>admin" title="Panel de administración"
                   target="_blank" class="grey-text">
                    Admin
                </a>
            </li>
        </ul>

        <main>

            <header>
                <h1>Mi primer CMS</h1>
                <h2>con POO, MVC, PHP e MySQL</h2>
            </header>

            <section class="container-fluid">```
view rawcreacion-de-un-cms-013.php hosted with ❤ by GitHub



Footer

El arquivo  view/app/partials/footer.php incluirá lo seguinte:


Etiqueta section (cierre).

Etiqueta main(cierre).

Etiqueta body (cierre).

Etiqueta footer con información institucional e copyright.

Llamadas a scripts necesarias.

Etiqueta html (cierre).



           ```</section>
        </main>
        <footer class="center-align">
            © <?php echo date("Y") ?>
            <a href="https://jairogarciarincon.com" target="_blank" title="Jairo García Rincón">
                Jairo García Rincón
            </a>
        </footer>

    </body>

    <!--JS-->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"
            integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="<?php echo $_SESSION['public'] ?>js/app.js"></script>

</html>```
view rawcreacion-de-un-cms-014.php hosted with ❤ by GitHub



Folla de estilos

Archivo public/css/app.css:

```/* LAYOUT *************************************************************************************************************/

html{
    color: #333;
}

body{
    display: flex;
    min-height: 100vh;
    flex-direction: column;
}

nav{
    background-color: #333;
    box-shadow: none;
    -webkit-box-shadow: none;
    padding: 0 2rem;
}

nav img{
    width: 8rem;
    padding-top: 0.8rem;
}

main {
    flex: 1 0 auto;
    width: 100%;
    max-width: 1920px;
    padding: 0 2rem;
    margin: 0 auto;
}

header h1{
    font-size: 3rem;
    margin: 1rem 0 0 0;
}

header h2{
    font-size: 2rem;
    margin: 1rem 0;
}

footer{
    background-color: #333;
    color: white;
    padding: 1rem 0;
}

footer a{
    color: white;
}

footer a:hover{
    text-decoration: underline;
}

/* SECTION ************************************************************************************************************/

section h3{
    font-size: 1.5rem;
    border-bottom: 1px solid #333;
    padding-bottom: 0.3rem;
    font-weight:bold;
}

section h3 a{
    color: #333;
}

section h3 span{
    font-weight:normal;
    color: #444;
}

article h4{
    font-size: 1.5rem;
}

article .card-info{
    position: relative;
    bottom: 3rem;
    left: 1.5rem;
}

.card.horizontal.small .card-image{
    max-height: 70%;
}

.card.horizontal.small .card-image img{
    margin-top: 20%;
}

.card.horizontal.noticia{
    border: none;
    box-shadow: none;
    -webkit-box-shadow: none;

}```
view rawcreacion-de-un-cms-015.css hosted with ❤ by GitHub



Javascript

Archivo public/js/app.js:

```$(document).ready(function(){

    //Sidenav en móviles
    $('.sidenav').sidenav();

});```
view rawcreacion-de-un-cms-016.js hosted with ❤ by GitHub



Vista de home




Archivo view/app/index.php:

```<h3>Inicio</h3>
<div class="row">
    <?php foreach ($datos as $row){ ?>
        <article class="col m12 l6">
            <div class="card horizontal small">
                <div class="card-image">
                    <img src="<?php echo $_SESSION['public']."img/".$row->imagen ?>" alt="<?php echo $row->titulo ?>">
                </div>
                <div class="card-stacked">
                    <div class="card-content">
                        <h4><?php echo $row->titulo ?></h4>
                        <p><?php echo $row->entradilla ?></p>
                    </div>
                    <div class="card-info">
                        <p><?php echo date("d/m/Y", strtotime($row->fecha)) ?></p>
                    </div>
                    <div class="card-action">
                        <a href="<?php echo $_SESSION['home']."noticia/".$row->slug ?>">Más información</a>
                    </div>
                </div>
            </div>
        </article>
    <?php } ?>
</div>```
view rawcreacion-de-un-cms-035.php hosted with ❤ by GitHub



Vista de noticias




Archivo view/app/novas.php:

```<h3>
    <a href="<?php echo $_SESSION['home'] ?>" title="Inicio">Inicio</a> <span>| Noticias</span>
</h3>
<div class="row">
    <?php foreach ($datos as $row){ ?>
        <article class="col m12 l6">
            <div class="card horizontal small">
                <div class="card-image">
                    <img src="<?php echo $_SESSION['public']."img/".$row->imagen ?>" alt="<?php echo $row->titulo ?>">
                </div>
                <div class="card-stacked">
                    <div class="card-content">
                        <h4><?php echo $row->titulo ?></h4>
                        <p><?php echo $row->entradilla ?></p>
                    </div>
                    <div class="card-info">
                        <p><?php echo date("d/m/Y", strtotime($row->fecha)) ?></p>
                    </div>
                    <div class="card-action">
                        <a href="<?php echo $_SESSION['home']."noticia/".$row->slug ?>">Más información</a>
                    </div>
                </div>
            </div>
        </article>
    <?php } ?>
</div>```
view rawcreacion-de-un-cms-036.php hosted with ❤ by GitHub



Vista de noticia




Archivo view/app/nova.php:

```<h3>
    <a href="<?php echo $_SESSION['home'] ?>" title="Inicio">Inicio</a> <span>| </span>
    <a href="<?php echo $_SESSION['home'] ?>noticias" title="Noticias">Noticias</a> <span>| </span>
    <span><?php echo $datos->titulo ?></span>
</h3>
<div class="row">
    <article class="col s12">
        <div class="card horizontal large noticia">
            <div class="card-image">
                <img src="<?php echo $_SESSION['public']."img/".$datos->imagen ?>" alt="<?php echo $datos->titulo ?>">
            </div>
            <div class="card-stacked">
                <div class="card-content">
                    <h4><?php echo $datos->titulo ?></h4>
                    <p><?php echo $datos->entradilla ?></p>
                    <p><?php echo $datos->texto ?></p>
                    <br>
                    <p>
                        <strong>Fecha</strong>: <?php echo date("d/m/Y", strtotime($datos->fecha)) ?><br>
                        <strong>Autor</strong>: <?php echo $datos->autor ?>
                    </p>
                </div>
            </div>
        </div>
    </article>
</div>```
view rawcreacion-de-un-cms-037.php hosted with ❤ by GitHub



Vista de acerca de




Archivo view/app/acercade.php:

```<h3>
    <a href="<?php echo $_SESSION['home'] ?>" title="Inicio">Inicio</a> <span>| Acerca de</span>
</h3>
<div class="row">
    <i class="large material-icons">info_outline</i>
    <p>
        Esta páxina muestra noticias relacionadas con el universo de Harry Potter.
    </p>
    <p>
        Está desarrollada en PHP con Programación orientada a Objetos, siguiendo el patrón Modelo Vista Controlador y
        utiliza MySQL para la persistencia de datos.
    </p>
</div>```
view rawcreacion-de-un-cms-038.php hosted with ❤ by GitHub



Con esto estaría terminado el front-end de nuestro CMS e todas las rutas deberían funcionar correctamente.

En los seguintes apartados crearemos el panel de administración o back-end de nuestro CMS para poder realizar modificaciones.

## 8
Introducción

UsuarioController será el controlador encargado de gestionar el back-end de nuestro CMS, proporcionando un acceso restringido al mismo e permitiéndonos además realizar la gestión de usuarios (crear, editar, activar o borrar).

Además de invocar los modelos e helpers correspondientes en el construct , utilizaremos dentro de él ocho métodos correspondientes a las acciones, a saber:


admin() para la homedel panel de administración.

entrar() para acceder al panel de administración (muestra el formulario, lo tramita o redirecciona si ya estaba autenticado).

salir() para salir del panel de administración e retornar al formulario de acceso.

index() para el listado de usuarios ordenado alfabéticamente.

crear() para acceder al formulario de de creación de un nuevo usuario.

editar($id) para editar un determinado usuario.

activar($id) para activar o desactivar un determinado usuario.

borrar($id) para borrar a un determinado usuario.



Contenido de ejemplo

Dado que inicialmente no existe ningún usuario con el que hacer pruebas, vamos a añadir mediante la consola SQL algunos usuarios con diferentes permisos:

USE cms;
INSERT INTO usuarios VALUES
    (1,"admin","hash-de-la-password",null,1,1,1),
    (2,"operador1","hash-de-la-password",null,1,0,1),
    (3,"operador2","hash-de-la-password",null,0,0,1)
;
view rawcreacion-de-un-cms-017.sql hosted with ❤ by GitHub

facelo des de phpmyadmin


~~Importante: Dado que las contraseñas la guardaremos mediante la función password_hash() de cadenas de un solo sentido, previamente deberás generar e hacer echo en cualquier arquivo  PHP el hash de tu contraseña mediante la sentencia:~~

~~<?php
echo password_hash("introduce-tu-contrasena",  PASSWORD_BCRYPT, ['cost'=>12]) ?>
?>
view rawceracion-de-un-cms-018.php hosted with ❤ by GitHub~~


Para mayor seguridad, una ve terminado este apartado deberías cambiar las contraseñas introducidas.


UsuarioController

```<?php
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

        //Si ya está autenticado, le llevo a la páxina de inicio del panel
        if (isset($_SESSION['usuario'])){

            $this->admin();

        }
        //Si ha pulsado el botón de acceder, tramito el formulario
        else if (isset($_POST["acceder"])){

            //Recupero los datos del formulario
            $campo_usuario = filter_input(INPUT_POST, "usuario", FILTER_SANITIZE_STRING);
            $campo_clave = filter_input(INPUT_POST, "clave", FILTER_SANITIZE_STRING);

            //Busco al usuario en la base de datos
            $rowset = $this->db->query("SELECT * FROM usuarios WHERE usuario='$campo_usuario' AND activo=1 LIMIT 1");

            //Asigno resultado a una instancia del modelo
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $usuario = new Usuario($row);

            //Si existe el usuario
            if ($usuario){
                //Compruebo la clave
                if (password_verify($campo_clave,$usuario->clave)) {

                    //Asigno el usuario e los permisos la sesión
                    $_SESSION["usuario"] = $usuario->usuario;
                    $_SESSION["usuarios"] = $usuario->usuarios;
                    $_SESSION["noticias"] = $usuario->noticias;

                    //Guardo la fecha de último acceso
                    $ahora = new \DateTime("now", new \DateTimeZone("Europe/Madrid"));
                    $fecha = $ahora->format("Y-m-d H:i:s");
                    $this->db->exec("UPDATE usuarios SET data_acceso='$fecha' WHERE usuario='$campo_usuario'");

                    //Redirección con mensaje
                    $this->view->redireccionConMensaje("admin","green","Bienvenido al panel de administración.");
                }
                else{
                    //Redirección con mensaje
                    $this->view->redireccionConMensaje("admin","red","Contraseña incorrecta.");
                }
            }
            else{
                //Redirección con mensaje
                $this->view->redireccionConMensaje("admin","red","No existe ningún usuario con ese nombre.");
            }
        }
        //Le llevo a la páxina de acceso
        else{
            $this->view->vista("admin","usuarios/entrar");
        }

    }

    public function salir(){

        //Borro al usuario de la sesión
        unset($_SESSION['usuario']);

        //Redirección con mensaje
        $this->view->redireccionConMensaje("admin","green","Te has desconectado con éxito.");

    }

    //Listado de usuarios
    public function index(){

        //Permisos
        $this->view->permisos("usuarios");

        //Recojo los usuarios de la base de datos
        $rowset = $this->db->query("SELECT * FROM usuarios ORDER BY usuario ASC");

        //Asigno resultados a un array de instancias del modelo
        $usuarios = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($usuarios,new Usuario($row));
        }

        $this->view->vista("admin","usuarios/index", $usuarios);

    }

    //Para activar o desactivar
    public function activar($id){

        //Permisos
        $this->view->permisos("usuarios");

        //Obtengo el usuario
        $rowset = $this->db->query("SELECT * FROM usuarios WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $usuario = new Usuario($row);

        if ($usuario->activo == 1){

            //Desactivo el usuario
            $consulta = $this->db->exec("UPDATE usuarios SET activo=0 WHERE id='$id'");

            //Mensaje e redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/usuarios","green","El usuario <strong>$usuario->usuario</strong> se ha desactivado correctamente.") :
                $this->view->redireccionConMensaje("admin/usuarios","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Activo el usuario
            $consulta = $this->db->exec("UPDATE usuarios SET activo=1 WHERE id='$id'");

            //Mensaje e redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/usuarios","green","El usuario <strong>$usuario->usuario</strong> se ha activado correctamente.") :
                $this->view->redireccionConMensaje("admin/usuarios","red","Hubo un error al guardar en la base de datos.");
        }

    }

    public function borrar($id){

        //Permisos
        $this->view->permisos("usuarios");

        //Borro el usuario
        $consulta = $this->db->exec("DELETE FROM usuarios WHERE id='$id'");

        //Mensaje e redirección
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

        //Si ha pulsado el botón de guardar
        if (isset($_POST["guardar"])){

            //Recupero los datos del formulario
            $usuario = filter_input(INPUT_POST, "usuario", FILTER_SANITIZE_STRING);
            $clave = filter_input(INPUT_POST, "clave", FILTER_SANITIZE_STRING);
            $usuarios = (filter_input(INPUT_POST, 'usuarios', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;
            $noticias = (filter_input(INPUT_POST, 'noticias', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;
            $cambiar_clave = (filter_input(INPUT_POST, 'cambiar_clave', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;

            //Encripto la clave
            $clave_encriptada = ($clave) ? password_hash($clave,  PASSWORD_BCRYPT, ['cost'=>12]) : "";

            if ($id == "nuevo"){

                //Creo un nuevo usuario
                $this->db->exec("INSERT INTO usuarios (usuario, clave, noticias, usuarios) VALUES ('$usuario','$clave_encriptada',$noticias,$usuarios)");

                //Mensaje e redirección
                $this->view->redireccionConMensaje("admin/usuarios","green","El usuario <strong>$usuario</strong> se creado correctamente.");
            }
            else{

                //Actualizo el usuario
                ($cambiar_clave) ?
                    $this->db->exec("UPDATE usuarios SET usuario='$usuario',clave='$clave_encriptada',noticias=$noticias,usuarios=$usuarios WHERE id='$id'") :
                    $this->db->exec("UPDATE usuarios SET usuario='$usuario',noticias=$noticias,usuarios=$usuarios WHERE id='$id'");

                //Mensaje e redirección
                $this->view->redireccionConMensaje("admin/usuarios","green","El usuario <strong>$usuario</strong> se actualizado correctamente.");
            }
        }

        //Si no, obtengo usuario e muestro la ventana de edición
        else{

            //Obtengo el usuario
            $rowset = $this->db->query("SELECT * FROM usuarios WHERE id='$id' LIMIT 1");
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $usuario = new Usuario($row);

            //Llamo a la ventana de edición
            $this->view->vista("admin","usuarios/editar", $usuario);
        }

    }


}```
view rawcreacion-de-un-cms-019.php hosted with ❤ by GitHub


En el seguinte apartado modificaremos la clase ViewHelper() para que incluya los métodos permisos() e redireccionConMensaje() utilizados en UsuarioController e crearemos los arquivo s de vistas del back-end correspondientes a los usuarios.


## 8 back end
Introducción

En este apartado crearemos todas las vistas relativas al área de usuarios, incluyendo el acceso, así como las del layout del panel de administración.

Del mismo modo que hicimos en el front-end, incluiremos Materialize para generar la interfaz de usuario, ajustando los estilos necesarios a nuestro caso particular, así como jQuery para crear interactividad.

Además, modificaremos la clase ViewHelper para que incluya los métodos permisos() e redireccionConMensaje(), necesarios en UsuarioController.


Header

El arquivo  view/admin/partials/header.php incluirá lo seguinte:


Etiqueta html (apertura).

Etiqueta head con todas las etiquetas e llamadas a estilos necesarias.

Etiqueta body (apertura).

Etiqueta nav para el menú de navegación e el logo

El apartado de mensajes informativos sobre las acciones realizadas

Etiqueta main(apertura).

Etiqueta header con título e subtítulo.

Etiqueta section(apertura).



```<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Panel de administración</title>

        <!--CSS-->
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
        <link rel="stylesheet" type="text/css" href="<?php echo $_SESSION['public'] ?>css/admin.css">

    </head>

    <body>
        <nav>
            <div class="nav-wrapper">
                <!--Logo-->
                <a href="<?php echo $_SESSION['home'] ?>admin" class="brand-logo" title="Inicio">
                    <img src="<?php echo $_SESSION['public'] ?>img/logo.svg" alt="Logo Harry Potter">
                </a>

                <?php if (isset($_SESSION['usuario'])){ ?>

                    <!--Botón menú móviles-->
                    <a href="#" data-target="mobile-demo" class="sidenav-trigger"><i class="material-icons">menu</i></a>

                    <!--Menú de navegación-->
                    <ul id="nav-mobile" class="right hide-on-med-and-down">
                        <li>
                            <a href="<?php echo $_SESSION['home'] ?>admin" title="Inicio">Inicio</a>
                        </li>
                        <?php if ($_SESSION['noticias'] == 1){ ?>
                            <li>
                                <a href="<?php echo $_SESSION['home'] ?>admin/noticias" title="Noticias">Noticias</a>
                            </li>
                        <?php } ?>
                        <?php if ($_SESSION['usuarios'] == 1){ ?>
                            <li>
                                <a href="<?php echo $_SESSION['home'] ?>admin/usuarios" title="Usuarios">Usuarios</a>
                            </li>
                        <?php } ?>
                        <li>
                            <a href="<?php echo $_SESSION['home'] ?>admin/salir" title="Salir">Salir</a>
                        </li>
                    </ul>

                <?php } ?>

            </div>
        </nav>

        <?php if (isset($_SESSION['usuario'])){ ?>

            <!--Menú de navegación móvil-->
            <ul class="sidenav" id="mobile-demo">
                <li>
                    <a href="<?php echo $_SESSION['home'] ?>admin" title="Inicio">Inicio</a>
                </li>
                <?php if ($_SESSION['noticias'] == 1){ ?>
                    <li>
                        <a href="<?php echo $_SESSION['home'] ?>admin/noticias" title="Noticias">Noticias</a>
                    </li>
                <?php } ?>
                <?php if ($_SESSION['usuarios'] == 1){ ?>
                    <li>
                        <a href="<?php echo $_SESSION['home'] ?>admin/usuarios" title="Usuarios">Usuarios</a>
                    </li>
                <?php } ?>
                <li>
                    <a href="<?php echo $_SESSION['home'] ?>admin/salir" title="Salir">Salir</a>
                </li>
            </ul>

        <?php } ?>

        <!-- Si existen mensajes  -->
        <?php if (isset($_SESSION["mensaje"])) { ?>

            <!-- Los muestro ocultos -->
            <input type="hidden" name="tipo-mensaje" value="<?php echo $_SESSION["mensaje"]['tipo'] ?>">
            <input type="hidden" name="texto-mensaje" value="<?php echo $_SESSION["mensaje"]['texto'] ?>">

            <!-- Borro mensajes -->
            <?php unset($_SESSION["mensaje"]) ?>

        <?php } ?>

        <main>

            <header>
                <h1>Panel de administración</h1>

                <?php if (isset($_SESSION['usuario'])){ ?>

                    <h2>
                        Usuario: <strong><?php echo $_SESSION['usuario'] ?></strong>
                    </h2>

                <?php } else { ?>

                    <h2>Bienvenido, introduce usuario e contraseña.</h2>

                <?php } ?>
            </header>

            <section class="container-fluid">```
view rawcreacion-de-un-cms-020.php hosted with ❤ by GitHub



Footer

El arquivo  view/admin/partials/footer.php incluirá lo seguinte:


Etiqueta section (cierre).

Etiqueta main(cierre).

Etiqueta body (cierre).

Etiqueta footer con información institucional e copyright.

Llamadas a scripts necesarias.

Etiqueta html (cierre).



           ``` </section>
        </main>
        <footer class="center-align">
            © <?php echo date("Y") ?> Panel de Administración.
            <a href="https://jairogarciarincon.com" target="_blank" title="Jairo García Rincón">
                Jairo García Rincón
            </a>
        </footer>


    </body>

    <!--JS-->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"
            integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="<?php echo $_SESSION['public'] ?>js/admin.js"></script>

</html>```
view rawcreacion-de-un-cms-021.php hosted with ❤ by GitHub



Hoja de estilos

Archivo public/css/admin.css:

```/* LAYOUT *************************************************************************************************************/

html{
    color: #333;
}

body{
    display: flex;
    min-height: 100vh;
    flex-direction: column;
}

nav{
    background-color: #333;
    box-shadow: none;
    -webkit-box-shadow: none;
    padding: 0 2rem;
}

nav img{
    width: 8rem;
    padding-top: 0.8rem;
}

main {
    flex: 1 0 auto;
    width: 100%;
    max-width: 1920px;
    padding: 0 2rem;
    margin: 0 auto;
}

header h1{
    font-size: 3rem;
    margin: 1rem 0 0 0;
}

header h2{
    font-size: 2rem;
    margin: 1rem 0;
}

footer{
    background-color: #333;
    color: white;
    padding: 1rem 0;
}

footer a{
    color: white;
}

footer a:hover{
    text-decoration: underline;
}

/* SECTION ************************************************************************************************************/

section h3{
    font-size: 1.5rem;
    border-bottom: 1px solid #333;
    padding-bottom: 0.3rem;
    font-weight:bold;
}

section h3 a{
    color: #333;
}

section h3 span{
    font-weight:normal;
    color: #444;
}

.btn{
    background-color: #777;
}

.btn:hover{
    background-color: #333;
}

article h4{
    font-size: 1.5rem;
}

article .card-info{
    position: relative;
    bottom: 3rem;
    left: 1.5rem;
}

.card.horizontal.small .card-image{
    max-height: 70%;
}

.card.horizontal.small .card-image img{
    margin-top: 20%;
}

.card.horizontal.noticia{
    border: none;
    box-shadow: none;
    -webkit-box-shadow: none;
}

.card.horizontal.admin h4{
    margin: 0 0 0.5rem 0.5rem;
}

.card.horizontal.admin strong{
    margin-left: 0.5rem;
}

.toast{
    background-color: transparent;
    border: none;
    box-shadow: none;
    -webkit-box-shadow: none;
    color: #333;
    cursor: pointer;
}

.toast strong{
    padding: 0 0.2rem;
}

form p{
    padding-left: 0.8rem;
}```
view rawcreacion-de-un-cms-022.css hosted with ❤ by GitHub



Javascript

Archivo public/js/admin.js:

```$(document).ready(function(){

    //Sidenav en móviles
    $('.sidenav').sidenav();

    //Mensajes
    var input_tipo = $("input[name=tipo-mensaje]");
    var input_texto = $("input[name=texto-mensaje]");
    if (input_tipo.length && input_texto.length){
        //var contenido = $('<span class="'+ input_tipo.val() +'">'+ input_texto.val() +'</span>');
        M.toast({html: input_texto.val(), classes: input_tipo.val() + " lighten-5"});
    }

    //Ocultar toast
    $(".toast").click(function () {
        $(this).hide();
    });

    //Cambiar clave
    $("input[type=checkbox][name=cambiar_clave]").click(function () {
        $("#password").toggleClass( "hide" );
    });

});```
view rawcreacion-de-un-cms-023.js hosted with ❤ by GitHub



Vista de acceso




Archivo view/admin/usuarios/entrar.php:

```<h3>Acceso</h3>
<div class="row">
    <form class="col m12 l6" method="POST">
        <div class="row">
            <div class="input-field col s12">
                <input id="usuario" type="text" name="usuario" value="">
                <label for="usuario">Usuario</label>
            </div>
            <div class="input-field col s12">
                <input id="clave" type="password" name="clave" value="">
                <label for="clave">Contraseña</label>
            </div>
            <div class="input-field col s12">
                <button class="btn waves-effect waves-light" type="submit" name="acceder">Acceder
                    <i class="material-icons right">person</i>
                </button>
            </div>
        </div>
    </form>
</div>```
view rawcreacion-de-un-cms-024.php hosted with ❤ by GitHub



Vista de inicio




El arquivo  view/admin/index.php se crea pero se deja vacío, si bien podría incluir iconos a las acciones, últimas modificaciones o acciones más utilizadas.


Vista de listado de usuarios




Archivo view/admin/usuarios/index.php:

```<h3>
    <a href="<?php echo $_SESSION['home'] ?>admin" title="Inicio">Inicio</a> <span>| Usuarios</span>
</h3>
<div class="row">
    <!--Nuevo-->
    <article class="col s12 l6">
        <div class="card horizontal admin">
            <div class="card-stacked">
                <div class="card-content">
                    <i class="grey-text material-icons medium">person</i>
                    <h4 class="grey-text">
                        nuevo usuario
                    </h4><br><br>
                </div>
                <div class="card-action">
                    <a href="<?php echo $_SESSION['home']."admin/usuarios/crear" ?>" title="Añadir nuevo usuario">
                        <i class="material-icons">add_circle</i>
                    </a>
                </div>
            </div>
        </div>
    </article>
    <?php foreach ($datos as $row){ ?>
        <article class="col s12 l6">
            <div class="card horizontal  sticky-action admin">
                <div class="card-stacked">
                    <div class="card-content">
                        <i class="material-icons medium">person</i>
                        <h4>
                            <?php echo $row->usuario ?>
                        </h4>
                        <strong>Usuarios: </strong><?php echo ($row->usuarios) ? "Sí" : "No" ?><br>
                        <strong>Noticias: </strong><?php echo ($row->noticias) ? "Sí" : "No" ?>
                    </div>
                    <div class="card-action">
                        <a href="<?php echo $_SESSION['home']."admin/usuarios/editar/".$row->id ?>" title="Editar">
                            <i class="material-icons">edit</i>
                        </a>
                        <?php $title = ($row->activo == 1) ? "Desactivar" : "Activar" ?>
                        <?php $color = ($row->activo == 1) ? "green-text" : "red-text" ?>
                        <?php $icono = ($row->activo == 1) ? "mood" : "mood_bad" ?>
                        <a href="<?php echo $_SESSION['home']."admin/usuarios/activar/".$row->id ?>" title="<?php echo $title ?>">
                            <i class="<?php echo $color ?> material-icons"><?php echo $icono ?></i>
                        </a>
                        <a href="#" class="activator" title="Borrar">
                            <i class="material-icons">delete</i>
                        </a>
                    </div>
                </div>
                <!--Confirmación de borrar-->
                <div class="card-reveal">
                    <span class="card-title grey-text text-darken-4">Borrar usuario<i class="material-icons right">close</i></span>
                    <p>
                        ¿Está seguro de que quiere borrar al usuario<strong><?php echo $row->usuario ?></strong>?<br>
                        Esta acción no se puede deshacer.
                    </p>
                    <a href="<?php echo $_SESSION['home']."admin/usuarios/borrar/".$row->id ?>" title="Borrar">
                        <button class="btn waves-effect waves-light" type="button">Borrar
                            <i class="material-icons right">delete</i>
                        </button>
                    </a>
                </div>
            </div>
        </article>
    <?php } ?>
</div>```
view rawcreacion-de-un-cms-025.php hosted with ❤ by GitHub



Vista de editar o crear usuario




Archivo view/admin/usuarios/editar.php:

```<h3>
    <a href="<?php echo $_SESSION['home'] ?>admin" title="Inicio">Inicio</a> <span>| </span>
    <a href="<?php echo $_SESSION['home'] ?>admin/usuarios" title="Usuarios">Usuarios</a> <span>| </span>
    <?php if ($datos->id){ ?>
        <span>Editar <?php echo $datos->usuario ?></span>
    <?php } else { ?>
        <span>Nuevo usuario</span>
    <?php } ?>
</h3>
<div class="row">
    <?php $id = ($datos->id) ? $datos->id : "nuevo" ?>
    <form class="col m12 l6" method="POST" action="<?php echo $_SESSION['home'] ?>admin/usuarios/editar/<?php echo $id ?>">
        <div class="row">
            <div class="input-field col s12">
                <input id="usuario" type="text" name="usuario" value="<?php echo $datos->usuario ?>">
                <label for="usuario">Usuario</label>
            </div>
            <?php $clase = ($datos->id) ? "hide" : "" ?>
            <div class="input-field col s12 <?php echo $clase ?>" id="password">
                <input id="clave" type="password" name="clave" value="">
                <label for="clave">Contraseña</label>
            </div>
            <?php $clase = ($datos->id) ? "" : "hide" ?>
            <p class="<?php echo $clase ?>">
                <label for="cambiar_clave">
                    <input id="cambiar_clave" name="cambiar_clave" type="checkbox">
                    <span>Pulsa para cambiar la clave</span>
                </label>
            </p>
        </div>
        <div class="row">
            <p>Permisos</p>
            <p>
                <label for="noticias">
                    <input id="noticias" name="noticias" type="checkbox" <?php echo ($datos->noticias == 1) ? "checked" : "" ?>>
                    <span>Noticias</span>
                </label>
            </p>
            <p>
                <label for="usuarios">
                    <input id="usuarios" name="usuarios" type="checkbox" <?php echo ($datos->usuarios == 1) ? "checked" : "" ?>>
                    <span>Usuarios</span>
                </label>
            </p>
            <?php $clase = ($datos->id) ? "" : "hide" ?>
            <p class="<?php echo $clase ?>">
                Último acceso: <strong><?php echo date("d/m/Y H:i", strtotime($datos->data_acceso)) ?></strong>
            </p>
            <div class="input-field col s12">
                <a href="<?php echo $_SESSION['home'] ?>admin/usuarios" title="Volver">
                    <button class="btn waves-effect waves-light" type="button">Volver
                        <i class="material-icons right">replay</i>
                    </button>
                </a>
                <button class="btn waves-effect waves-light" type="submit" name="guardar">Guardar
                    <i class="material-icons right">save</i>
                </button>
            </div>
        </div>
    </form>
</div>```
view rawcreacion-de-un-cms-026.php hosted with ❤ by GitHub



ViewHelper

El arquivo  de vistas se ha modificado para incluir los métodos permisos() e redireccionConMensaje():

```<?php
namespace App\Helper;

class ViewHelper {

    function vista($carpeta,$arquivo ,$datos=null){

        //Llamo a la cabecera
        require("../view/$carpeta/partials/header.php");

        //Llamo al contenido
        require ("../view/$carpeta/$arquivo .php");

        //Llamo al pie
        require("../view/$carpeta/partials/footer.php");

    }

    public function redireccionConMensaje($ruta, $tipo, $texto){

        $_SESSION['mensaje'] = array("tipo" => $tipo, "texto" => $texto);
        header("Location:".$_SESSION["home"].$ruta);

    }

    public function permisos($permiso=null){


        if (isset($_SESSION['usuario']) AND ($permiso == null OR $_SESSION[$permiso] == 1)){
            return true;
        }
        else{
            $this->redireccionConMensaje("admin","yellow", "No tienes permiso para realizar esta operación");
        }


    }
    
}```
view rawcreacion-de-un-cms-027.php hosted with ❤ by GitHub



Con esto estaría terminado el back-end de usuarios de nuestro CMS e todas las rutas asociadas e acciones deberían funcionar correctamente.

En los seguintes apartados crearemos el back-end de noticias para finalizar nuestro CMS.

## 9
Introducción

NoticiaControllerserá el controlador encargado de gestionar las noticias en el back-end de nuestro CMS.

Además de invocar los modelos e helpers correspondientes en el construct , utilizaremos dentro de él seis métodos correspondientes a las acciones, a saber:


index() para el listado de noticias ordenado por fecha más reciente.

crear() para acceder al formulario de de creación de una nueva noticia.

editar($id) para editar una determinada noticia.

activar($id) para activar o desactivar una determinada noticia.

home($id) para mostrar o no en la home una determinada noticia.

borrar($id) para borrar una determinada noticia.




NoticiaController

```<?php
namespace App\Controller;

use App\Helper\ViewHelper;
use App\Helper\DbHelper;
use App\Model\Noticia;


class NoticiaController
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

    //Listado de noticias
    public function index(){

        //Permisos
        $this->view->permisos("noticias");

        //Recojo las noticias de la base de datos
        $rowset = $this->db->query("SELECT * FROM noticias ORDER BY fecha DESC");

        //Asigno resultados a un array de instancias del modelo
        $noticias = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($noticias,new Noticia($row));
        }

        $this->view->vista("admin","noticias/index", $noticias);

    }

    //Para activar o desactivar
    public function activar($id){

        //Permisos
        $this->view->permisos("noticias");

        //Obtengo la noticia
        $rowset = $this->db->query("SELECT * FROM noticias WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $noticia = new Noticia($row);

        if ($noticia->activo == 1){

            //Desactivo la noticia
            $consulta = $this->db->exec("UPDATE noticias SET activo=0 WHERE id='$id'");

            //Mensaje e redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/noticias","green","La noticia <strong>$noticia->titulo</strong> se ha desactivado correctamente.") :
                $this->view->redireccionConMensaje("admin/noticias","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Activo la noticia
            $consulta = $this->db->exec("UPDATE noticias SET activo=1 WHERE id='$id'");

            //Mensaje e redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/noticias","green","La noticia <strong>$noticia->titulo</strong> se ha activado correctamente.") :
                $this->view->redireccionConMensaje("admin/noticias","red","Hubo un error al guardar en la base de datos.");
        }

    }

    //Para mostrar o no en la home
    public function home($id){

        //Permisos
        $this->view->permisos("noticias");

        //Obtengo la noticia
        $rowset = $this->db->query("SELECT * FROM noticias WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $noticia = new Noticia($row);

        if ($noticia->home == 1){

            //Quito la noticia de la home
            $consulta = $this->db->exec("UPDATE noticias SET home=0 WHERE id='$id'");

            //Mensaje e redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/noticias","green","La noticia <strong>$noticia->titulo</strong> ya no se muestra en la home.") :
                $this->view->redireccionConMensaje("admin/noticias","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Muestro la noticia en la home
            $consulta = $this->db->exec("UPDATE noticias SET home=1 WHERE id='$id'");

            //Mensaje e redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/noticias","green","La noticia <strong>$noticia->titulo</strong> ahora se muestra en la home.") :
                $this->view->redireccionConMensaje("admin/noticias","red","Hubo un error al guardar en la base de datos.");
        }

    }

    public function borrar($id){

        //Permisos
        $this->view->permisos("noticias");

        //Obtengo la noticia
        $rowset = $this->db->query("SELECT * FROM noticias WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $noticia = new Noticia($row);

        //Borro la noticia
        $consulta = $this->db->exec("DELETE FROM noticias WHERE id='$id'");

        //Borro la imagen asociada
        $arquivo  = $_SESSION['public']."img/".$noticia->imagen;
        $texto_imagen = "";
        if (is_file($arquivo )){
            unlink($arquivo );
            $texto_imagen = " e se ha borrado la imagen asociada";
        }

        //Mensaje e redirección
        ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
            $this->view->redireccionConMensaje("admin/noticias","green","La noticia se ha borrado correctamente$texto_imagen.") :
            $this->view->redireccionConMensaje("admin/noticias","red","Hubo un error al guardar en la base de datos.");

    }

    public function crear(){

        //Permisos
        $this->view->permisos("noticias");

        //Creo un nuevo usuario vacío
        $noticia = new Noticia();

        //Llamo a la ventana de edición
        $this->view->vista("admin","noticias/editar", $noticia);

    }

    public function editar($id){

        //Permisos
        $this->view->permisos("noticias");

        //Si ha pulsado el botón de guardar
        if (isset($_POST["guardar"])){

            //Recupero los datos del formulario
            $titulo = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_STRING);
            $entradilla = filter_input(INPUT_POST, "entradilla", FILTER_SANITIZE_STRING);
            $autor = filter_input(INPUT_POST, "autor", FILTER_SANITIZE_STRING);
            $fecha = filter_input(INPUT_POST, "fecha", FILTER_SANITIZE_STRING);
            $texto = filter_input(INPUT_POST, "texto", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            //Formato de fecha para SQL
            $fecha = \DateTime::createFromFormat("d-m-Y", $fecha)->format("Y-m-d H:i:s");

            //Genero slug (url amigable)
            $slug = $this->view->getSlug($titulo);

            //Imagen
            $imagen_recibida = $_FILES['imagen'];
            $imagen = ($_FILES['imagen']['name']) ? $_FILES['imagen']['name'] : "";
            $imagen_subida = ($_FILES['imagen']['name']) ? '/var/www/html'.$_SESSION['public']."img/".$_FILES['imagen']['name'] : "";
            $texto_img = ""; //Para el mensaje

            if ($id == "nuevo"){

                //Creo una nueva noticia
                $consulta = $this->db->exec("INSERT INTO noticias 
                    (titulo, entradilla, autor, fecha, texto, slug, imagen) VALUES 
                    ('$titulo','$entradilla','$autor','$fecha','$texto','$slug','$imagen')");

                //Subo la imagen
                if ($imagen){
                    if (is_uploaded_file($imagen_recibida['tmp_name']) && move_uploaded_file($imagen_recibida['tmp_name'], $imagen_subida)){
                        $texto_img = " La imagen se ha subido correctamente.";
                    }
                    else{
                        $texto_img = " Hubo un problema al subir la imagen.";
                    }
                }

                //Mensaje e redirección
                ($consulta > 0) ?
                    $this->view->redireccionConMensaje("admin/noticias","green","La noticia <strong>$titulo</strong> se creado correctamente.".$texto_img) :
                    $this->view->redireccionConMensaje("admin/noticias","red","Hubo un error al guardar en la base de datos.");
            }
            else{

                //Actualizo la noticia
                $this->db->exec("UPDATE noticias SET 
                    titulo='$titulo',entradilla='$entradilla',autor='$autor',
                    fecha='$fecha',texto='$texto',slug='$slug' WHERE id='$id'");

                //Subo e actualizo la imagen
                if ($imagen){
                    if (is_uploaded_file($imagen_recibida['tmp_name']) && move_uploaded_file($imagen_recibida['tmp_name'], $imagen_subida)){
                        $texto_img = " La imagen se ha subido correctamente.";
                        $this->db->exec("UPDATE noticias SET imagen='$imagen' WHERE id='$id'");
                    }
                    else{
                        $texto_img = " Hubo un problema al subir la imagen.";
                    }
                }

                //Mensaje e redirección
                $this->view->redireccionConMensaje("admin/noticias","green","La noticia <strong>$titulo</strong> se guardado correctamente.".$texto_img);

            }
        }

        //Si no, obtengo noticia e muestro la ventana de edición
        else{

            //Obtengo la noticia
            $rowset = $this->db->query("SELECT * FROM noticias WHERE id='$id' LIMIT 1");
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $noticia = new Noticia($row);

            //Llamo a la ventana de edición
            $this->view->vista("admin","noticias/editar", $noticia);
        }

    }

}```
view rawcreacion-de-un-cms-028.php hosted with ❤ by GitHub


IMPORTANTE: Recuerda que es recomendable que la carpeta cms/public/img e todos los arquivo s que contenga pertenezcan al usuario www-data del grupo www-data para evitar problemas de permisos cuando queramos subir imágenes desde el panel de administración. Esto lo puedes cambiar desde un terminal, accediendo al directorio padre de la carpeta de cms e ejecutando el comando:

sudo chown -R www-data:www-data cms/public/img
view rawcreacion-de-un-cms-029.c hosted with ❤ by GitHub



En el seguinte apartado modificaremos la clase ViewHelper() para que incluya el método getSlug() utilizados en NoticiaController e crearemos los arquivo s de vistas del back-end correspondientes a los noticias.

## 10 vista back-end
Introducción

En este apartado crearemos todas las vistas relativas al área de noticias.

Además, modificaremos la clase ViewHelper para que incluya el método getSlug(), necesario en NoticiaController.

También modificaremos la hoja de estilos e el arquivo  de script dado que en las seguintes vistas aparecen nuevos elementos que requieren de estilo e funcionalidad.


Hoja de estilos

Archivo public/css/admin.css:

```/* LAYOUT *************************************************************************************************************/

html{
    color: #333;
}

body{
    display: flex;
    min-height: 100vh;
    flex-direction: column;
}

nav{
    background-color: #333;
    box-shadow: none;
    -webkit-box-shadow: none;
    padding: 0 2rem;
}

nav img{
    width: 8rem;
    padding-top: 0.8rem;
}

main {
    flex: 1 0 auto;
    width: 100%;
    max-width: 1920px;
    padding: 0 2rem;
    margin: 0 auto;
}

header h1{
    font-size: 3rem;
    margin: 1rem 0 0 0;
}

header h2{
    font-size: 2rem;
    margin: 1rem 0;
}

footer{
    background-color: #333;
    color: white;
    padding: 1rem 0;
}

footer a{
    color: white;
}

footer a:hover{
    text-decoration: underline;
}

/* SECTION ************************************************************************************************************/

section h3{
    font-size: 1.5rem;
    border-bottom: 1px solid #333;
    padding-bottom: 0.3rem;
    font-weight:bold;
}

section h3 a{
    color: #333;
}

section h3 span{
    font-weight:normal;
    color: #444;
}

.btn{
    background-color: #777;
}

.btn:hover{
    background-color: #333;
}

article h4{
    font-size: 1.5rem;
}

article .card-info{
    position: relative;
    bottom: 3rem;
    left: 1.5rem;
}

.card.horizontal.small .card-image{
    max-height: 70%;
}

.card.horizontal.small .card-image img{
    margin-top: 20%;
}

.card.horizontal.noticia{
    border: none;
    box-shadow: none;
    -webkit-box-shadow: none;
}

.card.horizontal.admin h4{
    margin: 0 0 0.5rem 0.5rem;
}

.card.horizontal.admin strong{
    margin-left: 0.5rem;
}

.card.horizontal.admin img{
    height: 3rem;
    margin: 1.5rem 0 0 2rem;
}

.toast{
    background-color: transparent;
    border: none;
    box-shadow: none;
    -webkit-box-shadow: none;
    color: #333;
    cursor: pointer;
}

.toast strong{
    padding: 0 0.2rem;
}

form p{
    padding-left: 0.8rem;
}```
view rawcreacion-de-un-cms-030.css hosted with ❤ by GitHub



Javascript

Archivo public/js/admin.js:

```$(document).ready(function(){

    //Sidenav en móviles
    $('.sidenav').sidenav();

    //Mensajes
    var input_tipo = $("input[name=tipo-mensaje]");
    var input_texto = $("input[name=texto-mensaje]");
    if (input_tipo.length && input_texto.length){
        //var contenido = $('<span class="'+ input_tipo.val() +'">'+ input_texto.val() +'</span>');
        M.toast({html: input_texto.val(), classes: input_tipo.val() + " lighten-5"});
    }

    //Ocultar toast
    $(".toast").click(function () {
        $(this).hide();
    });

    //Cambiar clave
    $("input[type=checkbox][name=cambiar_clave]").click(function () {
        $("#password").toggleClass( "hide" );
    });

    //Fecha
    $( ".datepicker" ).datepicker({
        firstDay: true,
        format: 'dd-mm-yyyy',
        i18n: {
            months: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
            monthsShort: ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Set", "Oct", "Nov", "Dic"],
            weekdays: ["Domingo","Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"],
            weekdaysShort: ["Dom","Lun", "Mar", "Mie", "Jue", "Vie", "Sab"],
            weekdaysAbbrev: ["D","L", "M", "M", "J", "V", "S"]
        }
    });

});```
view rawcreacion-de-un-cms-031.js hosted with ❤ by GitHub



Vista de listado de noticias




Archivo view/admin/noticias/index.php:

```<h3>
    <a href="<?php echo $_SESSION['home'] ?>admin" title="Inicio">Inicio</a> <span>| Noticias</span>
</h3>
<div class="row">
    <!--Nuevo-->
    <article class="col s12 l6">
        <div class="card horizontal admin">
            <div class="card-stacked">
                <div class="card-content">
                    <i class="grey-text material-icons medium">image</i>
                    <h4 class="grey-text">
                        nueva noticia
                    </h4><br><br>
                </div>
                <div class="card-action">
                    <a href="<?php echo $_SESSION['home']."admin/noticias/crear" ?>" title="Añadir nueva noticia">
                        <i class="material-icons">add_circle</i>
                    </a>
                </div>
            </div>
        </div>
    </article>
    <?php foreach ($datos as $row){ ?>
        <article class="col s12 l6">
            <div class="card horizontal  sticky-action admin">
                <div class="card-stacked">
                    <?php if ($row->imagen){ ?>
                        <div class="card-image">
                                <img src="<?php echo $_SESSION['public']."img/".$row->imagen ?>" alt="<?php echo $row->titulo ?>">
                        </div>
                    <?php } ?>
                    <div class="card-content">
                        <?php if (!$row->imagen){ ?>
                            <i class="grey-text material-icons medium">image</i>
                        <?php } ?>
                        <h4>
                            <?php echo $row->titulo ?>
                        </h4>
                        <strong>URL amigable:</strong> <?php echo $row->slug ?><br>
                        <strong>Fecha:</strong> <?php echo date("d/m/Y", strtotime($row->fecha)) ?>
                    </div>
                    <div class="card-action">
                        <a href="<?php echo $_SESSION['home']."admin/noticias/editar/".$row->id ?>" title="Editar">
                            <i class="material-icons">edit</i>
                        </a>
                        <?php $title = ($row->activo == 1) ? "Desactivar" : "Activar" ?>
                        <?php $color = ($row->activo == 1) ? "green-text" : "red-text" ?>
                        <?php $icono = ($row->activo == 1) ? "mood" : "mood_bad" ?>
                        <a href="<?php echo $_SESSION['home']."admin/noticias/activar/".$row->id ?>" title="<?php echo $title ?>">
                            <i class="<?php echo $color ?> material-icons"><?php echo $icono ?></i>
                        </a>
                        <?php $title = ($row->home == 1) ? "Quitar de la home" : "Mostrar en la home" ?>
                        <?php $color = ($row->home == 1) ? "green-text" : "red-text" ?>
                        <a href="<?php echo $_SESSION['home']."admin/noticias/home/".$row->id ?>" title="<?php echo $title ?>">
                            <i class="<?php echo $color ?> material-icons">home</i>
                        </a>
                        <a href="#" class="activator" title="Borrar">
                            <i class="material-icons">delete</i>
                        </a>
                    </div>
                </div>
                <!--Confirmación de borrar-->
                <div class="card-reveal">
                    <span class="card-title grey-text text-darken-4">Borrar noticia<i class="material-icons right">close</i></span>
                    <p>
                        ¿Está seguro de que quiere borrar la noticia<strong><?php echo $row->titulo ?></strong>?<br>
                        Esta acción no se puede deshacer.
                    </p>
                    <a href="<?php echo $_SESSION['home']."admin/noticias/borrar/".$row->id ?>" title="Borrar">
                        <button class="btn waves-effect waves-light" type="button">Borrar
                            <i class="material-icons right">delete</i>
                        </button>
                    </a>
                </div>
            </div>
        </article>
    <?php } ?>
</div>```
view rawcreacion-de-un-cms-032.php hosted with ❤ by GitHub



Vista de editar o crear noticia




Archivo view/admin/noticias/editar.php:

```<h3>
    <a href="<?php echo $_SESSION['home'] ?>admin" title="Inicio">Inicio</a> <span>| </span>
    <a href="<?php echo $_SESSION['home'] ?>admin/noticias" title="Noticias">Noticias</a> <span>| </span>
    <?php if ($datos->id){ ?>
        <span>Editar <?php echo $datos->titulo ?></span>
    <?php } else { ?>
        <span>Nueva noticia</span>
    <?php } ?>
</h3>
<div class="row">
    <?php $id = ($datos->id) ? $datos->id : "nuevo" ?>
    <form class="col s12" method="POST" enctype="multipart/form-data" action="<?php echo $_SESSION['home'] ?>admin/noticias/editar/<?php echo $id ?>">
        <div class="col m12 l6">
            <div class="row">
                <div class="input-field col s12">
                    <input id="titulo" type="text" name="titulo" value="<?php echo $datos->titulo ?>">
                    <label for="titulo">Título</label>
                </div>
                <div class="input-field col s12">
                    <input id="autor" type="text" name="autor" value="<?php echo $datos->autor ?>">
                    <label for="autor">Autor</label>
                </div>
                <div class="input-field col s12">
                    <?php $fecha = ($datos->fecha) ? date("d-m-Y", strtotime($datos->fecha)) : date("d-m-Y") ?>
                    <input id="fecha" type="text" name="fecha" class="datepicker" value="<?php echo $fecha ?>">
                    <label for="fecha">Fecha</label>
                </div>
            </div>
        </div>
        <div class="col m12 l6 center-align">
            <div class="file-field input-field">
                <div class="btn">
                    <span>Imagen</span>
                    <input type="file" name="imagen">
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text">
                </div>
            </div>
            <?php if ($datos->imagen){ ?>
                <img src="<?php echo $_SESSION['public']."img/".$datos->imagen ?>" alt="<?php echo $datos->titulo ?>">
            <?php } ?>
        </div>
        <div class="col s12">
            <div class="row">
                <div class="input-field col s12">
                    <textarea id="entradilla" class="materialize-textarea" name="entradilla"><?php echo $datos->entradilla ?></textarea>
                    <label for="entradilla">Entradilla</label>
                </div>
                <div class="input-field col s12">
                    <textarea id="texto" class="materialize-textarea" name="texto"><?php echo $datos->texto ?></textarea>
                    <label for="texto">Texto</label>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="input-field col s12">
                <a href="<?php echo $_SESSION['home'] ?>admin/noticias" title="Volver">
                    <button class="btn waves-effect waves-light" type="button">Volver
                        <i class="material-icons right">replay</i>
                    </button>
                </a>
                <button class="btn waves-effect waves-light" type="submit" name="guardar">Guardar
                    <i class="material-icons right">save</i>
                </button>
            </div>
        </div>
    </form>
</div>```
view rawcreacion-de-un-cms-033.php hosted with ❤ by GitHub



ViewHelper

El arquivo  de vistas se ha modificado para incluir el método getSlug():

```<?php
namespace App\Helper;

class ViewHelper {

    function vista($carpeta,$arquivo ,$datos=null){

        //Llamo a la cabecera
        require("../view/$carpeta/partials/header.php");

        //Llamo al contenido
        require ("../view/$carpeta/$arquivo .php");

        //Llamo al pie
        require("../view/$carpeta/partials/footer.php");

    }

    public function redireccionConMensaje($ruta, $tipo, $texto){

        $_SESSION['mensaje'] = array("tipo" => $tipo, "texto" => $texto);
        header("Location:".$_SESSION["home"].$ruta);

    }

    public function permisos($permiso=null){

        if (isset($_SESSION['usuario']) AND ($permiso == null OR $_SESSION[$permiso] == 1)){
            return true;
        }
        else{
            $this->redireccionConMensaje("admin","yellow", "No tienes permiso para realizar esta operación");
        }

    }

    //Función para generar el slug a partir de un string
    public function getSlug($str){

        //Quito acentos e caracteres extraños
        $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë',
            'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø',
            'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å',
            'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò',
            'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā',
            'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č',
            'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę',
            'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ',
            'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ',
            'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ',
            'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ',
            'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ',
            'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ',
            'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů',
            'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż',
            'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ',
            'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ',
            'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
        $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E',
            'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O',
            'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a',
            'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o',
            'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A',
            'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C',
            'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E',
            'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H',
            'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I',
            'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L',
            'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n',
            'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r',
            'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't',
            'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u',
            'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z',
            'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I',
            'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U',
            'u', 'A', 'a', 'AE', 'ae', 'O', 'o');

        $sin_acentos = str_replace($a, $b, $str);

        //genero slug
        return mb_strtolower(preg_replace(array('/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'), array('', '-', ''), $sin_acentos),'UTF-8');
    }
    
}```
view rawcreacion-de-un-cms-034.php hosted with ❤ by GitHub



Con esto estaría terminado el CMS e todas las rutas asociadas e acciones deberían funcionar correctamente.

No te olvides de revisar las conclusiones e propuestas de mejora en el seguinte e último apartado.




## Apéndice I
**Paso 1:** Accede como `root` con SSH no teu server.
**Paso 2:** Identificate en MySQL como root:
```
mysql -u root
```

**Paso 3:** Crea un novo usuario de base de datos:
```
GRANT ALL PRIVILEGES ON *.* TO 'db_user'@'localhost' IDENTIFIED BY 'P@s$w0rd123!';```

NOTA: Be sure to modify db_user with the actual name, you would like to give the database user, as well as, P@s$w0rd123! with the password to be given to the user.
**Paso 4:** Sal de MySQL escribindo: \q.
**Paso 5:** Log in as the new database user you just created:
```
mysql -u db_user -p```

NOTA: Be sure to modify db_user with the actual database user name.
Then, type the new database user’s password and press Enter.

**Paso 6:** Create a new database:
```
CREATE DATABASE db_name;```

NOTA: Be sure to modify db_name with the actual name you would like to give the database.