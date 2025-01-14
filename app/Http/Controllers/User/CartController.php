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

    // 在庫を確認し、決済前に在庫を減らしておく（他のユーザーもいるため）
    $lineItems = [];
    foreach ($products as $product) {
        $stockQuantity = Stock::where('product_id', $product->id)->sum('quantity');

        // 商品の中で一つでも買えないものがあれば、リダイレクトしキャンセルする
        if ($product->pivot->quantity > $stockQuantity) {
            return redirect()->route('user.cart.index')->with('error', '在庫が足りません');
        } else {
            $lineItem = [
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $product->name,
                        'description' => $product->information,
                    ],
                    'unit_amount' => $product->price
                ],
                'quantity' => $product->pivot->quantity,
            ];
            $lineItems[] = $lineItem; // 配列に追加
        }
    }

    // カートに入れた分だけ在庫を減らす処理
    foreach ($products as $product) {
        Stock::create([
            'product_id' => $product->id,
            'type' => \Constant::PRODUCT_LIST['reduce'],
            'quantity' => $product->pivot->quantity * -1,
        ]);
    }

    // Stripe APIキーを設定
    \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

    // Stripe Checkoutセッションを作成
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems, // 修正：配列の形に修正
        'mode' => 'payment',
        'success_url' => route('user.cart.success'),
        'cancel_url' => route('user.cart.cancel'),
    ]);

    $publicKey = env('STRIPE_PUBLIC_KEY');

    // チェックアウト画面にリダイレクト
    return view('user.checkout', compact('session', 'publicKey'));
  }

  public function success()
  {
    Cart::where('user_id', Auth::id())->delete();

    return redirect()->route('user.items.index');
  }

  public function cancel()
  {
    $user = User::findOrFail(Auth::id());
    foreach ($user->products as $product) {
        Stock::create([
            'product_id' => $product->id,
            'type' => \Constant::PRODUCT_LIST['add'],
            'quantity' => $product->pivot->quantity
        ]);
        }

        return redirect()->route('user.cart.index');
    }
}
