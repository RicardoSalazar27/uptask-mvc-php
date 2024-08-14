<?php

namespace Controllers;

use Classes\Email;
use MVC\Router;
use Model\Usuario;

class LoginController{
    public static function login(Router $router){

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            //
            $usuario = new Usuario($_POST);

            $alertas = $usuario->validarLogin();

            if(empty($alertas)){
                // Verificar que el usuario existe
                $usuario = Usuario::where('email', $usuario->email);

                if(!$usuario || !$usuario->confirmado){
                    Usuario::setAlerta('error', 'El usuario no existe o no esta confirmado');
                } else{
                    // El usuario existe
                    if(password_verify($_POST['password'], $usuario->password)) {
                        //Iniciar Sesion
                        session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        // Redireccionar
                        header('Location: /dashboard');
                    
                    } else{
                        Usuario::setAlerta('error', 'Password Incorrecto');
                    }
                }
                //debuguear($usuario);
            }
        }

        $alertas = Usuario::getAlertas();

        // Render a la vista
        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesión',
            'alertas' => $alertas
        ]);
    }

    public static function logout(){
        session_start();
        $_SESSION = []; 
        header('Location: /');       
    }

    public static function crear(Router $router){
        
        // instanciar Usario
        $usuario = new Usuario();

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            //
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta(); //validamos que no haya errores en su registro
            
            //Revisar si el usuario ya esta registrado
            $existeUsuario = Usuario::where('email', $usuario->email);
            
           if(empty($alertas)){ 
                if($existeUsuario){
                    Usuario::setAlerta('error', 'El usuario ya esta registrado');
                    $alertas = Usuario::getAlertas();
                }
                else{
                    // Hashear el password
                    $usuario->hashPassword();

                    // Elinminar password2
                    unset($usuario->password2);

                    //Generar el token
                    $usuario->crearToken();

                    $resultado = $usuario->guardar();

                    // Enviar Email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarConfirmacion();

                    if($resultado){
                        header('Location: /mensaje');
                    }
                } 
           } 
        }

        // Render a la vista
        $router->render('auth/crear', [
            'titulo' => 'Crear Cuenta',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function olvide(Router $router){

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            //
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if(empty($alertas)){
                // Buscar el usuario
                $usuario = Usuario::where('email', $usuario->email);

                if($usuario && $usuario->confirmado === "1"){

                    // Elinminar password2
                    unset($usuario->password2);

                    // Generar un nuevo token
                    $usuario->crearToken();

                    // Actualizar el usuario
                    $usuario->guardar();

                    // Enviar Email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();

                    // Imprimir altera
                    Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu Email');
                    $alertas = Usuario::getAlertas();

                } else{
                    Usuario::setAlerta('error', 'El usuario no existe o no esta confirmado');
                    $alertas = Usuario::getAlertas();
                }
            }
        }

        // Render a la vista
        $router->render('auth/olvide', [
            'titulo' => 'Olvide Mi Passowrd',
            'alertas' => $alertas
        ]);
    }

    public static function restablecer(Router $router){

        $token = s($_GET['token']);
        $mostrar = TRUE;
        if(!$token){
            header('Location:/');
        }

        // Encontrar al usuario con el token
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)){
            Usuario::setAlerta('error', 'Token No Valido');
            $mostrar= FALSE;
        }
        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Añadir el nuevo password
            $usuario->sincronizar($_POST);

            // Validar el password
            $alertas = $usuario->validarPassword();

            if(empty($alertas)){
                // Hashear el password
                $usuario->hashPassword();

                //Eliminar el token 
                $usuario->token = '';
                unset($usuario->password2);

                // Guardar el nuevo password en la bd
                $resultado = $usuario->guardar();

                //Redireccionar
                if($resultado) {
                    header('Locartion: /');
                }
            }
        }
        
        $alertas = Usuario::getAlertas();
        

        // Render a la vista
        $router->render('auth/restablecer', [
            'titulo' => 'Restablecer Password',
            'alertas' => $alertas,
            'mostrar' => $mostrar
        ]);
    }

    public static function mensaje(Router $router){
        // Render a la vista
        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta Creada Exitosamente'
        ]);
    }

    public static function confirmar(Router $router){

        $token = s($_GET['token']);

        if(!$token){
            header('Location:/');
        }

        // Encontrar al usuario con el token

        $usuario = Usuario::where('token', $token);
        if(empty($usuario)){
            Usuario::setAlerta('error', 'Usuario No Valido');
        } else{
            // Confirmar cuenta
            $usuario->confirmado = 1;
            $usuario->token = '';
            unset($usuario->password2);
            
            // Guardar en la bd
            $usuario->guardar();

            Usuario::setAlerta('exito', 'Cuenta comprobada correctamente');
        }

        $alertas = Usuario::getAlertas();
        
        $router->render('auth/confirmar', [
            'titulo' => 'Cuenta Confirmada',
            'alertas' => $alertas
        ]);
    }
}