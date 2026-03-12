<?php


use Eprog\Manager\Models\Invoice;
use Eprog\Manager\Models\Invoicevalue;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Models\SettingConfig as Settings;
use Backend\Facades\BackendAuth;
use Rainlab\User\Facades\Auth;
use RainLab\Translate\Classes\Translator;


	$auth = Auth::getUser();

	$input = Invoice::where("id",">",0);
	if($auth && $auth->id > 2) $input = $input->where("user_id","=", Auth::getUser()->id);
	$input = $input->where("id", "=", $id)->get();
 	if(!isset($input[0])) die(); else $input = $input[0];
  
  $invoice = Invoice::find($id);
	$value = Invoicevalue::where("invoice_id","=",$id)->get();
  $currency = $invoice->currency ?? $currency;
	$currency = Settings::get("currency") == null ? "PLN" : Settings::get("currency");
	$exchange = 1;

    	$translator = Translator::instance();
    	$translator->setLocale(Session::get("locale"));


    function policz($l,$t1,$t2,$t3) {

      $j = array("", "jeden ", "dwa ", "trzy ", "cztery ", "pięć ", "sześć ",
        "siedem ", "osiem ", "dziewięć ", "dziesięć ", "jedenaście ",
        "dwanaście ", "trzynaście ", "czternaście ", "piętnaście ",
        "szesnaście ", "siedemnaście ", "osiemnaście ", "dziewiętnaście ");
      $d = array("", "", "dwadzieścia ", "trzydzieści ", "czterdzieści ",
        "pięćdziesiąt ", "sześćdziesiąt ", "siedemdziesiąt ",
        "osiemdziesiąt ", "dziewięćdziesiąt ");
      $s = array("","sto ", "dwieście ", "trzysta ", "czterysta ", "pięćset ",
        "sześćset ", "siedemset ", "osiemset ", "dziewięćset ");

      $txt = $s[0+substr($l,0,1)];
      if (substr($l,1,2)<20) $txt .= $j[0+substr($l,1,2)];
      else $txt .= $d[0+substr($l, 1,1)].$j[0+substr($l, 2,1)];
      if ($l==1) $txt .= "$t1 "; else {
        if ((substr($l,2,1)==2 or substr($l,2,1)==3 or substr($l,2,1)==4)
        and (substr($l,1,2)>20 or substr($l,1,2)<10))
        $txt .= "$t2 "; else $txt .= "$t3 ";
      }

      return $txt;
    }

    function slownie($liczba) {

      $liczba = str_replace(",", ".", $liczba);
      $liczba = number_format($liczba, 2, ",", "");
      $kwota=explode(",", $liczba);
      $kwotazl=sprintf("%012d",$kwota[0]);
      $kwotagr=sprintf("%03d",$kwota[1]);
    	$txt = "";
      if ($kwotazl>999999999) $txt .= policz(substr($kwotazl, 0,3),"miliard","miliardy","miliardów");
      if ($kwotazl>999999) $txt .= policz(substr($kwotazl, 3,3),"milion","miliony","milionów");
      if ($kwotazl>999) $txt .= policz(substr($kwotazl, 6,3),"tysiąc","tysiące","tysięcy");
      if ($kwotazl>0) $txt .= policz(substr($kwotazl, 9,3),"złoty","złote","złotych");
      if ($kwotazl==0) $txt="zero złotych";
      $txt .= " ";

      if ($kwotagr==0) $txt .= "zero groszy";
      else
      $txt .= policz($kwotagr,"grosz","grosze","groszy");

      return $txt;

    }


?>
<title data-title-template="%s | Emode"><?= e(trans('eprog.manager::lang.print'))?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style>

     div{font-family: DejaVu Sans !important;}
     .invoicepdf .product td{font-family: DejaVu Sans !important}

    .invoicepdf {font-size:14px;font-family:Tahoma;color:#333}
    .invoicepdf div {padding-top:4px;padding-bottom:4px}
    .invoicepdf .seller {float:left}
    .invoicepdf .head {font-size:16px; white-space: nowrap;}
    .invoicepdf .product {width:100%; border-collapse: collapse;border-spacing: 0;}
    .invoicepdf .product td{padding-left:5px;padding-right:5px;padding-bottom:4px;font-size:12px;border-left:1px solid #333;border-top:1px solid #333;text-align:right}
    .invoicepdf .product .row{}
    .invoicepdf .product td.noborder {border:0}
    .invoicepdf .product td.noleft {border-left:0px}
    .invoicepdf .product td.right {border-right:1px solid #333}
    .invoicepdf .product td.bottom {border-bottom:1px solid #333}
    .invoicepdf .product .margintop {padding-top:10px}
    .invoicepdf .product .nomargintop {padding-top:0px}
    .invoicepdf .product .aleft {text-align:left}
    .invoicepdf .product .acenter {text-align:center}
    .desc {font-size:12px;font-family:Tahoma;color:#333;margin-top:40px;line-height:10px}
    .footer {float: right;position: fixed;bottom: 0;font-size:14px;font-family:Tahoma;color:#333}
    .adress div {height:17px;}	
	
</style>

<body>
<div id="register" >
    <div class="margin-register">

        <div class="invoicepdf">
            <div style="text-align:right"><?= $input->place ?>: <?= Util::dateFormat($input->place_at,"d-m-Y") ?><?= Util::timeZoneOffset(); ?></div>
            <div style="text-align:right"><?= e(trans("eprog.manager::lang.make_at")) ?>: <?= Util::dateFormat($input->make_at,"d-m-Y") ?><?= Util::timeZoneOffset(); ?></div>
            <?php $faktura = [trans("eprog.manager::lang.vat"),trans("eprog.manager::lang.proforma"),trans("eprog.manager::lang.margin")]; if($input->type == "") $input->type = 0;?>
            <div style="text-align:center;font-size:22px;padding-top:10px"><?= e(trans("eprog.manager::lang.invoice_one")) ?> <?= $faktura[$input->type] ?> <?php if($input->correct > 0) : ?> <?= lcfirst(e(trans("eprog.manager::lang.correct"))) ?> <?php endif ?> <?= lcfirst(e(trans("eprog.manager::lang.nr_short"))) ?> <?= $input->nr ?></div>
            <?php if($input->correct > 0)  : ?><div style="text-align:center;font-size:18px;padding-top:0px"><?= lcfirst(e(trans("eprog.manager::lang.to"))) ?> <?= lcfirst(e(trans("eprog.manager::lang.invoice"))) ?> <?= lcfirst(e(trans("eprog.manager::lang.nr_short"))) ?> <?= Invoice::find($input->correct)->nr ?></div><?php endif ?>
            <?php $rodzaj = [trans("eprog.manager::lang.original"),trans("eprog.manager::lang.copy"),trans("eprog.manager::lang.duplicate"),trans("eprog.manager::lang.none")]; if($input->mode == "") $input->mode = 0;?>
            <div style="text-align:center;"><?= $rodzaj[$input->mode] ?></div>

	   
            <div class="adress" style="float:left;padding-right:40px;padding-top:20px;">
              <div class="head"><?= e(trans("eprog.manager::lang.buyer_data")) ?></div>
                <div><?= $input->firm_name ?></div>
                <div>NIP: <?= $input->firm_nip ?></div>
                <div>ul. <?= $input->firm_street ?> <?= $input->firm_number ?></div>
                <div><?= $input->firm_code ?> <?= $input->firm_city ?></div>
            </div>
            <div class="adress" style="padding-top:20px;">
                <div class="head"><?= e(trans("eprog.manager::lang.seller_data")) ?></div>
                <div><?= $input->name ?></div>
                <div>NIP: <?= $input->nip ?></div>
                <div>ul. <?= $input->street ?> <?= $input->number ?></div>
                <div><?= $input->code ?> <?= $input->city ?></div>
            </div>
	   

            <div style="padding-top:20px;"><?= e(trans("eprog.manager::lang.paytime")) ?>: <?= $input->paytime ?> <?= lcfirst(e(trans("eprog.manager::lang.days"))) ?></div>
            <?php $faktura = [strtolower(trans("eprog.manager::lang.transfer")),strtolower(trans("eprog.manager::lang.cash"))]; if($input->paytype == "") $input->paytype = 1;?>
            <div><?= e(trans("eprog.manager::lang.paytype")) ?>: <?= $faktura[$input->paytype] ?></div>
            <div style="padding-bottom:20px"><?= e(trans("eprog.manager::lang.note")) ?>: <?= $input->note ?></div>
            <table class="product">
                <tr class="row head" style="background:#f1f1f1"><td style="width:30px;text-align:center">lp.</td><td style="width:40%;text-align:left"><?= lcfirst(e(trans("eprog.manager::lang.name"))) ?></td> <td><?= lcfirst(e(trans("eprog.manager::lang.quantity"))) ?></td><td><?= lcfirst(e(trans("eprog.manager::lang.measure"))) ?></td><td><?= lcfirst(e(trans("eprog.manager::lang.net_price"))) ?><br><?= $currency ?></td><td><?= e(trans("eprog.manager::lang.vat")) ?></td><td><?= lcfirst(e(trans("eprog.manager::lang.net_value"))) ?><br><?= $currency ?></td><td><?= lcfirst( e(trans("eprog.manager::lang.vat_value"))) ?><br><?= $currency ?></td><td class="right"><?= lcfirst(e(trans("eprog.manager::lang.gross_value"))) ?><br><?= $currency ?></td></tr>
                <?php

                $sumbrutto = 0;
                $sumvat = 0;
                $sumnetto = 0;
		$l = 0;	

                ?>
           
                <?php foreach($value as $val) :?>
                    <?php

                        $label_vat = $val->vat;
                        $val->vat = explode("%", $val->vat)[0];$val->vat = is_numeric($val->vat) ? $val->vat : 0;
                  			$netto = $val->netto*$val->quantity;
                  			$brutto = $val->netto*$val->quantity*((100+$val->vat)/100);
                  			$vat = $brutto - $netto;
                  			$sumnetto += $netto;
                  			$sumbrutto += $brutto;
                  			$sumvat += $vat;
                  			$l++;

                    ?>
                    <tr><td class="acenter"><?= $l ?></td><td class="aleft"><?= $val->product ?></td> <td><?= $val->quantity ?></td><td><?= $val->measure ?></td><td><?= Util::currency($val->netto) ?></td><td><?= $label_vat ?></td><td><?= Util::currency($netto) ?></td><td><?= Util::currency($vat) ?></td><td class="right"><?= Util::currency($brutto) ?></td></tr>
                <?php endforeach ?>
		
                <tr class="row"><td class="noleft"></td> <td class="noleft"></td><td class="noleft"></td><td class="noleft"></td><td class="noleft"></td><td class="noleft"><?= e(trans("eprog.manager::lang.summary")) ?></td><td  class="bottom"><?= Util::currency($sumnetto) ?></td><td class="bottom"><?= Util::currency($sumvat) ?></td><td  class="right bottom"><?= Util::currency($sumbrutto) ?></td></tr>

                <tr class="row"><td class="noborder"></td> <td class="noborder"></td><td class="noborder"></td><td class="noborder"></td><td class="noborder"></td><td class="noborder"></td><td class="noborder"><?= lcfirst(e(trans("eprog.manager::lang.net_sum"))) ?><br><?= $currency ?></td><td class="noborder"><?= lcfirst(e(trans("eprog.manager::lang.vat_sum")))?><br><?= $currency ?></td><td class="noborder"><?=lcfirst(e(trans("eprog.manager::lang.gross_sum")))?><br><?= $currency ?></td></tr>
                <tr class="row"><td class="noborder"></td> <td class="noborder"></td><td class="noborder"></td><td class="noborder"></td><td class="noborder"></td><td class="noborder"></td><td class="noborder"><?= Util::currency($sumnetto) ?></td><td class="noborder"><?= Util::currency($sumvat) ?></td><td class="noborder"><?= Util::currency($sumbrutto) ?></td></tr>


            </table>
            <?php if($exchange != 1)  : ?>
                <div style="text-align:right"><?= e(trans("eprog.manager::lang.exchange")) ?>: <?= $exchange ?></div>
            <?php endif ?>
            <div style="text-align:right;font-size:18px;padding-top:20px"><?= e(trans("eprog.manager::lang.to")) ?> <?= ($sumbrutto >= 0) ? lcfirst(e(trans("eprog.manager::lang.topay"))) : lcfirst(e(trans("eprog.manager::lang.toreturn")))  ?>: <?= Util::currency($sumbrutto) ?> <?= $currency ?></div>
            <div style="text-align:right"><?= e(trans("eprog.manager::lang.words")) ?>: <?php echo slownie($sumbrutto); ?></div>
        </div>

    </div>
    <div class="desc"><?= $input->desc ?></div>
</div> <!-- end -->

<div class="bbr"></div>
<div class="footer">http://emode.pl<div>
</body>

