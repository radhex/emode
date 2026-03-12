<?php
use App\Classes\Util;
use App\Payment;

$input->appends( Input::only('order', 'ord') );
if(Input::has("select"))
    $input->appends( Input::only('select'));
if(Input::has("field") && Input::has("text"))
    $input->appends( Input::only('field', 'text','type') );
if(Input::has("type"))
    $input->appends( Input::only('type'));
?>

@extends('index')

@section('title')
    <title>Handleo - panel administracyjny</title>
    <meta name="Description" content="">
    <meta name="Keywords" content="">
@stop


@section('main')
    <div id="register" >
        <div class="margin-register">
            <div class="header">
                <div><a href="/admin/users" >Użytkownicy</a></div>
                <div><a href="/admin/comments/0">Komentarze</a></div>
                <div><a href="/admin/admins">Administratorzy</a></div>
                <div><a href="/admin/categories">Kategorie</a></div>
                <div><a href="/admin/products">Produkty</a></div>
                <div><a href="/admin/options">Opcje</a></div>
                <div><a href="/admin/sales/0">Transakcje</a></div>
                <div><a href="/admin/payments/0">Płatności</a></div>
                <div class="active"><a href="/admin/invoices">Faktury</a></div>
                <div><a href="/admin/currencies">Waluty</a></div>
                <div><a href="/admin/pages">Strony</a></div>
            </div>
          <div class="product-type">
                <a href="/admin/invoices?type=2"><div @if($type == 2) class="active" @else class="baton" @endif>Korekta</div></a>
                <a href="/admin/invoices?type=1"><div @if($type == 1) class="active" @else class="baton" @endif>Pro Forma</div></a>
                <a href="/admin/invoices?type=0"><div @if($type == 0) class="active" @else class="baton" @endif>VAT</div></a>
            </div>

            <div class="br"></div>

            <div class="product-search">
                <div>{!! csrf_field() !!}</div>
                <div class="item select"><div></div><select id="search-field" name="field">
                        <option  value="nr" {{Input::get("field") == "nr"? "selected" : ""}}>nr</option>
                        <option  value="id" {{Input::get("field") == "id"? "selected" : ""}}>id</option>
                    </select></div>

                <div class="item"><div></div><input id="search-text" type="text" size="30" name="text" value="{{Input::get("text")}}"/></div>
                <div class="item right"><input type="hidden" value="{{$type}}" id="search-type"><input type="button" value="SZUKAJ" class="but" id="search-button-invoices"></div>

            </div>


            <nav class="product-pagin up">
                @if($input->lastPage() > 1)
                    <div class="page"><input type="text" size="3" class="pagin-page" value="{{Input::get("page")}}" ></div>
                    {!!$input->render()!!}
                @endif
                <div class="total">ilość - {{$input->total()}}</div>
            </nav>

            <div class="table">
                <div class="row head">
                    <div style="width:30px;"></div>
                    <div style="width:150px;">{!! Util::order('number', 'numer') !!}</div>
                    <div>{!! Util::order('firm_name', 'użytkownik') !!}</div>
                    <div>{!! Util::order('brutto', 'wartość') !!}</div>
                    <div>{!! Util::order('make_at', 'data') !!}</div>
                    <div style="width:50px">{!! Util::order('disp', 'wyłącz') !!}</div>
                    </div>
                @foreach($input as $inp)
                    <div class="row"><div><input type="checkbox" id="ch{{$inp->id}}" @if(is_array(Session::get("select.invoice")) && in_array($inp->id,Session::get("select.invoice"))) checked  @endif><label class="checkbox" for="ch{{$inp->id}}"></label></div>
                        <div>{!! Html::link('/admin/invoice/'.$inp->id.'', ''.$inp->nr.'') !!}</div>
                        <div>{{$inp->firm_name}}</div>
                        <?php $pay = Payment::where("invoice_id","=",$inp->id)->first(); ?>
                        <div>{{$inp->brutto}} {{$inp->currency}}</div>
                        <div>{{Util::date($inp->make_at)}}</div>
                        <div>{{($inp->disp) ? '' : 'Tak' }}</div>
                        </div>
                @endforeach
            </div>


            <div class="sign" @if (sizeof(Session::get("select.invoice")) == 0 ) style='display:none' @endif >
                <a href="/admin/invoices?select=true">wybrane</a> - <span class="select">{{sizeof(Session::get("select.invoice"))}}</span>
            </div>
            <form id="form" method="POST" action="/admin/invoices"/>
            <div>{!! csrf_field() !!}</div>

            <div class="edit"><div class="msg">{{Session::get("status")}}</div><div class="baton addNew">Dodaj</div><div class="baton  deleteSelect">Usuń</div></div>
            </form>

        </div>

    </div> <!-- register end -->

    <div class="bbr"></div>
    <div class="bbr"></div>



@stop


@section('script')


    <script type="text/javascript">


        $(function() {


            $(".addNew").click(function() {
                window.location = "/admin/invoice/new";
            });

            $(".deleteSelect").click(function() {
                yesno(this,$("#form"));
            });

            if($(".msg").html())
                $(".msg").html($('#valid .' + $(".msg").html()).html());


            $(".row div input[type=checkbox]").click(function() {
                $.ajax({
                    type: "POST",
                    dataType: 'json',
                    url: "/admin/invoices/select",
                    data: "add=" + $(this).is(':checked') + "&id=" + $(this).attr("id").substr(2) + "&_token={{csrf_token()}}",
                    success: function (data) {

                        $(".sign .select").html(data.res);
                        if (data.res == 0)
                            $(".sign").hide();
                        else
                            $(".sign").show();

                    },
                    error:function(xhr,status,error){
                        if(xhr.status == "500")
                            $(form).find('.msg').html($("#valid .session").html());
                        else
                            alert('Error ' + xhr.status);

                    }
                });
            });

        });

    </script>
@stop
@section('error')
    @include('errors.valid')
@stop

