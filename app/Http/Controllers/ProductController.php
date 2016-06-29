<?php

namespace Proto\Http\Controllers;

use Illuminate\Http\Request;

use Proto\Http\Requests;
use Proto\Http\Controllers\Controller;

use Proto\Models\Product;
use Proto\Models\StorageEntry;

use Redirect;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $paginate = false;
        if ($request->has('search')) {
            $search = $request->get('search');
            $products = Product::where('name', 'like', "%$search%")->orWhere('nicename', 'like', "%$search%")->orderBy('is_visible', 'desc')->orderBy('name', 'asc')->get();
        } elseif ($request->has('filter')) {
            switch ($request->get('filter')) {

                case 'invisible':
                    $products = Product::where('is_visible', false)->orderBy('name', 'asc')->get();
                    break;

                default:
                    $paginate = true;
                    $products = Product::orderBy('is_visible', 'desc')->orderBy('name', 'asc')->paginate(15);
                    break;

            }
        } else {
            $paginate = true;
            $products = Product::orderBy('is_visible', 'desc')->orderBy('name', 'asc')->paginate(15);
        }

        return view('omnomcom.products.index', ['products' => $products, 'paginate' => $paginate]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        return view('omnomcom.products.edit', ['product' => null]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $product = Product::create($request->except('image'));
        $product->is_visible = $request->has('is_visible');
        $product->is_alcoholic = $request->has('is_alcoholic');
        $product->is_visible_when_no_stock = $request->has('is_visible_when_no_stock');

        if ($request->file('image')) {
            $file = new StorageEntry();
            $file->createFromFile($request->file('image'));

            $product->image()->associate($file);
        }

        $product->save();

        $request->session()->flash('flash_message', 'The new product has been created!');

        return Redirect::route('omnomcom::products::list', ['search' => $product->nicename]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        return view('omnomcom.products.show', ['product' => Product::findOrFail($id)]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        return view('omnomcom.products.edit', ['product' => Product::findOrFail($id)]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
        $product = Product::findOrFail($id);
        $product->fill($request->except('image'));
        $product->is_visible = $request->has('is_visible');
        $product->is_alcoholic = $request->has('is_alcoholic');
        $product->is_visible_when_no_stock = $request->has('is_visible_when_no_stock');

        if ($request->file('image')) {
            $file = new StorageEntry();
            $file->createFromFile($request->file('image'));

            $product->image()->associate($file);
        }

        $product->save();

        $request->session()->flash('flash_message', 'The product has been updated.');

        return Redirect::route('omnomcom::products::edit', ['id' => $product->id]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {

        $product = Product::findOrFail($id);

        if ($product->orderlines->count() > 0) {
            $request->session()->flash('flash_message', "You cannot delete this product because there are orderlines associated with it.");
            return Redirect::back();
        }

        $product->delete();

        $request->session()->flash('flash_message', "The product has been deleted.");
        return Redirect::back();        

    }
}
