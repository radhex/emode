<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style>
    .invoicepdf {font-size:14px;font-family:Tahoma;color:#333}
    .invoicepdf div {padding-top:4px;padding-bottom:4px}
    .invoicepdf .seller {float:left}
    .invoicepdf .head {font-size:16px}
    .invoicepdf .product div{display:table-cell;padding-left:5px;padding-right:5px;font-size:12px;border-left:1px solid #333;border-top:1px solid #333;text-align:right}
    .invoicepdf .product .row{display:table-row;}
    .invoicepdf .product .noborder div{border:0}
    .invoicepdf .product .noleft {border-left:0px}
    .invoicepdf .product .right {border-right:1px solid #333}
    .invoicepdf .product .bottom {border-bottom:1px solid #333}
    .invoicepdf .product .margintop div{padding-top:10px}
    .invoicepdf .product .nomargintop div{padding-top:0px}
    .invoicepdf .product .aleft {text-align:left}

</style>
<script>

    function slowa(kwota,waluta){


        kwota = kwota.replace("-","");

        zlote = kwota.substr(0,kwota.length-3);
        grosze = kwota.substr(kwota.length-2,2);

        if(zlote.length == 1) zlote = "00" + zlote;
        if(zlote.length == 2) zlote = "0" + zlote;


        dec = new Array();
        dec[0] = new Array('', 'tysiąc ','milion ', 'miliard ', 'bilion ', 'biliard ', 'trylion ', 'tryliard ', 'kwadrylion ', 'kwadryliard ', 'kwintylion ', 'kwintyliard ');
        dec[1] = new Array('', 'tysiące ', 'miliony ', 'miliardy ', 'biliony ', 'biliardy ', 'tryliony ', 'tryliardy ', 'kwadryliony ', 'kwadryliardy ', 'kwintyliony ', 'kwintyliardy ');
        dec[2] = new Array('', 'tysięcy ','milionów ', 'miliardów ', 'bilionów ', 'biliardów ', 'trylionów ', 'tryliardów ', 'kwadrylionów ', 'kwadryliardów ', 'kwintylionów ', 'kwintyliardów ');

        lb = new Array();
        lb[0] = new Array('', 'jeden ', 'dwa ', 'trzy ', 'cztery ', 'pięć ', 'sześć ', 'siedem ', 'osiem ', 'dziewięć ', 'dziesięć ', 'jedenaście ', 'dwanaście ', 'trzynaście ', 'czternaście ', 'piętnaście ', 'szesnaście ', 'siedemnaście ', 'osiemnaście ', 'dziewiętnaście ');
        lb[1] = new Array('','dziesięć ', 'dwadzieścia ', 'trzydzieści ', 'czterdzieści ', 'pięćdziesiąt ', 'sześćdziesiąt ', 'siedemdziesiąt ', 'osiemdziesiąt ', 'dziewięćdziesiąt ');
        lb[2] = new Array('','sto ', 'dwieście ', 'trzysta ', 'czterysta ', 'pięćset ', 'sześćset ', 'siedemset ', 'osiemset ', 'dziewięćset ');

        cur = new Array();

        if(waluta == "PLN") {
            cur[0] = new Array('', 'złoty ','grosz');
            cur[1] = new Array('', 'złote ', 'grosze');
            cur[2] = new Array('', 'złotych ','groszy');
        }

        if(waluta == "EUR") {
            cur[0] = new Array('', 'euro ','cent');
            cur[1] = new Array('', 'euro ', 'centy');
            cur[2] = new Array('', 'euro ','centów');
        }

        if(waluta == "USD") {

            cur[0] = new Array('', 'dolar ','cent');
            cur[1] = new Array('', 'dolary ', 'centy');
            cur[2] = new Array('', 'dolarów ', 'centów');
        }

        if(waluta == "GBP") {

            cur[0] = new Array('', 'funt szterling ','pens');
            cur[1] = new Array('', 'funty szterlingi ', 'pensy');
            cur[2] = new Array('', 'funtów szterlingów ', 'pensów');
        }


        if(waluta == "RUB") {

            cur[0] = new Array('', 'rubel ','kopiejka');
            cur[1] = new Array('', 'ruble ', 'kopiejki');
            cur[2] = new Array('', 'rubli ', 'kopiejek');
        }

        if(waluta == "CHF") {

            cur[0] = new Array('', 'frank ','centym');
            cur[1] = new Array('', 'franki ', 'centymy');
            cur[2] = new Array('', 'franków ', 'centymów');
        }

        if(waluta == "JPY") {

            cur[0] = new Array('', 'jen ','sen');
            cur[1] = new Array('', 'jeny ', 'seny');
            cur[2] = new Array('', 'jenów ', 'senów');
        }

        if(waluta == "CNY") {

            cur[0] = new Array('', 'juan ','fen');
            cur[1] = new Array('', 'juany ', 'feny');
            cur[2] = new Array('', 'juanów ', 'fenów');
        }


        t = zlote.split(' ');
        ret = '';
        for(i=0; i< t.length; i++){

            if(i == t.length - 1)
                ret += slowa_podzial(t[i], t.length-i-1, 1);
            else
                ret += slowa_podzial(t[i], t.length-i-1);

        }

        ret += slowa_podzial('0'+grosze, 0 , 2);

        return ret;

    }


    function slowa_podzial(liczba, decymal, currency){

        ret = '';

        zn = parseFloat(liczba);
        if(zn < 20) {
            if(zn != 0)
                ret += lb[0][zn];
        }
        else if(zn >= 20 && zn < 100)
            ret += lb[1][zn.toString().substr(0,1)] + lb[0][zn.toString().substr(1,1)];

        else {
            if(parseFloat(liczba.substr(1,2)) < 20)
                ret += lb[2][liczba.substr(0,1)] +  lb[0][parseFloat(liczba.substr(1,2))];
            else
                ret += lb[2][zn.toString().substr(0,1)] + lb[1][zn.toString().substr(1,1)] + lb[0][zn.toString().substr(2,1)];

        }

        if(liczba.length <= 3 && zn == 0)
            ret +="zero ";

        if(zn < 20) {
            if(zn == 1){if(!currency) ret += dec[0][decymal]; else ret += cur[0][currency];}
            else  if(zn > 1 && zn < 5) { if(!currency) ret += dec[1][decymal]; else ret += cur[1][currency]; }
            else { if(!currency) ret += dec[2][decymal]; else ret += cur[2][currency];}
        }
        else {
            if(liczba.substr(2,1) == 1 || liczba.substr(2,1) > 4 || liczba.substr(2,1) == 0 || (parseFloat(liczba.substr(1,2)) < 20 && parseFloat(liczba.substr(1,2)) > 4))
            { if(!currency) ret += dec[2][decymal]; else ret += cur[2][currency];}

            else { if(!currency) ret += dec[1][decymal]; else ret += cur[1][currency];}
        }


        return ret;
    }


</script>

<?php
use App\Classes\Util;
use App\Payment;
use App\User;
use App\Invoice;


$exchange =  $input->exchange;
$currency =  $input->currency;

?>


<div id="register" >
    <div class="margin-register">

        <div class="invoicepdf">
            <div style="text-align:right">{{$input->place}}: {{Util::dateUTC2($input->place_at,1)}} UTC+2</div>
            <div style="text-align:right">Data sprzedaży: {{Util::dateUTC2($input->make_at,1)}} UTC+2</div>
            <?php $faktura = ['VAT','Pro Forma']; if($input->type == "") $input->type = 0;?>
            <div style="text-align:center;font-size:22px;padding-top:10px">Faktura {{$faktura[$input->type]}} @if($input->correct > 0) korekta @endif nr {{$input->nr}}</div>
            @if($input->correct > 0)<div style="text-align:center;font-size:18px;padding-top:0px">do faktury nr {{Invoice::find($input->correct)->nr}}</div>@endif
            <?php $rodzaj = ['ORYGINAŁ','KOPIA','DUPLIKAT','-- BRAK --']; if($input->mode == "") $input->mode = 0;?>
            <div style="text-align:center;">{{$rodzaj[$input->mode]}}</div>

            <div style="display:table-cell;padding-right:20px;padding-top:20px">
                <div class="head">Dane sprzedawcy</div>
                <div>{{$input->name}}</div>
                <div>NIP: {{$input->nip}}</div>
                <div>{{$input->code}} {{$input->city}}</div>
                <div>ul. {{$input->street}} {{$input->number}}</div>
            </div>
            <div style="display:table-cell">
                <div class="head">Dane nabywcy</div>
                <div>{{$input->firm_name}}</div>
                <div>NIP: {{$input->firm_nip}}</div>
                <div>{{$input->firm_code}} {{$input->firm_city}}</div>
                <div>ul. {{$input->firm_street}} {{$input->firm_number}}</div>
            </div>

            <div style="padding-top:20px">Termin zapłaty: {{$input->paytime}} dni</div>
            <?php $faktura = ['gotówka','przelew']; if($input->paytype == "") $input->paytype = 1;?>
            <div>Sposób zapłaty: {{$faktura[$input->paytype]}}</div>
            <div style="padding-bottom:20px">Konto do przelewu: {{$input->note}}</div>
            <div class="product">
                <div class="row head"><div style="width:30%"></div> <div>ilość</div><div>j.m.</div><div>cena brutto {{$currency}}</div><div>VAT %</div><div>wartość netto {{$currency}}</div><div>kwota VAT {{$currency}}</div><div class="right">wartość brutto {{$currency}}</div></div>
                <?php
                $sum_brutto = 0;
                $sum_vat = 0;
                $sum_netto = 0;
                ?>
                @foreach($value as $val)
                    <?php
                    $brutto = str_replace(",",".",str_replace(" ","",$val->brutto));
                    $val_brutto = $val->quantity * $brutto;
                    $val_vat = round(($val->vat/100) * $val_brutto,2);
                    $val_netto = $val_brutto - $val_vat;
                    $sum_brutto += $val_brutto;
                    $sum_vat += $val_vat;
                    $sum_netto += $val_netto;
                    ?>
                    <div class="row"><div class="aleft">{{$val->product}}</div> <div>{{$val->quantity}}</div><div>{{$val->measure}}</div><div>{{Util::dkwota($val->brutto)}}</div><div>{{$val->vat}}</div><div>{{Util::dkwota($val_netto)}}</div><div>{{Util::dkwota($val_vat)}}</div><div class="right">{{Util::dkwota($val_brutto)}}</div></div>
                @endforeach

                <div class="row"><div class="noleft"></div> <div class="noleft"></div><div class="noleft"></div><div class="noleft"></div><div class="noleft">razem</div><div  class="bottom">{{Util::dkwota($sum_netto)}}</div><div class="bottom">{{Util::dkwota($sum_vat)}}</div><div  class="right bottom">{{Util::dkwota($sum_brutto)}}</div></div>

                <div class="row noborder  margintop"><div></div> <div></div><div></div><div></div><div></div><div>wartość netto PLN</div><div>wartość VAT PLN</div><div>wartość brutto PLN</div></div>
                <div class="row noborder  nomargintop"><div></div> <div></div><div></div><div></div><div></div><div>{{Util::dkwota($input->pl_netto)}}</div><div>{{Util::dkwota($input->pl_vat)}}</div><div>{{Util::dkwota($input->pl_brutto)}}</div></div>


            </div>
            @if($exchange != 1)
                <div style="text-align:right">kurs: {{$exchange}}</div>
            @endif
            <div style="text-align:right;font-size:18px;padding-top:20px">Do {{($sum_brutto >= 0) ? 'zapłaty':'zwrotu'}}: {{Util::dkwota($sum_brutto)}} {{$currency}}</div>
            <div style="text-align:right">Słownie: <script>document.write(slowa("{{Util::dkwota($sum_brutto)}}","{{$currency}}"))</script></div>
        </div>

    </div>
</div> <!-- register end -->

<div class="bbr"></div>

