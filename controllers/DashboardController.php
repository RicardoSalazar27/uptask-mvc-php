<?php

namespace Controllers;

use Model\Proyecto;
use Model\Usuario;
use MVC\Router;

class DashboardController{
    public static function index(Router $router){

        if(!isset($_SESSION)) {
            session_start();
        }

        isAuth();

        $id = $_SESSION['id'];

        $proyectos = Proyecto::belongsTo('propietarioId', $id);
     
        $router->render('dashboard/index', [
            'titulo' => 'Proyectos',
            'proyectos' => $proyectos
        ]);
    }

    public static function crear_proyecto(Router $router){

        if(!isset($_SESSION)) {
            session_start();
        }

        isAuth();

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $proyecto = new Proyecto($_POST);

            // Validacion
            $alertas = $proyecto->validarProyecto();

            if(empty($alertas)){
                // Generar URL única
                $proyecto->url = md5(uniqid());

                // Almacenar propietario del proyecto
                $proyecto->propietarioId = $_SESSION['id'];
                
                // Guardar el proyecto
                $proyecto->guardar();

                // Redireccionar
                header('Location:/proyecto?id=' . $proyecto->url);
            }
            //debuguear($proyecto);
        }
        
        $router->render('dashboard/crear-proyecto', [
            'titulo' => 'Crear Proyecto',
            'alertas' => $alertas
        ]);
    }

    public static function proyecto(Router $router){

        if(!isset($_SESSION)) {
            session_start();
        }

        isAuth();

        $token = $_GET['id'];
        if(!$token){ header('Location:/dashboard'); }

        // Revisar que la persona que visita el proyecto, es quien lo creo
        $proyecto = Proyecto::where('url', $token);
        //debuguear($proyecto);

        if($proyecto->propietarioId !== $_SESSION['id']){
            header('Location:/dashboard');
        }

        $router->render('dashboard/proyecto', [
            'titulo' => $proyecto->proyecto
        ]);
    }

    public static function perfil(Router $router){

        if(!isset($_SESSION)) {
            session_start();
        }

        isAuth();

        $alertas = [];

        $usuario = Usuario::find($_SESSION['id']);
        //debuguear($usuario);
        
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $usuario->sincronizar($_POST);

            $alertas = $usuario->validar_perfil();

            if(empty($alertas)) {

                $existeUsuario = Usuario::where('email', $usuario->email);

                if ($existeUsuario && $existeUsuario->id !== $usuario->id) {
                    // Mensaje de error
                    Usuario::setAlerta('error', 'El Email ya pertenece a otra cuenta');
                    $alertas = $usuario->getAlertas();
                } else{
                    //Guardar Usuario
                $usuario->guardar();

                Usuario::setAlerta('exito', 'Guardado Corectamente');
                $alertas = $usuario->getAlertas();

                //Asignar el nombre nuevo a la barra
                $_SESSION['nombre'] = $usuario->nombre;
                }
            }
        }

        $router->render('dashboard/perfil', [
            'titulo' => 'Perfil',
            'alertas' => $alertas,
            'usuario' => $usuario
        ]);
    }

    public static function cambiar_password(Router $router) {
        
        if(!isset($_SESSION)) {
            session_start();
        }

        isAuth();

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $usuario = Usuario::find($_SESSION['id']); //identificar alusuario que deseacambiar su password
            
            // Sincronizar con los datos del usuario (llenar con los valores enviados)
            $usuario->sincronizar($_POST);

            $alertas = $usuario->nuevo_password();

            if(empty($alertas)){
                $resultado = $usuario->comprobar_password();
                
                if($resultado){

                    $usuario->password = $usuario->password_nuevo;
                    // Eliminar propiedades no necesarias
                    unset($usuario->password_actual);
                    unset($usuario->password_nuevo);

                    // Hashear el nuevo password
                    $usuario->hashPassword();

                    // Actualizar
                    $resultado = $usuario->guardar();//123456789

                    if($resultado){
                        Usuario::setAlerta('exito', 'Password Guardado Correctamente');
                        $alertas = $usuario->getAlertas();
                    }

                } else {
                    Usuario::setAlerta('error', 'Password Incorrecto');
                    $alertas = $usuario->getAlertas();
                }
            }
            
            //ebuguear($usuario);
        }

        $router->render('dashboard/cambiar-password', [
            'titulo' => 'Cambiar Password',
            'alertas' => $alertas
        ]);
    }
}