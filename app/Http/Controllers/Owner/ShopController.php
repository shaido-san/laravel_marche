<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;

class ShopController extends Controller
{
    //ここでログインしてるかの確認
    public function __construct()
    {
        $this->middleware('auth:owners');

        $this->middleware(function ($request, $next) {
           //dd($request->route()->parameter('shop'));//文字列
           //dd(Auth::id()); //数字
           $id = $request->route()->parameter('shop'); //shopのid取得
           if(!is_null($id)){ // null判定
           $shopsOwnerId = Shop::findOrFail($id)->owner->id;
                $shopId = (int)$shopsOwnerId; // キャスト 文字列→数値に型変換
                $ownerId = Auth::id();
                if($shopId !== $ownerId){ // 同じでなかったら
                    abort(404); // 404画面表示
                }
            }
            return $next($request);
        });
    } 
    public function index()
    {
        //ログインしているオーナーのidを取得できる。
        //$ownerId = Auth::id(); これを下のコードに移動
        $shops = Shop::where('owner_id', Auth::id())->get();

        return view('owner.shops.index',
        compact('shops'));
    }

    public function edit($id)
    {
        //dd(Shop::findOrFail($id));
        $shop = Shop::findOrFail($id);

        return view('owner.shops.edit', compact('shop'));
    }

    //サイズが大きすぎるとエラーが出る
    public function update(Request $request, $id)
    {
        $imageFile = $request->image;
        if(!is_null($imageFile) && $imageFile->isValid() ){
           Storage::putFile('public/shops', $imageFile);   
        }

        return redirect()->route('owner.shops.index');
    }

}