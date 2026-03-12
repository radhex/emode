<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
?>
<!DOCTYPE html>
<html lang="pl" class="no-js webkit safari chrome win">
    <head>
    <meta charset="utf-8">
<title>Emode</title>

<meta name="description" content="">
<meta name="title" content="You might want to sit down for this">
<meta name="author" content="Emode">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="/themes/rainlab-relax/assets/images/emode.png" />
<meta name="generator" content="Emode" />



<style>

.myalert, .flash {padding-top: 10px;color: #bb2424}
.green {color:green; }
.mtable {display: table; width:100%;border-radius:7px;}
.mtable > div {display: table-row; }
.mtable > div > div {display: table-cell;padding: 6px 10px 6px 10px;vertical-align:middle;font-size:15px;}
.mlist > div > div {border-bottom:1px solid #eee; }
.mlist h4 {padding-left:7px;}
.form-group-my {padding-bottom:15px;}
.noread a{font-weight:bold}


.mfield {border: 1px solid #eee;}
.mfield > div > div {vertical-align:top;font-size:14px;}
.lhead { color:#777; background:#f7f7f7;}
.fhead {color:#aaa; width:150px; margin-right:20px; background:#f5f5f5; solid #ccc;border-bottom: 1px solid #ccc;vertical-align:top;}
//.fhead::after { content: " :";}
.noborder {border:0px}

 .container {border:1px solid #ddd; border-radius: 7px; background: #fff ;padding: 0 20px 20px 20px}
 .info {color:#0181b9}

#layout-content   {margin-top:10px; border:1px; solid red;}

    
 .tabs  { margin-left:0px}
 .tabs  ul {width: 100%; float:right; padding-bottom:10px; margin-right:50px;}
 .tabs  ul  li  {list-style: none;float:left;margin-left: 10px;margin-bottom:5px; padding-left:12px;padding-top: 3px;padding-right: 12px;padding-bottom:3px; background: #eee; border-radius: 4px;}
 .tabs  ul  li.active  {background: #59a1d1;}
 .tabs  ul  li a {float:left; text-decoration:none; font-size:14px;padding:5px;color:#444}
 .tabs  ul  li.active a{ color:#fff}
 .tabs  .ui-tabs-active  {background: #59a1d1;}
 .tabs  .ui-tabs-active .ui-tabs-anchor  {color: #fff;}
 .tabs .ui-tabs-nav .ui-state-focus a {outline: none;}  

.userfile {width:500px}
.respo {margin:0 auto; width:500px; padding:20px;}
.loginrespo {margin:0 auto; width:300px;  padding:20px;}
//.restitle{width:400px}
             
.new-mail {display:none;vertical-align:middle;width: 26px;padding-top:12px;padding-right:0px;height: 26px;border-radius:14px;text-align: center;background-color: red;color:#fff;position: absolute;left: 2px;top:5px;}



@media (max-width: 550px) { .respo, .loginrespo{ width:100%} .userfile {width:100%}}
@media (max-width: 450px) {  .mtable > div > div.resdate{display:none};}

.tabs  ul  li:hover{-webkit-filter: brightness(95%);}; 



</style>
<link rel="icon" type="image/png" href="/modules/backend/assets/images/favicon.png">
<title data-title-template="">
   </title>
<link href="/modules/system/assets/ui/storm.css?v=1.2.3" rel="stylesheet">
<link href="/modules/backend/assets/css/winter.css?v=1.2.3" rel="stylesheet">
<link href="/modules/system/assets/ui/icons.css?v=1.2.3" rel="stylesheet">
<link href="/storage/cms/css/color.css?v=<?=filemtime('storage/cms/css/color.css') ?? ''; ?>" rel="stylesheet">


    <style>
        .form-group {position:static}

        .br-p{color:#34495e}.br-s{color:#e67e22}.br-a{color:#3498db}.br-p-s10{color:#2d4965}.br-s-s10{color:#f27d16}.br-a-s10{color:#289ae7}.br-p-s20{color:#25496d}.br-s-s20{color:#ff7c09}.br-a-s20{color:#1c9df3}.bg-p{background-color:#34495e}.bg-s{background-color:#e67e22}.bg-a{background-color:#3498db}.bg-p-s10{background-color:#2d4965}.bg-s-s10{background-color:#f27d16}.bg-a-s10{background-color:#289ae7}.bg-p-s20{background-color:#25496d}.bg-s-s20{background-color:#ff7c09}.bg-a-s20{background-color:#1c9df3}#layout-sidenav ul li a .nav-label,#layout-sidenav ul li a .nav-icon{text-shadow:0 -1px 0 rgba(16,22,28,0.6)}.sidenav-tree ul.top-level>li>div.group:before{border-top-color:#34495e}.sidenav-tree ul.top-level>li>ul li.active{border-color:#e67e22}body.outer{background:#293e53}table.table.data tbody tr.active td:first-child{border-left:3px solid #e67e22}.fancy-layout .form-tabless-fields{background:#e67e22}.fancy-layout .form-tabless-fields .loading-indicator-container .loading-indicator{background:#e67e22}.fancy-layout .control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li.active a>span.title,.fancy-layout.control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li.active a>span.title,.fancy-layout .control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li.active a>span.title:before,.fancy-layout.control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li.active a>span.title:before,.fancy-layout .control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li.active a>span.title:after,.fancy-layout.control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li.active a>span.title:after{background:#e67e22}.fancy-layout .control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li a>span.title,.fancy-layout.control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li a>span.title,.fancy-layout .control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li a>span.title:before,.fancy-layout.control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li a>span.title:before,.fancy-layout .control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li a>span.title:after,.fancy-layout.control-tabs.master-tabs>div>div.tabs-container>ul.nav-tabs>li a>span.title:after{background-color:#b05606}.fancy-layout .control-tabs.master-tabs>div>div.tabs-container,.fancy-layout.control-tabs.master-tabs>div>div.tabs-container{background-color:#bf5d07}.fancy-layout .control-tabs.primary-tabs>div>ul.nav-tabs.master-area,.fancy-layout.control-tabs.primary-tabs>div>ul.nav-tabs.master-area{background:#e67e22}.fancy-layout .control-tabs.secondary-tabs.secondary-content-tabs.primary-collapsed>div>ul.nav-tabs,.fancy-layout.control-tabs.secondary-tabs.secondary-content-tabs.primary-collapsed>div>ul.nav-tabs{background:#e67e22}.control-filelist ul li.active>a:after{background:#e67e22}div.control-componentlist.droppable{background-color:#f0b37e}.stripe-loading-indicator .stripe,.stripe-loading-indicator .stripe-loaded{background:#3498db}ul.pagination{list-style:none;float:left;vertical-align:middle;padding-top:3px}ul.pagination li{float:left;padding-left:2px}ul.pagination li a{float:left;color:#3a9bdc;text-decoration:none}ul.pagination li.active{color:#aaa}ul.pagination li.disabled{color:#aaa}.layout #layout-mainmenu.navbar{background-color:#333}   
        
             nav#layout-mainmenu.navbar-mode-inline, nav#layout-mainmenu.navbar-mode-inline_no_icons {height:49px}
             nav#layout-mainmenu .toolbar-item {margin-top:-6px}      
             .navbar {min-height:49px}      
            .layout > .my-size {height:80px}
            .layout > .fright {float:right}
            .layout #layout-mainmenu.navbar {background-color: #59a1d1;}
            //.layout #layout-mainmenu.navbar ul li a img {display:none}
            .layout #layout-mainmenu.navbar ul li a span {color:#fff;text-shadow:none;font-weight:normal }
            .layout #layout-mainmenu.navbar ul li a i {color:#fff;text-shadow:none;font-size: 2.5rem;display: inline-block;margin: 0;}
            .layout #layout-mainmenu.navbar ul li a {color:#fff;text-shadow:none;}
            .layout #layout-mainmenu.navbar ul li:hover a i,
            .layout #layout-mainmenu.navbar ul li:hover a span,
            .layout #layout-mainmenu.navbar ul li.active a i,
            .layout #layout-mainmenu.navbar ul li.active a span
              {color: #cde5f5}
            .layout #layout-mainmenu .menu-toggle     {color: #cde5f5}  
            .layout #layout-mainmenu.navbar ul li.active a i {color: #135987}
            .layout #layout-mainmenu.navbar ul li.active a span  {color: #fff}
            
             h3, h3 a {font-weight:normal; }
            .btn-primary{font-weight:normal;background:#59a1d1}
            .btn-primary:hover,.btn-default:hover {background:#458fc0}
            .btn-primary .badge{color:#59a1d1;background:#ffffff}
            
            .flash-message{position:fixed;width: 350px !important;left:50% !important;top:65px !important;margin-left:-175px !important; color:#ffffff;font-size:14px;text-shadow:none !important;box-shadow:none !important;-webkit-box-shadow:none !important}

            .flash-message.success{background: #edf8ff !important;border: 1px solid #59a1d1; color: #59a1d1}
            .flash-message.error{background:#fee5e5 !important;border: 1px solid red; color: red}
            .flash-message.warning{background:#ffecd2 !important;border: 1px solid #db8509; color: #db8509}
            .flash-message.info{background: #edf8ff !important;border: 1px solid #59a1d1; color: #59a1d1}
            .flash-message button{float:none;position:absolute;right:10px;top:8px;color:#777 !important;outline:none}
            .flash-message button:hover{color:white}
            .flash-message.static{position:static !important;width:auto !important;display:block !important;margin-left:0 !important;-webkit-box-shadow:none;box-shadow:none}

            .responsiv-uploader-fileupload.style-file-multi .upload-files-container .upload-object:hover{background:#59a1d1 !important}
            
            </style>
    </head>
    <body class="slim-container">
        <div id="layout-canvas">
            <div class="layout">

                <!-- Main Menu -->
                <div class="layout-row my-size">
                    <nav class="navbar control-toolbar navbar-mode-inline" id="layout-mainmenu" role="navigation">
    <div class="toolbar-item toolbar-primary">
        <div data-control="toolbar">


            <ul class="nav1 mainmenu-nav">
                                         
                         
             
                            </ul>
        </div>
    </div>
    <div class="toolbar-item" data-calculate-width>
    
             <ul class="mainmenu-toolbar" style="float:right;padding-right:10px;">

     
         <li class="mainmenu-account"><a href="http://emode.pl" class="oc-icon-copyright">Emode</a></li>
                            </ul>
    </div>
</nav>
                </div>

        <section id="layout-content" >  
            <div class="container" ><div class="text-center" style="margin-top:16px;height:400px;padding-top:70px">
     <h1> <i class="icon-exclamation-triangle" style="color:#59a1d1;font-size:75px" ondblclick="$('#desc').show()"></i></h1> <br>
    <h4><?= $_SESSION['error']?></h4>
    <p id="desc" style="display:none"><?= $desc ?></p>
    <p><a href="/"  class="btn btn-primary oc-icon-arrow-left" style="margin-top:20px">Powr&oacute;t</a></p>   
</div>
            </div>
        </section> 

            </div>
        </div>
        
             </body>
</html>
<?php unset($_SESSION['error']);?>