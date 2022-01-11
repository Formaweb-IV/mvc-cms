<!DOCTYPE html>
<html lang="gl">
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
                        <?php if ($_SESSION['novas'] == 1){ ?>
                            <li>
                                <a href="<?php echo $_SESSION['home'] ?>admin/novas" title="Novas">Novas</a>
                            </li>
                        <?php } ?>
                        <?php if ($_SESSION['usuarios'] == 1){ ?>
                            <li>
                                <a href="<?php echo $_SESSION['home'] ?>admin/usuarios" title="Usuarios">Usuarios</a>
                            </li>
                        <?php } ?>
                        <li>
                            <a href="<?php echo $_SESSION['home'] ?>admin/salir" title="Saír">Saír</a>
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
                <?php if ($_SESSION['novas'] == 1){ ?>
                    <li>
                        <a href="<?php echo $_SESSION['home'] ?>admin/novas" title="Novas">Novas</a>
                    </li>
                <?php } ?>
                <?php if ($_SESSION['usuarios'] == 1){ ?>
                    <li>
                        <a href="<?php echo $_SESSION['home'] ?>admin/usuarios" title="Usuarios">Usuarios</a>
                    </li>
                <?php } ?>
                <li>
                    <a href="<?php echo $_SESSION['home'] ?>admin/salir" title="Saír">Saír</a>
                </li>
            </ul>

        <?php } ?>

        <!-- Si existen mensaxes  -->
        <?php if (isset($_SESSION["mensaxe"])) { ?>

            <!-- Los muestro ocultos -->
            <input type="hidden" name="tipo-mensaxe" value="<?php echo $_SESSION["mensaxe"]['tipo'] ?>">
            <input type="hidden" name="texto-mensaxe" value="<?php echo $_SESSION["mensaxe"]['texto'] ?>">

            <!-- Borro mensaxes -->
            <?php unset($_SESSION["mensaxe"]) ?>

        <?php } ?>

        <main>

            <header>
                <h1>Panel de administración</h1>

                <?php if (isset($_SESSION['usuario'])){ ?>

                    <h2>
                        Usuario: <strong><?php echo $_SESSION['usuario'] ?></strong>
                    </h2>

                <?php } else { ?>

                    <h2>Benvido, introduce usuario e contrasinal.</h2>

                <?php } ?>
            </header>

            <section class="container-fluid">