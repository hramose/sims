<?php

namespace App\Http\Controllers;

use App;
use Auth;
use DB;
use Carbon;
use Session;
use PDF;
use Validator;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->ajax())
        {

          $ret_val = App\Request::with('office')->with('requestor');

          if(Auth::user()->access != 1)
          {
            if(Auth::user()->position == 'head') $ret_val->findByOffice( Auth::user()->office );
            else $ret_val->me();
          }

          return json_encode([
              'data' => $ret_val->get()
          ]);
        }

        return view('request.index')
                ->with('title','Request');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
      $code = $this->generate($request);

      return view('request.create')
              ->with('code',$code)
              ->with('title','Request');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      $stocknumbers = $request->get("stocknumber");
      $quantity = $request->get("quantity");
      $quantity_issued = null;
      $array = [];
      $office = App\Office::findByCode(Auth::user()->office)->id;
      $status = null;
      $purpose = $request->get("purpose");;
      $requestor = Auth::user()->id;

      foreach(array_flatten($stocknumbers) as $stocknumber)
      {
        if($stocknumber == '' || $stocknumber == null || !isset($stocknumber))
        {
          \Alert::error('Encountered an invalid stock! Resetting table')->flash();
           return redirect("request/create");
        }

        $validator = Validator::make([
            'Purpose' => $purpose,
            'Stock Number' => $stocknumber,
            'Quantity' => $quantity["$stocknumber"]
        ],App\Request::$issueRules);

        if($validator->fails())
        {
            return redirect("request/create")
                    ->with('total',count($stocknumbers))
                    ->with('stocknumber',$stocknumbers)
                    ->with('quantity',$quantity)
                    ->withInput()
                    ->withErrors($validator);
        }

        array_push($array,[
            'quantity_requested' => $quantity["$stocknumber"],
            'supply_id' => App\Supply::findByStockNumber($stocknumber)->id,
            'quantity_issued' => $quantity_issued
        ]);
      }

      DB::beginTransaction();

      $request = App\Request::create([
        'requestor_id' => $requestor,
        'issued_by' => null,
        'office_id' => $office,
        'remarks' => null,
        'purpose' => $purpose,
        'status' => $status
      ]);

      $request->supplies()->sync($array);

      DB::commit();

      \Alert::success('Request Sent')->flash();
      return redirect('request');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$id)
    {
        $id = $this->sanitizeString($id);

        if($request->ajax())
        {

          $supplies = App\Request::find($id)->supplies;
          return json_encode([
            'data' => $supplies
          ]);
        }

        $requests = App\Request::find($id);
        return view('request.show')
              ->with('request',$requests)
              ->with('title','Request');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $request = App\Request::find($id);

        return view('request.edit')
                ->with('request',$request)
                ->with('title',$request->id);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
      $stocknumbers = $request->get("stocknumber");
      $quantity = $request->get("quantity");
      $issued_by = Auth::user()->username;
      $office = Auth::user()->id;
      $id = $this->sanitizeString($id);

      /**
       * [$array description]
       * variable used for storing stock details
       * to be used by sync method in request
       * @var array
       */
      $array = [];

      foreach(array_flatten($stocknumbers) as $stocknumber)
      {
        $validator = Validator::make([
            'Stock Number' => $stocknumber,
            'Quantity' => $quantity["$stocknumber"]
        ],App\Request::$issueRules);

        /**
         * [$supply description]
         * returns the supply details found
         * using stocknumber as search attribute
         * @var [type]
         */
        $supply = App\Supply::findByStockNumber($stocknumber);

        if($validator->fails() || count($supply) <= 0 )
        {
          if(count($supply) <= 0)
          {
              \Alert::error("No information found for Supply with stocknumber of $stocknumber")->flash();
          }

          return redirect("request/$id/edit")
                  ->with('total',count($stocknumbers))
                  ->with('stocknumber',$stocknumbers)
                  ->with('quantity',$quantity)
                  ->withInput()
                  ->withErrors($validator);
        }

        $array[ $supply->id ] = [
            'quantity_requested' => $quantity["$stocknumber"]
        ];
      }

      // $array = $array[0];
      // return $array;
      // return $id;
      // return array_flatten($array);

      DB::beginTransaction();
      App\Request::find($id)->supplies()->sync($array);
      DB::commit();

      \Alert::success('Request Updated')->flash();
      return redirect("request/$id");

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function releaseView(Request $request, $id)
    {
        $requests = App\Request::find($id);

        return view('request.release')
                ->with('request',$requests)
                ->with('title',$requests->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request,$id)
    {

      $daystoconsume = $request->get('daystoconsume');
      $quantity = $request->get('quantity');
      $stocknumber = $request->get('stocknumber');
      $date = Carbon\Carbon::now();

      DB::beginTransaction();

      /**
       * find the requests details
       * change the status to released
       * assigned the current user as the one
       * who released the requests
       * @var [type]
       */
      $requests = App\Request::find($id);
      $requests->status = 'released';
      $requests->released_at = $date;
      $requests->released_by = Auth::user()->id;
      $requests->save();

      $reference = $requests->code;
      $office = $requests->office->name;

      foreach($stocknumber as $stocknumber)
      {

        $_daystoconsume = "";
        $_quantity = 0;
        if(isset($daystoconsume["$stocknumber"]) && $daystoconsume["$stocknumber"] != null)
        {
            $_daystoconsume = $this->sanitizeString($daystoconsume["$stocknumber"]);
        }

        if(isset($quantity["$stocknumber"]) && $quantity["$stocknumber"] != null)
        {
          $_quantity = $this->sanitizeString($quantity["$stocknumber"]);
        }


        $validator = Validator::make([
          'Stock Number' => $stocknumber,
          'Requisition and Issue Slip' => $reference,
          'Date' => $date,
          'Issued Quantity' => $_quantity,
          'Office' => $office,
          'Days To Consume' => $_daystoconsume
        ],App\StockCard::$issueRules);

        $supply = App\Supply::findByStockNumber($stocknumber);
        if($validator->fails() || $_quantity > $supply->stock_balance)
        {

          DB::rollback();

          if($quantity > $balance)
          {
            $validator = [ "You cannot release quantity of $stocknumber which is greater than the remaining balance ($supply->stock_balance)" ];
          }

          return back()
              ->with('total',count($stocknumber))
              ->with('stocknumber',$stocknumber)
              ->with('quantity',$quantity)
              ->with('daystoconsume',$daystoconsume)
              ->withInput()
              ->withErrors($validator);
        }

        $transaction = new App\StockCard;
        $transaction->date = $date;
        $transaction->stocknumber = $stocknumber;
        $transaction->reference = $reference;
        $transaction->organization = $office;
        $transaction->issued_quantity  = $_quantity;
        $transaction->daystoconsume = $_daystoconsume;
        $transaction->user_id = Auth::user()->id;
        $transaction->issue();

        $requests->supplies()->updateExistingPivot($supply->id, [
          'quantity_released' => $_quantity
        ]);
      }

      DB::commit();



      \Alert::success('Items Released')->flash();
      return redirect('request');

    }

    public function getApproveForm(Request $request, $id)
    {
        $requests = App\Request::find($id);
        

        return view('request.approval')
                ->with('request',$requests)
                ->with('title',$request->code);
    }

    public function approve(Request $request, $id)
    {

        if($request->ajax())
        {
            $id = $this->sanitizeString($id);
            $status = $this->sanitizeString($request->get('status'));
            $remarks = $this->sanitizeString($request->get('reason'));

            $request = App\Request::find($id);
            $request->status = $status;
            $request->approved_at = Carbon\Carbon::now();
            $request->remarks = $remarks;
            $request->save();

            return json_encode('success');
        }

        $id = $this->sanitizeString($id);
        $quantity = $request->get('quantity');
        $comment = $request->get('comment');
        $stocknumbers = $request->get('stocknumber');
        $requested = $request->get('requested');
        $array = [];
        $remarks = $this->sanitizeString( $request->get('remarks') );
        $issued_by = Auth::user()->id;

        foreach($stocknumbers as $stocknumber)
        {

          $supply = App\Supply::findByStockNumber($stocknumber);

          $validator = Validator::make([
              'Stock Number' => $stocknumber,
              'Quantity' => $quantity["$stocknumber"]
          ],App\Request::$issueRules);

          if($validator->fails())
          {
              return redirect("request/$id/edit")
                      ->with('total',count($stocknumbers))
                      ->with('stocknumber',$stocknumbers)
                      ->with('quantity',$quantity)
                      ->withInput()
                      ->withErrors($validator);
          }

          $array [ $supply->id ] = [
            'quantity_requested' => (isset($requested[$stocknumber])) ? $requested[$stocknumber] : 0,
            'quantity_issued' => $quantity[$stocknumber],
            'comments' => $comment[$stocknumber]
          ];
        }

        DB::beginTransaction();

        $request = App\Request::find($id);
        $request->remarks = $remarks;
        $request->issued_by = $issued_by;
        $request->status = 'approved';
        $request->approved_at = Carbon\Carbon::now();
        $request->save();

        $request->supplies()->sync($array);

        DB::commit();

        \Alert::success('Request Approved')->flash();
        return redirect('request');

    }

    public function disapprove(Request $request, $id)
    {
        if($request->ajax())
        {
            $id = $this->sanitizeString($id);
            $remarks = $this->sanitizeString($request->get('reason'));

            $request = App\Request::find($id);
            $request->status = "disapproved";
            $request->approved_at = Carbon\Carbon::now();
            $request->remarks = $remarks;
            $request->save();

            return json_encode('success');
        }

        DB::beginTransaction();

        $request = App\Request::find($id);

        $request->status = 'disapproved';
        $request->approved_at = Carbon\Carbon::now();
        $request->save();

        DB::commit();

        \Alert::success('Request Disapproved')->flash();
        return redirect('request');

    }

    public function getCancelForm($id)
    {
        $request = App\Request::find($id);

        return view('request.cancel')
                ->with('request',$request)
                ->with('title',$request->id);
    }

    public function cancel(Request $request, $id)
    {

      $details = $this->sanitizeString($request->get('details'));

      DB::beginTransaction();

      $requests = App\Request::find($id);
      $requests->status = "cancelled";
      $requests->cancelled_by = Auth::user()->id;
      $requests->cancelled_at = Carbon\Carbon::now();
      $requests->remarks = $details;
      $requests->save();

      DB::commit();

      \Alert::success("$requests->code Cancelled")->flash();
      return redirect('request');
    }

    /**
     * Display the specified comments.
     *
     *
     * 
     */
    public function getComments(Request $request,$id)
    {
        $id = $this->sanitizeString($id);
        $requests = App\Request::find($id);

        if( count($requests) <= 0 ) return view('errors.404');

        if($request->ajax())
        {
          return json_encode([
            'data' => $requests->comments()
          ]);
        }

        $comments = $requests->comments()->orderBy('created_at','desc')->get();

        return view('request.comments')
              ->with('request',$requests)
              ->with('comments',$comments);

    }

    public function postComments(Request $request,$id)
    {
      
      $comments = new App\RequestComments;
      $comments->request_id = $id;
      $comments->details = $request->get('details');
      $comments->user_id = Auth::user()->id;
      $comments->save();

      return back();
    }

    public function print($id)
    {
      $id = $this->sanitizeString($id);
      $request = App\Request::find($id);

      $data = [
        'request' => $request, 
        'approvedby' => App\Office::where('code','=','OVPAA')->first()
      ];

      $filename = "Request-".Carbon\Carbon::now()->format('mdYHm')."-$request->code".".pdf";
      $view = "request.print_show";

      return $this->printPreview($view,$data,$filename);
    }

    public function generate(Request $request)
    {

      $requests = App\Request::orderBy('created_at','desc')->first();
      $id = 1;
      $now = Carbon\Carbon::now();
      $const = $now->format('y') . '-' . $now->format('m');

      if(count($requests) > 0)
      {
        $id = $requests->id + 1;
      }
      else
      {
        $id = count(App\StockCard::filterByIssued()->get()) + 1;
      }

      if($request->ajax())
      {
        return json_encode( $const . '-' . $id ); 
      }

      return $const . '-' . $id;

    }
}
