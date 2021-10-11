<?php
namespace App\Http\Controllers\Module_User\API;

session_start();
use App\Http\Controllers\Controller;
use App\Http\controllers\Module_User\API\UsuariosController;
use Laravel\Socialite\Facades\Socialite;

class GoogleProviderController extends Controller
{
    //Metodo para enviar peticion a 'Gmail', para inicio de sesion:
    public function redirect($url, $urlFail)
    {
        //Redirigimos al usuario al inicio de sesion de 'Gmail': 
        $_SESSION['url'] = $url;
        $_SESSION['urlFail'] = $urlFail;

        return Socialite::driver('google')->redirect();
        
    }

    //Metodo para recibir la informacion del usuario que inicia sesion con su cuenta de 'Gmail': 
    public function receive()
    {
       //Recibimos la informacion del usuario logeado por 'Gmail': 
        $user = Socialite::driver('google')->user();

        //Instanciamos el controlador de la clase 'Usuarios', para validar si existe ese usuario en el sistema: 
        $usersController = new UsuariosController;
        //Validamos si existe el usuario: 
        $validateUser = $usersController->show(email: $user->email);

        //Si existe, cambiamos su estado de sesion: 
        if($validateUser['Query']){

            //Estados de sesion: 
            //------------------
            //Estado de sesion 'Inactiva: 
            static $inactive = 'Inactiva';
            //Estado de sesion 'Activa':
            static $active   = 'Activa';
            //Estado de sesion 'Pendiente': 
            static $pending  = 'Pendiente';

            //Si el usuario no ha iniciado sesion con anterioridad, realizamos el logeo: 
            if($validateUser['User']['sesion'] == $inactive){

                //Cambiamos su estado de sesion a 'Activa:
                $newSession = $usersController->updateSession(email: $user->email, session: $active);

                //Si ocurre un error en el cambio de estado de sesion, lo retornamos: 
                if(!$newSession['register']){
                    //Retornamos el error: 
                    $error = $newSession['Error'];
                    $urlFail = $_SESSION['urlFail'];
                    return view('error', ['error' => $error, 'urlFail' => $urlFail]);
                }

                //Si el usuario no tiene URl de su avatar, le asignamos el de su cuenta de 'Gmail':  
                if($validateUser['User']['avatar'] == null){

                    //Asignamos la URL de su avatar: 
                    $avatarUpdate = $usersController->addAvatar(email: $user->email, url: $user->avatar);

                    //Si ocurre un error en la asignacion de su avatarm lo retornamos: 
                    if(!$avatarUpdate['Register']){
                        //Retornamos el error: 
                        $error = $avatarUpdate['Error'];
                        $urlFail = $_SESSION['urlFail'];
                        return view('error', ['error' => $error, 'urlFail' => $urlFail]);
                    }
                }

                return redirect('https://www.'.$_SESSION['url'].'.com');

            }elseif($validateUser['User']['sesion'] == $active){
                //Retornamos el error:
                $error = 'Este usuario ya inició sesión en el sistema.';
                $urlFail = $_SESSION['urlFail'];
                return view('error', ['error' => $error, 'urlFail' => $urlFail]);
                
            }elseif($validateUser['User']['sesion'] == $pending){
                //Retornamos el error:
                $error = 'Este usuario solicitó un restablecimiento de contraseña en el sistema.';
                $urlFail = $_SESSION['urlFail'];
                return view('error', ['error' => $error, 'urlFail' => $urlFail]);
            }

        }else{
            //Retornamos el error:
            $error = $validateUser['Error'];
            $urlFail = $_SESSION['urlFail'];
            return view('error', ['error' => $error, 'urlFail' => $urlFail]); 
        }
    }
}
