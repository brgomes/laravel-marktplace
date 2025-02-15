<?php

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->has('cart') ? session()->get('cart') : [];

        return view('cart', compact('cart'));
    }

    public function add(Request $request)
    {
        $productData = $request->get('product');
        $product = Product::whereSlug($productData['slug'])->first(['id', 'name', 'price', 'store_id']);

        if ((null === $product) || ($productData['number'] <= 0)) {
            return redirect()->route('home');
        }

        $product = array_merge($productData, $product->toArray());

        if (session()->has('cart')) {
            $products = session()->get('cart');
            $productsSlugs = array_column($products, 'slug');

            if (in_array($product['slug'], $productsSlugs)) {
                $products = $this->productIncrement($product['slug'], $product['number'], $products);
                session()->put('cart', $products);
            } else {
                session()->push('cart', $product);
            }
        } else {
            $products = [$product];
            session()->put('cart', $products);
        }

        flash('Produto adicionado no carrinho.')->success();

        return redirect()->route('product.single', ['slug' => $product['slug']]);
    }

    public function remove($slug)
    {
        if (!session()->has('cart')) {
            return redirect()->route('cart.index');
        }

        $products = session()->get('cart');

        $products = array_filter($products, function ($line) use ($slug) {
            return $line['slug'] != $slug;
        });

        session()->put('cart', $products);

        return redirect()->route('cart.index');
    }

    public function cancel()
    {
        session()->forget('cart');

        flash('Desistência da compra realizada com sucesso.')->success();

        return redirect()->route('cart.index');
    }

    private function productIncrement($slug, $qtd, $products)
    {
        $products = array_map(function ($line) use ($slug, $qtd) {
            if ($slug == $line['slug']) {
                $line['number'] += $qtd;
            }
            return $line;
        }, $products);

        return $products;
    }
}
