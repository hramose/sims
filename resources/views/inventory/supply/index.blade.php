@extends('backpack::layout')

@section('header')
	<section class="content-header">
		<legend><h3 class="text-muted">Supplies Inventory</h3></legend>
		<ol class="breadcrumb">
			<li>Inventory</li>
			<li class="active">Home</li>
		</ol>
	</section>
@endsection

@section('content')
<!-- Default box -->
  <div class="box">
    <div class="box-body">
		<div class="panel panel-body table-responsive">
		<table class="table table-hover table-bordered" id="supplyInventoryTable" width=100%>
			<thead>
				<tr>
					<th colspan="4" class="text-center">Information</th>
					<th colspan="2" class="text-center">Remaining Balance</th>
				</tr>
				<tr>
					<th class="col-sm-1">Stock No.</th>
					<th class="col-sm-1">Supply Item</th>
					<th class="col-sm-1">Unit</th>

					@if(Auth::user()->access == 1)
					<th class="col-sm-1">Reorder Point</th>
					@endif

					@if(Auth::user()->access == 2)
					<th class="col-sm-1">Cost (Ave)</th>
					@endif

					@if(Auth::user()->access == 2)
					<th class="col-sm-1">Ledger Card</th>
					@endif

					@if(Auth::user()->access == 1)
					<th class="col-sm-1">Stock Card</th>
					@endif

					@if(Auth::user()->access == 1 || Auth::user()->access == 2)
					<th class="col-sm-1 no-sort"></th>
					@endif
				</tr>
			</thead>
		</table>
		</div>

    </div><!-- /.box-body -->
  </div><!-- /.box -->

@endsection

@section('after_scripts')
<script>
	$(document).ready(function() {

	    var table = $('#supplyInventoryTable').DataTable({
	    	serverSide: true,
			language: {
					searchPlaceholder: "Search..."
			},
	    	columnDefs:[
	       	 { targets: 'no-sort', orderable: false },
	      	],
			@if(Auth::user()->access == 1 || Auth::user()->access == 2)
			"dom": "<'row'<'col-sm-3'l><'col-sm-6'<'toolbar'>><'col-sm-3'f>>" +
							"<'col-sm-12'<'search'>>" +
							"<'row'<'col-sm-12'tr>>" +
							"<'row'<'col-sm-5'i><'col-sm-7'p>>",
			@endif
			"processing": true,
			ajax: "{{ url('inventory/supply') }}",
			columns: [
					{ data: "stocknumber" },
					{ data: "details" },
					{ data: "unit.name" },

					@if(Auth::user()->access == 1)
					{ data: "reorderpoint" },
					@endif

					@if(Auth::user()->access == 2)
					{ data: function(callback){
						return parseFloat(callback.unitcost).toFixed(2).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
					} },
					@endif

					@if(Auth::user()->access == 2)
					{ data: "ledger_balance" },
					@endif

					@if(Auth::user()->access == 1)
					{ data: "stock_balance" },
					@endif

					@if(Auth::user()->access == 1 || Auth::user()->access == 2)
		            { data: function(callback){
		            	return `
		            			@if(Auth::user()->access == 1)
		            			<a href="{{ url("inventory/supply") }}` + '/' + callback.id  + '/stockcard' +`" class="btn btn-sm btn-primary">
		            				<span class="glyphicon glyphicon-list"></span> Stockcard
		            			</a>
								<a href="{{ url("inventory/supply") }}` + '/' + callback.id  + '/stockcard/print' +`" target="_blank" id="print" class="print btn btn-default ladda-button" data-style="zoom-in">
									<span class="glyphicon glyphicon-print" aria-hidden="true"></span>
									<span id="nav-text"> Print</span>
								</a>
		            			@endif
		            			@if(Auth::user()->access == 2)
		            			<a href="{{ url("inventory/supply") }}` + '/' + callback.id  + '/ledgercard' +`" class="btn btn-sm btn-primary">
		            				<span class="glyphicon glyphicon-list"></span> Supply Ledger
		            			</a>
			                      <a href="{{ url("inventory/supply") }}` + '/' + callback.id  + '/ledgercard/print' +`" target="_blank" id="print" class="print btn btn-sm btn-default ladda-button" data-style="zoom-in">
			              	        <span class="glyphicon glyphicon-print" aria-hidden="true"></span>
			              	        <span id="nav-text"> Print</span>
			              	      </a>
		            			@endif
		            	`;
		            } }
		            @endif
			],
	    });

	    $("div.search").html(`
			<a href="{{ url('inventory/supply/advancesearch') }}" style="font-size: 10px;" class="pull-right col-md-offset-11 col-md-1">Advance Search</a>
    	`)

		@if(Auth::user()->access == 1 || Auth::user()->access == 2)
	 	$("div.toolbar").html(`
			<a @if(Auth::user()->access == 1) href="{{ url("inventory/supply/stockcard/print") }}" @else href="{{ url("inventory/supply/ledgercard/print") }}" @endif target="_blank" id="print" class="print btn btn-sm btn-default ladda-button" data-style="zoom-in">
				<span class="glyphicon glyphicon-print" aria-hidden="true"></span>
				<span id="nav-text"> Print</span>
			</a>
			<a @if(Auth::user()->access == 1) href = "{{ url('inventory/supply/stockcard/accept') }}" @else href = "{{ url('inventory/supply/ledgercard/accept') }}" @endif  id="accept" class="btn btn-sm btn-success">
				<span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
				<span id="nav-text"> Accept</span>
			</a>
			<a @if(Auth::user()->access == 1) href = "{{ url('inventory/supply/stockcard/release') }}" @else href = "{{ url('inventory/supply/ledgercard/release') }}" @endif id="release" class="btn btn-sm btn-danger">
				<span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
				<span id="nav-text"> Release</span>
			</a>
		`);
		@endif
	} );
</script>
@endsection
