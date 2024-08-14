<?php

namespace Controllers;

use Model\Proyecto;
use Model\Tarea;

class TareaController{
    public static function index(){
        if(!isset($_SESSION)) {
            session_start();
        }

        isAuth();

        $proyectoId = $_GET['id'];

        if(!$proyectoId){
            header('Location:/dashboard');
        }

        $proyecto = Proyecto::where('url', $proyectoId);

        if(!$proyecto || $proyecto->propietarioId !== $_SESSION['id']){
            header('Locarion: /404');
        }

        $tareas = Tarea::belongsTo('proyectoId', $proyecto->id);
        //debuguear($tareas);
        echo json_encode(['tareas' => $tareas]);
    }

    public static function crear(){

        if(!isset($_SESSION)) {
            session_start();
        }

        isAuth();

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            //$array = [
            //    'respuesta' => true,
            //    'nombre' => 'ricardo'
            //];

            //echo json_encode($array); //para enviar desde el servidor
            //echo json_encode($_POST); //para recibir de post

            $proyectoId = $_POST['proyectoId'];
            $proyecto = Proyecto::where('url', $proyectoId);
            if(!$proyecto || $proyecto->propietarioId !== $_SESSION['id']){
                $respuesta = [
                    'tipo' => 'error',
                    'mensaje' => 'No se pudo agregar la tarea correctamente'
                ];
                echo json_encode($respuesta);
                return;
            }
            // Todo bien, instanciar y eliminar la tarea
            $tarea = new Tarea($_POST);
            $tarea->proyectoId = $proyecto->id;
            $resultado = $tarea->guardar();
            $respuesta = [
                'tipo' => 'exito',
                'id' => $resultado['id'],
                'mensaje' => 'Tarea Creada Correctamente',
                'proyectoId' => $proyecto->id
            ];
            echo json_encode($respuesta);
        }
    }



    public static function actualizar(){

        if(!isset($_SESSION)) {
            session_start();
        }

        isAuth();

        if($_SERVER['REQUEST_METHOD'] === 'POST'){

            // Validar que el proyecto exista
            $proyecto = Proyecto::where('url', $_POST['proyectoId']);

            if(!$proyecto || $proyecto->propietarioId !== $_SESSION['id']){
                $respuesta = [
                    'tipo' => 'error',
                    'mensaje' => 'Hubo un error al actualizar la tarea'
                ];
                echo json_encode($respuesta);
                return;
            }

            $tarea = new Tarea($_POST);
            $tarea->proyectoId = $proyecto->id; //desde jd se enviar pero con la url por eso se modifica 

            $resultado = $tarea->guardar();
            if($resultado){
                $respuesta = [
                    'tipo' => 'exito',
                    'id' => $tarea->id,
                    'proyectoId' => $proyecto->id,
                    'mensaje' => 'Actualizado Correctamente'
                ];
                echo json_encode(['respuesta' => $respuesta]);
            }
            //echo json_encode(['resultado' => $resultado]); //me devueklve true
        }
    }



    public static function eliminar(){
        
        if(!isset($_SESSION)) {
            session_start();
        }

        isAuth();

        if($_SERVER['REQUEST_METHOD'] === 'POST'){

            // Validar que el proyecto exista
            $proyecto = Proyecto::where('url', $_POST['proyectoId']);

            if(!$proyecto || $proyecto->propietarioId !== $_SESSION['id']){
                $respuesta = [
                    'tipo' => 'error',
                    'mensaje' => 'Hubo un error al actualizar la tarea'
                ];
                echo json_encode($respuesta);
                return;
            }

            $tarea = new Tarea($_POST);
            $resultado = $tarea->eliminar();

            $resultado = [
                'resultado' => $resultado,
                'mensaje' => 'Eliminado Correctamente',
                'tipo' => 'exito'
            ];

            echo json_encode($resultado);
        }
    }
}