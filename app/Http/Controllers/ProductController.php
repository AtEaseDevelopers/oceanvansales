<?php

namespace App\Http\Controllers;

use App\DataTables\ProductDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Repositories\ProductRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\SpecialPrice;
use App\Models\foc;
use Illuminate\Support\Facades\Session;
use Exception;
use App\Services\EInvoiceService;

class ProductController extends AppBaseController
{
    /** @var ProductRepository $productRepository*/
    private $productRepository;
    private $eInvoiceService;

    public function __construct(ProductRepository $productRepo, EInvoiceService $eInvoiceService)
    {
        $this->productRepository = $productRepo;
        $this->eInvoiceService = $eInvoiceService;
    }

    /**
     * Display a listing of the Product.
     *
     * @param ProductDataTable $productDataTable
     *
     * @return Response
     */
    public function index(ProductDataTable $productDataTable)
    {
        return $productDataTable->render('products.index');
    }

    /**
     * Show the form for creating a new Product.
     *
     * @return Response
     */
    public function create()
    {
        // If e-invoice is disabled, return simple view
        if (!$this->eInvoiceService->isEnabled()) {
            return view('products.create');
        }
        
        // If e-invoice is enabled, get classification codes
        $classificationOptions = $this->getClassificationOptions();
        
        return view('products.create', compact('classificationOptions'));
    }

    /**
     * Store a newly created Product in storage.
     *
     * @param CreateProductRequest $request
     *
     * @return Response
     */
    public function store(CreateProductRequest $request)
    {
        $input = $request->all();

        if(str_contains($input['name'],'"')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain double quote');
        }

        if(str_contains($input['name'],'\'')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain single quote');
        }

        $product = $this->productRepository->create($input);

        $tiers = array_filter($input['price_tiers'] ?? [], fn($t) => isset($t['price']) && $t['price'] !== '');
        foreach ($tiers as $tier) {
            ProductPrice::create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'price'      => $tier['price'],
                'status'     => 1,
            ]);
        }
        if (!empty($tiers)) {
            $firstPrice = reset($tiers)['price'];
            $product->price = $firstPrice;
            $product->save();
        }

        Flash::success($input['code'].__('products.saved_successfully'));

        return redirect(route('products.index'));
    }

    /**
     * Display the specified Product.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            Flash::error(__('products.product_not_found'));

            return redirect(route('products.index'));
        }

        $data = ['product' => $product];
        
        // Add classification data if e-invoice is enabled
        if ($this->eInvoiceService->isEnabled()) {
            $data['classificationOptions'] = $this->getClassificationOptions();
            // You might also want to get the current classification code
            $data['currentClassification'] = $product->classification_code ?? null;
        }

        return view('products.show', $data);
    }

    /**
     * Show the form for editing the specified Product.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            Flash::error(__('products.product_not_found'));

            return redirect(route('products.index'));
        }

        $data = ['product' => $product];
        
        // Add classification data if e-invoice is enabled
        if ($this->eInvoiceService->isEnabled()) {
            $data['classificationOptions'] = $this->getClassificationOptions();
            // You might also want to get the current classification code
            $data['currentClassification'] = $product->classification_code ?? null;
        }

        return view('products.edit', $data);
    }

    /**
     * Update the specified Product in storage.
     *
     * @param int $id
     * @param UpdateProductRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateProductRequest $request)
    {
        $id = Crypt::decrypt($id);
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            Flash::error(__('products.product_not_found'));

            return redirect(route('products.index'));
        }

        $input = $request->all();

        if(str_contains($input['name'],'"')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain double quote');
        }

        if(str_contains($input['name'],'\'')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain single quote');
        }

        $product = $this->productRepository->update($input, $id);

        ProductPrice::where('product_id', $product->id)->delete();
        $tiers = array_filter($input['price_tiers'] ?? [], fn($t) => isset($t['price']) && $t['price'] !== '');
        foreach ($tiers as $tier) {
            ProductPrice::create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'price'      => $tier['price'],
                'status'     => 1,
            ]);
        }
        if (!empty($tiers)) {
            $firstPrice = reset($tiers)['price'];
            $product->price = $firstPrice;
            $product->save();
        }

        Flash::success($product->code.__('products.updated_successfully'));

        return redirect(route('products.index'));
    }

    /**
     * Remove the specified Product from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            Flash::error(__('products.product_not_found'));

            return redirect(route('products.index'));
        }

        $SpecialPrice = SpecialPrice::where('product_id',$id)->get()->toArray();
        if(count($SpecialPrice)>0){
            Flash::error('Unable to delete '.$product->name.', '.$product->name.' is being used in Special Price');

            return redirect(route('products.index'));
        }

        $foc = foc::where('product_id',$id)->get()->toArray();
        if(count($foc)>0){
            Flash::error('Unable to delete '.$product->name.', '.$product->name.' is being used in Foc');

            return redirect(route('products.index'));
        }

        $this->productRepository->delete($id);

        Flash::success($product->code.__('products.deleted_successfully'));

        return redirect(route('products.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $Invoice = InvoiceDetail::where('product_id',$id)->get()->toArray();
            if(count($Invoice)>0){
                continue;
            }

            $SpecialPrice = SpecialPrice::where('product_id',$id)->get()->toArray();
            if(count($SpecialPrice)>0){
                continue;
            }

            $foc = foc::where('product_id',$id)->get()->toArray();
            if(count($foc)>0){
                continue;
            }

            $count = $count + Product::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = Product::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
    
    public function syncXero(Request $req)
    {
        try {
            $redirect_uri = config('app.url') . '/products/sync-xero';
            $xero = new XeroController($redirect_uri);

            if ($req->has('ids')) {
                $ids = explode(',', $req->ids);
                Session::put('ids_to_sync_xero', $ids);
            }
            // Get Xero's access token
            if ($req->has('code')) {
                $res = $xero->getToken($req->code);
                if (!$res->ok()) {
                    throw new Exception('Failed to get xero access token.');
                }
            }
            // Xero auth
            $res = $xero->auth();
            if ($res !== true) {
                return $res;
            }
            // Sync products
            $ids = Session::get('ids_to_sync_xero');
            $products = Product::whereIn('id',$ids)->get();
            
            for ($i = 0; $i < count($products) ;$i++) {
                $res = $xero->createItem($products[$i]->code, $products[$i]->name, $products[$i]->price);

                if (!$res->ok()) {  
                    throw new Exception('Failed to sync product.');
                }
            }
            
            Flash::success('Products synced to Xero.');
            return redirect(route('products.index'));
        } catch (\Throwable $th) {
            report($th);
            
            Flash::error('Something went wrong. Please contact administator.');
            return redirect(route('products.index'));
        }
    }

    /**
     * Get classification options for e-invoice
     *
     * @return array
     */
    protected function getClassificationOptions()
    {
        return \App\Models\ClassificationCode::orderBy('code')->get()->mapWithKeys(function ($code) {
            return [$code->code => $code->code . ' - ' . $code->description];
        })->toArray();
    }
}