<?php

namespace App\Http\Controllers\Products;


use Illuminate\Http\Request;

use DB;

use App\Http\Controllers\Controller;

use App\Products;

use App\Cart;

use App\Users;


class ProductsController extends Controller
{

    /**
     * show single product in the single page; 
     * with all info
     * 
     * @param int $id
     * 
     * @return App\Users::getUser(),
     *         App\Products::find($id);
     */

    public function single(int $id)
    {
        // STAGE 1; Initializing the classes; objects

        $user = new Users;
        $product = new Products;
        $cart = new Cart;

        // STAGE 2; Caching the arguments; user info; product's plain

        $userData = $user->getUser();
        $product = $product->find($id);

        $countIt = $cart->where(
            'users_id',
            $userData['id']
        )
        ->count();

        // STAGE 3; Logic Bomb; The logical Thinking;

        /**
         * -----------------------------------------------
         * the same thing as category not existing
         * ----------------------------------------------
         * @source redirect('/');
         * ----------------------------------------------
         * @see CategoryController.php LINE 61
         * ---------------------------------------------
         * if product doesn't exist; its id is not in DB,
         * we redirect back; 
         * to home page
         * ---------------------------------------------
         * 
         */
        if (is_null($product)) {
            return redirect('/');
        }

        return view('products.product')
                ->with('user', $userData)
                ->with('count', $countIt)
                ->with('product', $product);
    }

    /**
     * Adding the products to the cart;
     * 
     * @param Illuminate\Http\Request $request
     * 
     * @return mixed|array
     */

     public function addToCart(Request $request) 
     {
        // STAGE 1; Initializing the objects

        $cart = new Cart;
        $product = new Products;
        $user = new Users;

        // STAGE 2; Obtaining the arguments

        $userData = $user->getUser();
        $itemId = $request->id;

        // STAGE 3; The Logic Bomb

        $item = $product->find($itemId);

        // by click, we get the product's id and with that,
        // we search, and get all info about it;
        // put it to our method for adding that same product to the cart

        $cart->addItems($item, $itemId, $userData);
        $count = $cart->where(
            'users_id',
            $userData['id']
        )->count();
        $result['status'] = 1;
        $result['count'] = $count;
        $result['msg'] = 'Successfully added to cart!';
        return $result;
     }

    /**
     * Taking items from the cart;
     * look at them you can only with this method
     * 
     * @return view
     */

    public function getCartItems()
    {
        $cart = new Cart;
        $product = new Products;
        $user = new Users;
        $userData = $user->getUser();
        // if (is_null($userData))
        if (is_null($userData)) {
            return redirect('/');
        }

        $products = DB::select("SELECT t1.id, t2.id AS p_id, t1.title, t2.price, t2.quantity FROM products AS t1 
                                INNER JOIN cart AS t2 WHERE t1.id = t2.item_id AND t2.users_id = ". $userData['id']);
        $total = 0;
        foreach ($products as $p) {
            $total += $p->price;
        }

       /* 
       ////////////////////// 
        ANOTHER PRINCIPE TO DO IT
       ////////////////////
        // $use = $user->find($userData['id']);
        // $products = $use->cartItems;
        // $per = [];
        // $ren = [];
        // $total = 0;
        // foreach ($products as $p) {
        //     $total += $p->price;
        //     $ren[] = $p->id;
        //     $per[] = $product->find($p->item_id);
        // } */
        $countIt = $cart->where(
            'users_id',
            $userData['id']
        )
        ->count();
        return view('products.cart')
                ->with('user', $userData)
                ->with('count', $countIt)
                // ->with('ren', $ren)
                ->with('total', $total)
                // ->with('stuff', $per)
                ->with('stuff', $products);
    }

    /**
     * ---------------------------------------------------
     * Deleting items from the cart
     * By ID deleting the items
     * ---------------------------------------------------
     * 
     * @param Illuminate\Http\Request $request
     * 
     * @return mixed|array
     */

    public function deleteItems(Request $request) 
    {
        $cart = new Cart;
        $product = new Products;
        $user = new Users;
        $userData = $user->getUser();
        $itemId = $request->id;
        $delete = $cart->where(
            'id',
            $itemId
        )->delete();
        $count = $cart->where(
            'users_id',
            $userData['id']
        )
        ->count();
        if (is_null($delete)) {
            $result['status'] = 0;
            $result['msg'] = 'Error; something went wrong';
            return $result;
        }
        $total = 0;
        $products = DB::select("SELECT t1.id, t2.id AS p_id, t1.title, t2.price, t2.quantity FROM products AS t1 
                                INNER JOIN cart AS t2 WHERE t1.id = t2.item_id AND t2.users_id = ". $userData['id']);
        foreach ($products as $p) {
            $total += $p->price;
        }
        $result['status'] = 1;
        $result['msg'] = 'Successfully removed the item from your cart!';
        $result['count'] = $count;
        $result['total'] = $total;
        return $result;
    }

    /**
     * --------------------------------------------------------------
     * Delete all items from the cart;
     * if user clicked button with CSS selector ".deleteAll", 
     * it will redirect us here; to this method actually;
     * and this will remove from the Cart data from DB
     * ---------------------------------------------------------------
     * 
     * @param Illuminate\Http\Request $request
     * 
     * @return json|mixed
     */

    public function deleteAllItems(Request $request)
    {
        $cart = new Cart;
        $product = new Products;
        $user = new Users;
        $userData = $user->getUser();
        
        /**
         * Delete all user's items from the cart;
         * by meaning by User ID; delete by User Id;
         * 
         * @var $delete
         */
        $delete = $cart->where(
            'users_id',
            $userData['id']
        )->delete();

        // if delete isn't working

        if (is_null($delete)) {
            $result['status'] = 0;
            $result['msg'] = 'Error; Something went wrong...';
            return $result;
        }

        $result['status'] = 1;
        $result['msg'] = 'success';
        return $result;
    }

    /**
     * ------------------------------------------------------------------
     * But all items from the cart
     * with this method we'll send an E-Mail to customer to tell them
     * that they successfully bought the items;
     * --------------------------------------------------------------------
     * 
     * @param Illuminate\Http\Request $request
     * 
     * @return json|mixed
     */

    public function buyItems(Request $request)
    {
        $user = new Users;
        $cart = new Cart;
        $to = $request->email;
        $subject = 'Cart is full!';
        $message = "Dear \"{$request->name}\" you successfully did buy products, please pay before getting";
        $headers = 'From: noreply@larashop.tk';
        /* if (!mail($to, $subject, $message, $headers)) {
            $result['status'] = 0;
            $result['msg'] = 'Error sending an E-Mail';
            return $result;
        } */
        $userData = $user->getUser();
        $deleteItems = $cart->where(
            'users_id',
            $userData['id']
        )
        ->delete();
        // we'll send a test letter later;
        // for now; we do nothing;
        $result['status'] = 1;
        $result['msg'] = 'success';
        $result['message'] = $message;
        return $result;
    }

}
