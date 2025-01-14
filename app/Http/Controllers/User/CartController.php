<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\User;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;




class CartController extends Controller
{
    public function index()
    {
        $user = User::findOrFail(Auth::id());
        $products = $user->products;
        $totalPrice = 0;

        foreach($products as $product){
            $totalPrice += $product->price * $product->pivot->quantity;
        }

        // dd($products, $totalPrice);
        return view('user.cart',
        compact('products', 'totalPrice'));
     }


    public function add(Request $request)
    {
       $itemInCart = Cart::where('product_id', $request->product_id)
        ->where('user_id', Auth::id())->first();

        if($itemInCart){
            $itemInCart->quantity += $request->quantity;
            $itemInCart->save();
        }else{
            Cart::create([
                'user_id' => Auth::id(),
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }
        
        return redirect()->route('user.cart.index');
    }

    public function delete($id)
    {
        Cart::where('product_id', $id)
        ->where('user_id', Auth::id())
        ->delete();

        return redirect()->route('user.cart.index');
    }

    public function checkout()
    {
        $user = User::findOrFail(Auth::id());
        $products = $user->products;

        # 在庫を確認し、決済前に在庫を減らしておく（他のユーザーもいるため）
        $lineItems = [];
        foreach($products as $product){
            $quantity ='';
            $quantity = Stock::where('product_id', $product->id)->sum('quantity');

            # 商品の中で一つでも買えないものがあれば、リダイレクトし、キャンセルする。
            if($product->pivot->quantity > $quantity){
                return redirect()->route('user.cart.index');
            } else {
                $lineItem = [
                    #ストライプver16.4で受け取れる形にする
                    'name' => $product->name,
                    'descrrption' => $product->information,
                    'amount' => $product->price,
                    'currency' => 'jpy',
                    'quantity' => $product->pivot->quantity,
    
                ];
                array_push($lineItems, $lineItem);

            }
            
        }
        // dd($lineItems);
        # カートに入れた分だけ在庫を減らす処理
        foreach($products as $product){
            Stock::create([
                'product_id' => $product->id,
                'type' => \Constant::PRODUCT_LIST['reduce'],
                'quantity' => $product->pivot->quantity * -1
             ]);
        }

        dd('テスト');


        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[$lineItems]],
            'mode' => 'payment',
            'success_url' => route('user.items.index'),
            'cancel_url' => route('user.cart.index'),
        ]);

    
        $publicKey = env('STRIPE_PUBLIC_KEY');

        return view('user.checkout',
        compact('session', 'publicKey'));
    }
}
