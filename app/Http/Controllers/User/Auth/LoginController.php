<?php

namespace App\Http\Controllers\User\Auth;


use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\Users;

use App\Products;



class LoginController extends Controller
{

    /**
     * the main page of Sign In
     * 
     * @return view() -> login view
     */

    public function index()
    {
        $user = new Users;
        $userData = $user->getUser();
        return view('auth.login')
        ->with('user', $userData);
    }

    /**
     * Login process
     * 
     * @param Request $request
     */

    public function action(Request $request)
    {
        // STAGE 1; Initializing the classes;

        $user = new Users;
        $product = new Products;

        // STAGE 2; Arguments;
        
        $email = $request->email;
        $password = $request->password;

        /**
         * @var array $userData User
         */

        $userData = $user
        ->where([
            ['email', '=', $email]
        ])->first();

        /**
         * Fetching the all products from the db
         * 
         * @var array $products Products
         */
        
        $products = $product->all();

        // comparing inputed password with those from db

        if (password_verify($password, $userData['password'])) {
            $result['status'] = 1;
            $result['msg'] = 'Success';
            $result['html'] = view('pages.welcome')
             ->with('products', $products)
             ->render();
            $time = strtotime('+2 days');

            // setting cookies for 2 days

            setcookie('email', $email, $time, '/');
            setcookie('password', $password, $time, '/');
            return response()->json($result);
        } else {

            // if user's input is not same as from db, we get an error

            $result['status'] = 0;
            $result['msg'] = 'Wrong credetails; email or password';
            return $result;
        }

        $result['status'] = 1;
        $result['msg'] = 'success';
        $result['html'] = view('pages.welcome')
        ->render();
        return response()->json($result);
    }

}
