<?php
use App\Classes\Util;
use App\Payment;
use App\User;
use App\Invoice;




    if(Input::has("payment")){

        $user = User::find($input->user_id);
        $input->exchange =  $user->currency->exchange;
        $input->currency=  $user->currency->short;
    }

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
            <form id="form" method="POST" action="/admin/invoice/{{ Request::segment(3)}}"/>
            <input type="hidden" id="id" name="id" value="{{ Request::segment(3)}}">
            <input type="hidden"  name="user_id" value="{{$input->user_id}}">
            <input type="hidden"  name="payment" value="{{ Input::get("payment")}}">
            <input type="hidden" id="currency" name="currency" value="{{$input->currency}}">
            <input type="hidden" id="exchange" name="exchange" value="{{$input->exchange}}">
            <input type="hidden"  name="ispro" value="{{$input->ispro}}">
            <input type="hidden"  name="pro" value="{{$input->pro}}">
            <input type="hidden"  name="correct" value="{{$input->correct}}">
            {!! csrf_field() !!}
            <div class="form">
                <div class="head">Faktura {{ (Request::segment(3) == "new") ? "- nowa":"" }}</div>
                <div class="frame">


                    <div class="row"><div class="label"></div><div><div class="input">{!! Form::text("place", $input->place,['size' => '25']) !!} &nbsp;{!! Form::text("place_at", Util::date($input->place_at),['size' => '20','class'=>'datepicker']) !!}</div></div></div>

                    <div class="row"><div><div class="label">data wystawienia</div></div><div  class="row"><div class="input">{!! Form::text("make_at", Util::date($input->make_at),['size' => '20','class'=>'datepicker']) !!}</div></div><div class="label"></div><div class="input"></div></div>
                    <div class="row"><div><div class="label">faktura @if($input->correct > 0) korekta @endif</div></div><div  class="row">
                            <div class="input">
                                    <?php $faktura = ['VAT','Pro Forma']; ?>
                                <p>{!! Form::select('type', $faktura , $input->type) !!}</p>
                            </div>
                            <div class="label">nr.</div><div class="input">{!! Form::text("nr", $input->nr,['size' => '15']) !!}</div>

                            <div class="input">
                                <?php $rodzaj = ['ORYGINAL','KOPIA','DUPLIKAT','-- BRAK --']; ?>
                                <p>{!! Form::select('mode', $rodzaj , $input->mode) !!}</p>
                            </div>
                            @if($input->correct > 0)<div class="input" style="padding-top:14px;font-size:15px"> do faktury nr {{Invoice::find($input->correct)->nr}}</div>@endif
                        </div></div>
                    <div class="row"><div class="br"></div></div>
                    <div class="row"><div><div class="label">sprzedawca</div></div><div  class="row"><div class="input">{!! Form::text("name", $input->name,['size' => '35']) !!}</div><div class="label">NIP</div><div class="input">{!! Form::text("nip", $input->nip,['size' => '16']) !!}</div></div></div>
                    <div class="row"><div><div class="label">miejscowość</div></div><div  class="row"><div class="input">{!! Form::text("city", $input->city,['size' => '25']) !!}</div><div class="label">kod</div><div class="input">{!! Form::text("code", $input->code,['size' => '7']) !!}</div></div></div>
                    <div class="row"><div><div class="label">ulica</div></div><div  class="row"><div class="input">{!! Form::text("street", $input->street,['size' => '30']) !!}</div><div class="label">nr</div><div class="input">{!! Form::text("number", $input->number,['size' => '7']) !!}</div></div></div>

                    <div class="row"><div class="br"></div></div>
                    <div class="row"><div><div class="label">nabywca</div></div><div  class="row"><div class="input">{!! Form::text("firm_name", $input->firm_name,['size' => '35']) !!}</div><div class="label">NIP</div><div class="input">{!! Form::text("firm_nip", $input->firm_nip,['size' => '16']) !!}</div></div></div>
                    <div class="row"><div><div class="label">miejscowość</div></div><div  class="row"><div class="input">{!! Form::text("firm_city", $input->firm_city,['size' => '25']) !!}</div><div class="label">kod</div><div class="input">{!! Form::text("firm_code", $input->firm_code,['size' => '7']) !!}</div></div></div>
                    <div class="row"><div><div class="label">ulica</div></div><div  class="row"><div class="input">{!! Form::text("firm_street", $input->firm_street,['size' => '30']) !!}</div><div class="label">nr</div><div class="input">{!! Form::text("firm_number", $input->firm_number,['size' => '7']) !!}</div></div></div>
                    <div class="row"><div class="br"></div></div>
                    <div class="row"><div><div class="label">termin zapłaty</div></div><div  class="row">
                            <div class="input tekst">
                                {!! Form::text("paytime", $input->paytime,['size' => '6']) !!} dni
                            </div>
                            <div class="label">sposób zapłaty</div><div class="input">
                                <?php $faktura = ['Gotówka','Przelew']; ?>
                                <p>{!! Form::select('paytype', $faktura , $input->paytype) !!}</p>
                            </div></div></div>

                            <div class="row"><div class="label">Konto do przelewu</div><div><div class="input">{!! Form::text("note", $input->note,['size' => '50']) !!}</div></div></div>

                    <div class="bbr"></div>
                    <div class="table comment invoice">
                        <div class="row head" style="font-size:12px"><div style="width:30px;"></div><div style="width:250px;">nazwa</div><div>ilość</div><div >j.m.</div><div>cena brutto {{$input->currency}}</div><div>VAT %</div><div>wartość netto {{$input->currency}}</div><div>kwota VAT {{$input->currency}}</div><div>wartość brutto {{$input->currency}}</div></div>
                        @foreach($value as $val)
                        <div class="row irow"><div><img src="/img/minus.png" onclick="iminus($(this))"></div>
                            <div>{!! Form::text("product[]", $val->product,['size' => '30']) !!}</div>
                            <div>{!! Form::text("quantity[]", $val->quantity,['size' => '3','onkeyup'=>'invoice()']) !!}</div>
                            <div>{!! Form::text("measure[]", $val->measure,['size' => '3']) !!}</div>
                            <div>{!! Form::text("brutto[]", $val->brutto,['size' => '3','onkeyup'=>'invoice()']) !!}</div>
                            <div>{!! Form::text("vat[]", $val->vat,['size' => '3']) !!}</div>
                            <div>{!! Form::text("val_netto[]", '',['size' => '6']) !!}</div>
                            <div>{!! Form::text("val_vat[]", '',['size' => '6']) !!}</div>
                            <div>{!! Form::text("val_brutto[]", '',['size' => '6']) !!}</div>
                        </div>
                            @endforeach

                        <div class="row invoiceplus"><div><img src="/img/plus.png" class="iplus" > </div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div>{!! Form::text("sum_netto", '',['size' => '6']) !!}</div>
                            <div>{!! Form::text("sum_vat", '',['size' => '6']) !!}</div>
                            <div>{!! Form::text("sum_brutto", '',['size' => '6']) !!}</div>
                        </div>
                        <div class="row"><div> </div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div>wartość netto PLN<br>{!! Form::text("pl_netto", $input->pl_netto,['size' => '6']) !!}</div>
                            <div>kwota VAT PLN<br>{!! Form::text("pl_vat", $input->pl_vat,['size' => '6']) !!}</div>
                            <div>wartość brutto PLN<br>{!! Form::text("pl_brutto", $input->pl_brutto,['size' => '6']) !!}</div>
                        </div>
                    </div>
                    @if($input->exchange != 1)
                        <div class="kurs">Kurs: {{$input->exchange}}</div>
                    @endif
                    <div class="dozaplaty">Do <span id="pay"></span>: <span id="sum"></span></div>
                    @if(isset($input->disp))<div class="row"><div class="label text">wyłącz</div><div><div class="input check" style="vertical-align:top">{!! Form::checkbox("disp", null,!$input->disp,['id'=>'disp']) !!}<label class="checkbox" for="disp" id="ch11" ></label><div class="label" style="max-width:350px"></div></div></div></div>@endif
                    <div class="row"><div class="br"></div></div>


                    <div class="baton"><input type="submit"  value="Zapisz" class="bform">
                    
                    @if(Request::segment(3) != "new")&nbsp;&nbsp;<input type="button"  value="Pobierz PDF" class="bform" onclick="window.location='/admin/invoicepdf/{{$input->id}}'">
                        
                        @if($input->correct < 1 && !$input->ispro)&nbsp;&nbsp;<input type="button"  value="Wystaw korektę" class="bform" onclick="window.location='/admin/invoice/new?korekta={{$input->id}}'">@endif
                        @if($input->ispro)&nbsp;&nbsp;<input type="button"  value="Faktura VAT" class="bform" onclick="window.location='/admin/invoice/new?vat={{$input->id}}'">@endif
                   
                    @endif

                        <div class="load"></div>
                        <div class="status">{{ Session::get('status')}}</div>
                    </div>


                    </form>

                </div>



            </div>

        </div>
    </div> <!-- register end -->

    <div class="bbr"></div>

@stop


@section('script')
    <script type="text/javascript" src="/js/jquery.ui.datepicker-pl.js"></script>
    <script type="text/javascript" src="/js/jquery-ui-timepicker-addon.js"></script>
    <script type="text/javascript">


        function iminus(sel){
            sel.parent().parent().remove();
            invoice();
        }

        $(function() {

            invoice("start");

            $(".iplus").click(function(){
               $('<div class="row irow"><div><img src="/img/minus.png" class="iminus" onclick="iminus($(this))"></div><input type="hidden" id="base_brutto" name="base_brutto[]" value=""><div><input name="product[]" size="30"></div><div><input name="quantity[]" size="3" onkeyup="invoice()"></div><div><input name="measure[]" size="3"></div><div><input name="brutto[]" size="6" onkeyup="invoice()"></div><div><input name="vat[]" size="3" onkeyup="invoice()"></div><div><input name="val_netto[]" size="6"></div><div><input name="val_vat[]" size="6"></div><div><input name="val_brutto[]" size="6"></div>').insertBefore(".invoiceplus");
               invoice();
            });


                        $.datepicker.setDefaults( $.datepicker.regional[ "pl" ] );
            $(".datepicker").datetimepicker({
                changeMonth: true,
                changeYear: true,
                "dateFormat": "yy-mm-dd",

            });
            $(".datepicker").css("padding","5px");

            $('#clearCategory').click(function () {
                $('#addCategory').html("");
                $('#addCategoryInput').val(0);
            });

        });

    </script>
    <script type="text/javascript" src="/js/validate.js"></script>
@stop
@section('error')

    @foreach ($errors->getMessages() as $key => $value)
        <div id="error-{{$key}}" class="validator">{{$value[0]}}</div>
    @endforeach
    @include('errors.valid')
@stop
