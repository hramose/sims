<?php

namespace App\Http\Controllers;

use App\PurchaseOrder;
use App\PurchaseOrderSupply;
use Validator;
use Carbon;
use Session;
use Auth;
use DB;
use App\SupplyTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;
class PurchaseOrderSupplyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        if(Request::ajax())
        {
              $purchaseordersupply = PurchaseOrderSupply::find($id);

              if(count($purchaseordersupply) > 0)
              {

                if(Input::has('unitprice'))
                {
                  $price = $this->sanitizeString(Input::get('unitprice'));
                  $purchaseordersupply->unitprice = $price;
                }

                if(Input::has('receivedquantity'))
                {
                  $quantity = $this->sanitizeString(Input::get('receivedquantity'));
                  $purchaseordersupply->receivedquantity = $quantity;
                }

                $purchaseordersupply->save();
                return json_encode('success');
              }

              return json_encode('error');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
