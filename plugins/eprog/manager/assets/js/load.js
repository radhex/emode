var modal;
var maxmodal = 0;
var cdate;
all = document.getElementById("eprog-load").getAttribute("all");
user = document.getElementById("eprog-load").getAttribute("user");
admin = document.getElementById("eprog-load").getAttribute("admin");
envelope = document.getElementById("eprog-load").getAttribute("envelope");
inbox = document.getElementById("eprog-load").getAttribute("inbox");
sent= document.getElementById("eprog-load").getAttribute("sent");
draft= document.getElementById("eprog-load").getAttribute("draft");
archive = document.getElementById("eprog-load").getAttribute("archive");
spam = document.getElementById("eprog-load").getAttribute("spam");
trash = document.getElementById("eprog-load").getAttribute("trash");
backend = document.getElementById("eprog-load").getAttribute("backend");

$(function() {


	if(all > 0) $('.icon-comments').append("<div class='new-mail'>" + all + "</div>");
	if(user > 0) $('.icon-comment-o').append("<div class='new-mail-side'>" + user + "</div>");
	if(admin > 0) $('.icon-comment').append("<div class='new-mail-side'>" + admin + "</div>");
	if(envelope > 0){
		$('#layout-mainmenu').find('.icon-envelope').append("<div class='new-mail'>" + envelope + "</div>");
		$('.mainmenu-collapsed').find('.icon-envelope').append("<div class='new-mail'>" + envelope + "</div>");
	}
/*
	if(inbox > 0) $('.icon-inbox').append("<div class='new-mail-side-first'>" + inbox + "</div>");

	if(sent > 0) $('.icon-paper-plane').append("<div class='new-mail-side'>" + sent + "</div>");
	if(draft > 0) $('.icon-pencil').append("<div class='new-mail-side'>" + draft + "</div>");
	if(archive > 0) $('.icon-archive').append("<div class='new-mail-side'>" + archive + "</div>");
	if(spam > 0) $('.icon-fire').append("<div class='new-mail-side'>" + spam + "</div>");
	if(trash > 0) $('.icon-trash').append("<div class='new-mail-side'>" + trash + "</div>");
*/

	$(".mainmenu-toolbar .mainmenu-account > a").addClass("oc-icon-user");
	$(".mainmenu-toolbar").append('<li class="mainmenu-account"><a href="http://emode.pl" class="oc-icon-copyright">Emode</a></li>');



	$("#Form-field-Invoice-user_id").change(function() {

		$.ajax({
                    type: "POST",
                    dataType: 'json',
                    url: "/" + backend + "/eprog/manager/feed/invoice",
                    data: "user_id=" + $(this).val() ,
                    success: function (data) {
                  
                       $("#Form-field-Invoice-buyer_name").val(data.name);
                       $("#Form-field-Invoice-buyer_nip").val(data.nip);
                       $("#Form-field-Invoice-buyer_adres1").val('ul. ' + data.street +  ' '  + data.number);
                       $("#Form-field-Invoice-buyer_adres2").val(data.code +  ' '  + data.city);
                       $("#Form-field-Invoice-buyer_country").val(data.country).trigger('change');

                    },
                    error:function(xhr,status,error){
              
                    }
                });
	});

	$("#Form-field-Order-user_id").change(function() {

			$.ajax({
	                type: "POST",
	                dataType: 'json',
	                url: "/" + backend + "/eprog/manager/feed/invoice",
	                data: "user_id=" + $(this).val() ,
	                success: function (data) { 
	                   $("#Form-field-Order-buyer_name").val(data.name);
	                   $("#Form-field-Order-buyer_nip").val(data.nip);
	                   $("#Form-field-Order-buyer_adres1").val('ul. ' + data.street +  ' '  + data.number);
	                   $("#Form-field-Order-buyer_adres2").val(data.code +  ' '  + data.city);
	                   $("#Form-field-Order-buyer_country").val(data.country).trigger('change');

	                },
	                error:function(xhr,status,error){
	          
	                }
	         });
	});

	$("#Form-field-Accounting-user_id").change(function() {

			$.ajax({
	                type: "POST",
	                dataType: 'json',
	                url: "/" + backend + "/eprog/manager/feed/invoice",
	                data: "user_id=" + $(this).val() ,
	                success: function (data) { 
	                   $("#Form-field-Accounting-client_name").val(data.name);
	                   $("#Form-field-Accounting-client_nip").val(data.nip);
	                   $("#Form-field-Accounting-client_adres1").val('ul. ' + data.street +  ' '  + data.number);
	                   $("#Form-field-Accounting-client_adres2").val(data.code +  ' '  + data.city);
	                   $("#Form-field-Accounting-client_country").val(data.country).trigger('change');

	                },
	                error:function(xhr,status,error){
	          
	                }
	         });
	});



	$("#Form-field-Product-brutto").val(number_format($("#Form-field-Product-brutto").val(),2,","," "));
	$("#Form-field-Product-netto").val(number_format($("#Form-field-Product-netto").val(),2,","," "));
	$("#Form-field-Product-vat").val(number_format($("#Form-field-Product-vat").val(),2,","," "));


	$("#Form-field-Product-brutto").keyup(function() { 

		vat = $("#Form-field-Product-vat_procent").val(); 
		vat  = !isNaN(vat) ? vat : 0;
		netto = Math.abs($(this).val().replace(",",".").replace(/\s+/g, '')/((100+Number(vat))/100)); 
		brutto = Math.abs($(this).val().replace(",",".").replace(/\s+/g, ''));
		vat = brutto - netto;
		$("#Form-field-Product-brutto").val(number_format(brutto.toString(),2,","," "));
		$("#Form-field-Product-netto").val(number_format(netto.toString(),2,","," "));
		$("#Form-field-Product-vat").val(number_format(vat.toString(),2,","," "));

	});

	$("#Form-field-Product-netto").keyup(function() { 

		vat = $("#Form-field-Product-vat_procent").val(); 
		vat  = !isNaN(vat) ? vat : 0;
		netto = Math.abs($(this).val().replace(",",".").replace(/\s+/g, ''));
		brutto = Math.abs($(this).val().replace(",",".").replace(/\s+/g, '')*((100+Number(vat))/100)); 
		vat = brutto - netto;
		$("#Form-field-Product-brutto").val(number_format(brutto.toString(),2,","," "));
		$("#Form-field-Product-netto").val(number_format(netto.toString(),2,","," "));
		$("#Form-field-Product-vat").val(number_format(vat.toString(),2,","," "));

	});

	$("#Form-field-Product-vat").keyup(function() { 

		vat = Math.abs(round($(this).val().replace(",",".").replace(/\s+/g, ''),2));
		netto = Math.abs(vat*100/$("#Form-field-Product-vat_procent").val().replace(",",".").replace(/\s+/g, ''));
		brutto = vat + netto;
		$("#Form-field-Product-brutto").val(number_format(brutto.toString(),2,","," "));
		$("#Form-field-Product-netto").val(number_format(netto.toString(),2,","," "));
		$("#Form-field-Product-vat").val(number_format(vat.toString(),2,","," "));

	});

	$("#Form-field-Product-vat_procent").change(function() {

		vat = $(this).val(); 
		vat  = !isNaN(vat) ? vat : 0;
		netto = Math.abs($("#Form-field-Product-brutto").val().replace(",",".").replace(/\s+/g, '')/((100+Number(vat))/100)); 
		brutto = Math.abs($("#Form-field-Product-brutto").val().replace(",",".").replace(/\s+/g, ''));
		vat = brutto - netto;
		$("#Form-field-Product-brutto").val(number_format(brutto.toString(),2,","," "));
		$("#Form-field-Product-netto").val(number_format(netto.toString(),2,","," "));
		$("#Form-field-Product-vat").val(number_format(vat.toString(),2,","," "));



	});


	$("#FileUpload-formDocument-document").find("button").removeClass("oc-icon-upload");
	$("#FileUpload-formDocument-document").find("button").addClass("oc-icon-file-text");
	$("#FileUpload-formImage-image").find("button").removeClass("oc-icon-upload");
	$("#FileUpload-formImage-image").find("button").addClass("oc-icon-camera");
	$("#FileUpload-formMedia-media").find("button").removeClass("oc-icon-upload");
	$("#FileUpload-formMedia-media").find("button").addClass("oc-icon-video-camera");
	
	$(document).on('click', '.sweet-alert .confirm', function () {
	    const $alert = $('.sweet-alert');
	    $alert.stop(true, true)
	          .removeClass('showSweetAlert')
	          .addClass('hideSweetAlert')
	          .css({
	              opacity: 0,
	              transform: 'scale(1)',
	          });
	    $('body').removeClass('loading');
	    $('.oc-loading').hide();
	});


	

	$('#Form-field-SettingConfig-tax_amount').on('paste input', function (e) {

		inputCurrency(this, e);

	});


	$('#Form-field-SettingConfig-tax_amount').on('focusout', function (e) {
	    
		inputCurrencyOut(this, e);

	});

	$('#Form-field-SettingConfig-tax_amount').on('focusin', function (e) {
	    
		inputCurrencyIn(this, e);

	});

	$('#Form-field-User-tax_amount').on('paste input', function (e) {

		inputCurrency(this, e);

	});


	$('#Form-field-User-tax_amount').on('focusout', function (e) {
	    
		inputCurrencyOut(this, e);

	});

	$('#Form-field-User-tax_amount').on('focusin', function (e) {
	    
		inputCurrencyIn(this, e);

	});


	$('.menu-toggle').on('click', function(e){
		var sidebar = $('.layout-sidenav-container');
		var currentDisplay = sidebar.css('display');
		var sidebarWidth = 0.5 * window.innerWidth - 80;

		if(currentDisplay !== 'none'){
			if($('.mainmenu-collapsed ').width() != 0){
				  sidebar.attr('style','display: none !important;');
				  $('.layout-container').attr('style','position:relative;left:0px');
			}
		}
		if(currentDisplay === 'none'){
		  sidebar.attr('style','display: block !important;');
		 $('.layout-container').attr('style','position:relative;left:-'  + sidebarWidth  + 'px');
		}
	  });

	$('.layout-sidenav-container').on('click', function(e){
		var sidebarWidth = $(this).width();
		if($(window).width() <= 768)
		$('.layout-container').attr('style','position:relative;left:'  + sidebarWidth  + 'px;width:100%')

	});

	$('.layout-container').on('click', function(e){
		if($(window).width() <= 768){
			var sidebar = $('.layout-sidenav-container');
	    	sidebar.attr('style','display: none !important;');
	    	$('.layout-container').attr('style','position:relative;left:0px');
		}
	});

	$(window).resize(function(){
		if($('.layout-sidenav-container').css('display') != 'none' && $('.layout-sidenav-container').css('display') != 'table-cell'){
			var sidebarWidth = $('.layout-sidenav-container').width();
			$('.layout-container').attr('style','position:relative;left:'  + sidebarWidth  + 'px;width:100%')
		}
	});
});






	$.maxZIndex = $.fn.maxZIndex = function(opt) {

    		var def = { inc: 10, group: "*" };
    		$.extend(def, opt);    
    		var zmax = 0;
    		$(def.group).each(function() {
        		var cur = parseInt($(this).css('z-index'));
        		zmax = cur > zmax ? cur : zmax;
    		});
    		if (!this.jquery)
        		return zmax;

    		return this.each(function() {
        		zmax += def.inc;
        		$(this).css("z-index", zmax);
    		});
	}


	function round(number,decPlace){

			return Math.round(number* Math.pow(10,decPlace))/Math.pow(10,decPlace);


	}

	function round0(value) {
	    let num = Number(value.toString().replace(/\s+/g,'').replace(',', '.'));
	    if (isNaN(num)) num = 0;
	    return Math.round(num);
	}

	function round2(value) {

	    let num = Number(value.toString().replace(/\s+/g,'').replace(',', '.'));
	    if (isNaN(num)) num = 0; 
	    return Math.round((num + Number.EPSILON) * 100) / 100;
	}


	function modal_img(el, title) {

			
			 var content = '<img id="myIframe" src="'+ $(el).attr("file") +'"  style="display:none;border:0px;margin:0px;padding:5px;padding-top:8px;"  onload="load_img(this)">';
			 modal_base(title, 300, 300, content); 

	}
	


	function load_img(el) {

			$(".loadmodal").hide();
			$(el).css("display","block");
			
			
        		$width = $(el).width();
			$height = $(el).height();
	
			window.modal.dialog("option", "width", "auto");
			window.modal.dialog("option", "height","auto");

	}

	function modal_url(url, title, width, height, table = null, reload = null) {
	
			var content = '<iframe id="myIframe" src="'+ url +'"  style="display:none;border:0px;margin:0 auto;padding:0px;width:100%;height:100%;background:#fff !important"  onload="load_url(this)"></iframe>';
			modal_base(title, width, height,content, true, true, table, reload); 
		
	}

	function modal_base(title, width, height, content, overlay = true, load = true, table = null, reload = null) {


			 window.maxmodal++;
			 zind = $.maxZIndex() + 1;
	
			 name = "modal" + window.maxmodal; 			
	
			 $('body').append('<div  id="' + name + '"  style="margin:0px;padding:0px;overflow:hidden;background:#fff"></div>');	
			 $(".span-left").css("z-index","1");
			 loader = '<div class="loadmodal loading-indicator-container" style="margin-left:20px"><div class="loading-indicator  size-small indicator-center" style="background-color: transparent;"><span  style="margin-top:' + ((height/2-65)) + 'px;"></span></div></div>';
			 if(!load) loader = '';
			 window.modal = $('#' + name).html(loader + content);			
 			 window.modal.dialog({
		
				title: title,
    				autoOpen: true,
    				modal: true,
				zIndex: 300,
    				width: width,
				height: height,

   				open: function(){ 
			
					$('.ui-widget-overlay').css("opacity","0").animate({ opacity: 0.3 });
            				$('.ui-widget-overlay').bind('click',function(){ 
                				 //window.modal.dialog('close'); 
            	        		}) 
					if(!overlay) $('.ui-widget-overlay').remove();

				},
				close: function(ev, ui){

					window.maxmodal--;			
					if(window.maxmodal == 0) $(".span-left").css("z-index","200");
					if(table != null) ajax_update(table);
					if(title == "Import XML" || reload == true) window.location.href = window.location.href;
					if(table == "taxfile") taxfile();

     		 	}
			});


	}

	function load_url(el) {

			$(".loadmodal").hide();
			$(el).css("display","block");	
			$(el).contents().find('#layout-mainmenu').hide(); 
			$(el).contents().find('#layout-sidenav').hide();   
			$(el).contents().find('.layout-sidenav-container').hide();   
			$(el).contents().find('.layout-cell.w-120').hide();     
			$(el).contents().find('.oc-icon-file-code-o').hide();    
			 	

	}


	function ajax_update(table) {

			$.ajax({
			    type: "POST",
			    dataType: 'json',
			    url: "" + backend + "/eprog/manager/feed/ajaxupdate?table=" + table,
			    success: function (data) { 

			    	$("#Form-field-" + table).html(data.res);

			    },
			    error:function(xhr,status,error){

			    }
			});
	}


	function beforeClick(el){

		event.preventDefault();

	    href = $(el).attr("href");    
	    term = $("input[name='listToolbarSearch[term]']").val();

	    if(term.length > 0) 
	    window.location.href = href + '?term=' + term;
		else
		window.location.href = href;

	}

	function paginateUrl(page, pageToken = null){

		href = window.location.href;
		term = $("input[name='listToolbarSearch[term]']").val();
		const url = new URL(href);

		url.searchParams.set('page', page);
		//if(pageToken)
		//url.searchParams.set('pageToken', pageToken);

		if(term.length > 0)
			url.searchParams.set('term', term);

		const nextURL = url.toString();
		const nextTitle = 'Page' + page;
		const nextState = { additionalInformation: 'Updated the URL with JS' };
		window.history.pushState(nextState, nextTitle, nextURL);
		window.history.replaceState(nextState, nextTitle, nextURL);
	
	}

	function completeUrl(el){

		clearUrl();
		if(el.value == "")
		homeUrl();
		
	}

	function clearUrl(){

		href = window.location.href;
		const url = new URL(href);

		url.searchParams.delete('page');
		url.searchParams.delete('term');
		
		const nextURL = url.toString();
		const nextTitle = 'Clear';
		const nextState = { additionalInformation: 'Updated the URL with JS' };
		window.history.pushState(nextState, nextTitle, nextURL);
		window.history.replaceState(nextState, nextTitle, nextURL);
		
	}


	function searchUrl(el){

		$("#search-info").css("display","inline-block");
		$("#search-text").text($("#search-text").attr("label") + " '" + el.value + "'");

		href = window.location.href;
		const url = new URL(href);

		url.searchParams.delete('page');

		if(el.value.length > 0)
		url.searchParams.set('term', el.value);

		const nextURL = url.toString();
		const nextTitle = 'Search' + el.value;
		const nextState = { additionalInformation: 'Updated the URL with JS' };
		window.history.pushState(nextState, nextTitle, nextURL);
		window.history.replaceState(nextState, nextTitle, nextURL);

		if($(".control-list tr").size() -1 < 100) $('.nextPage').hide();

		
	
	}

	function homeUrl(){

		window.location.href = window.location.href;

	}


	function updateInbox(){

/*
		$.ajax({
	            type: "POST",
	            dataType: 'json',
	            url: "" + backend + "/eprog/manager/feed/inbox",
	            success: function (data) { 
	            	if(data > 0) 
	            		$('nav#layout-mainmenu.navbar-mode-inline ul.mainmenu-nav li a .nav-icon > .icon-envelope').append("<div class='new-mail'>" + data + "</div>");
	            	else
	            		$('nav#layout-mainmenu.navbar-mode-inline ul.mainmenu-nav li a .nav-icon > .icon-envelope > .new-mail').hide();

	            },
	            error:function(xhr,status,error){
	      
	            }
         });
*/
	}

	function bodyHeight(iframeElement){


		//iframeElement.src = 'data:text/html;charset=utf-8,' + decodeURI(value);

	    //$(iframeElement).width(the_height+10);
	    if(iframeElement)
	    $(iframeElement).height(iframeElement.contentWindow.document.body.scrollHeight+10);



	}

	function clickMail(el,email){
	
		$(el).click(function(e) {
		  		window.location.href='/' + backend + '/eprog/manager/inbox/create?action=to&email='+ email;
		   		e.stopPropagation();
		});
		
	}

	function twoDigits(n){

	    return n > 9 ? "" + n: "0" + n;

	}

	function addScheduler(){
	
		title = "Nowy";
		date = window.cdate;
		var eventData;
		var eventSource;
		var start = date.start.getFullYear() + '-' + twoDigits(date.start.getMonth()+1) + '-' + twoDigits(date.start.getDate()) + '00:00:00'; 
		var end = date.end.getFullYear() + '-' + twoDigits(date.end.getMonth()+1) + '-' + twoDigits(date.end.getDate()) + '00:00:00'; 

		if (title) {
			eventSource = {
				id: 'newEvent',
				url:  '/' + backend + '/eprog/manager/feed/save?title='+ title + '&start_=' + start + '&end_=' + end + '&user='  + $('#user').val() + '&admin='  + $('#admin').val()  + '&category='  + $('#category').val()
			};
			
			window.calendar.addEventSource(eventSource);					
			var eventSources = calendar.getEventSources(); 
			eventSources[eventSources.length-1].remove(); 
			window.calendar.refetchEvents();
				
		}
		window.calendar.unselect()
	
	}

	function actionDrive(el, action, id = null, confirm = null, filename = null, folder = null, name = null){
	
		if(action == "trash"){

			$(el).click(function(e) {

				$(this).request('onTrash', {
	    			confirm: confirm,
	    			data: {id:id}
				})

			   	e.stopPropagation();
			});
			
		}

		if(action == "download"){

			$(el).click(function(e) {

				$(this).request('onDownload', {
					confirm: confirm,
					data: {id:id}
				})

			   	e.stopPropagation();
			});
			
		}

		if(action == "import"){

			//modal_url("/" + backend + "/eprog/manager/feed/drive_import?id=" + id,"", 400, 250);
			const now = new Date();
			const month = String(now.getMonth() + 1).padStart(2, '0'); // miesiące 0–11
			const year = now.getFullYear();

			const currentDate = month + '_' + year;

			modal_url('/' + backend + '/eprog/manager/feed/savefile/drive/' + id + '/' + currentDate + '/getFile',name, 450, 300);


		}

		if(action == "rename"){
			modal_url("/" + backend + "/eprog/manager/feed/drive_rename?id=" + id + "&filename=" + filename + "&folder=" + folder + "&name=" + name, filename, 400, 250);
		}

		if(action == "copy"){
			modal_url("/" + backend + "/eprog/manager/feed/drive_copy?id=" + id + "&filename=" + filename + "&folder=" + folder + "&name=" + name, filename, 400, 250);
		}

		if(action == "move"){
			modal_url("/" + backend + "/eprog/manager/feed/drive_move?id=" + id + "&filename=" + filename + "&folder=" + folder + "&name=" + name, filename, 500, 500);
		}

		if(action == "delete"){

			$(el).click(function(e) {

				$(this).request('onDelete', {
	    			confirm: confirm,
	    			data: {id:id}
				})

			   	e.stopPropagation();
			});
			
		}


		if(action == "restore"){

			$(el).click(function(e) {

				$(this).request('onRestore', {
	    			confirm: confirm,
	    			data: {id:id}
				})

			   	e.stopPropagation();
			});
			
		}


		if(action == "logout"){

			$(el).click(function(e) {

				$(this).request('onLogout', {
	    			confirm: confirm,
				})

				e.stopPropagation();
			});
			
		}
		/*

					special_netto = $("#Form-field-Product-special_brutto").val()/((100+Number(vat))/100); 
					special_brutto = $("#Form-field-Product-special_brutto").val();
					vat = brutto - netto;
					$("#Form-field-Product-special_brutto").val(round(special_brutto,2));
					*/
		
	}




	function isInt(value) {

		return isIntBase(value) && value > 0;
		
	}

	function isFloat(val) {
	    var floatRegex = /^-?\d+(?:[.,]\d*?)?$/;
	    if (!floatRegex.test(val))
	        return false;

	    val = parseFloat(val);
	    if (isNaN(val))
	        return false;
	    return true;
	}


	function isFloatMod(val) {
		var floatRegex = /^-?\d*(?:[.,]\d*)?$/; // /^-?([0-9]*)([.,][0-9]*)?$/
	    if (!floatRegex.test(val))
	        return false;

	    //val = parseFloat(val);
	    //if (isNaN(val))
	        //return false;
	    return true;

	}


	function isNumber(value) {

		return isIntBase(value) && value >= 0;
		
	}

	function isIntBase(value) {

		return (parseFloat(value) == parseInt(value)) && !isNaN(value)  && value.toString().indexOf(',') === -1 && value.toString().indexOf('.') === -1;
		
	}

	function isNumeric(x) {

	
	    if(typeof x == 'number' && !isNaN(x)){
	    
	        // check if it is integer
	        if (Number.isInteger(x)) {
	            //console.log(`${x} is integer.`);
	        }
	        else {
	            //console.log(`${x} is a float value.`);
	        }
	    
	    } else {
	        //console.log(`${x} is not a number`);
	    }
	}


	function  isCurrency(value){

		value = value.replace("-",""); 
		var regex  = /^[0-9\.]+$/; 
		if (regex.test(value) && value >= 0) return true; else return false;
			
	} 

	function  isTwoDecimalNumeric(value){
	
		value = value.replace("-",""); 
		var regex  = /^\d+(?:\.\d{0,2})$/; 
		if (regex.test(value)) return true; else return false;
			
	} 

	function  isGtu(value){

		if(value.length == 0) return true;

		value = value.replace("_"," ");
		value = value.split(" "); 
		
		if(value[0] == "GTU" && value[1] > 0 && value[1] < 14) 
			return true;
		else
			return false;

	}

	function  inputCurrency(el, e){
	
		val = $(el).val().replace(",",".").replace(/\s+/g, '');
		if(!isFloat(val)  || val < 0 || e.originalEvent.data == "-")
			$(el).val(stringDifference(e.originalEvent.data,$(el).val()));
		

	}

	function inputCurrencyMod(el, e){

		val = $(el).val().replace(",",".").replace(/\s+/g,'');
		if(!isFloat(val) && val !== "-" && val !== "-0")
		    $(el).val(stringDifference(e.originalEvent.data,$(el).val()));
		

	}

	function  inputCurrencyIn(el, e){

		if($(el).val().trim() != "")
			$(el).val(number_format($(el).val().replace(",",".").replace(/\s+/g, ''),2,",",""));


	}

	function  inputCurrencyOut(el, e){

		 if($(el).val().trim() != "")
			$(el).val(number_format($(el).val().replace(",",".").replace(/\s+/g, ''),2,","," "));

	}

	function inputCurrencyNum(el, e){

		val = $(el).val().replace(/\s+/g,'');
		if(!/^-?\d*$/.test(val))
		    $(el).val(val.replace(/[^0-9-]/g,''));
		

	}

	function  inputCurrencyNumIn(el, e){

		if($(el).val().trim() != "")
			$(el).val($(el).val().replace(/\s+/g,'').replace(/[^0-9-]/g,''));


	}

	function  inputCurrencyNumOut(el, e){

		 if($(el).val().trim() != "")
			$(el).val($(el).val().replace(/\s+/g,'').replace(/[^0-9-]/g,'').replace(/\B(?=(\d{3})+(?!\d))/g," "));

	}

	function  inputCurrencyZero(el, e){


		if(!isFloat($(el).val())  || $(el).val() < 0 || e.originalEvent.data == "-")
			$(el).val(stringDifference(e.originalEvent.data,$(el).val()));
		

	}

	function  inputCurrencyZeroIn(el, e){

		
			$(el).val(number_format($(el).val().replace(",",".").replace(/\s+/g, ''),2,",",""));


	}

	function  inputCurrencyZeroOut(el, e){

	
			$(el).val(number_format($(el).val().replace(",",".").replace(/\s+/g, ''),2,","," "));

	}

	function  inputCourse(el, e){

		if(!isFloat($(el).val())  || $(el).val() < 0)
			$(el).val(stringDifference(e.originalEvent.data,$(el).val()));

	}

	function  inputCourseIn(el, e){

		if($(el).val().trim() != "")
			$(el).val(number_format($(el).val().replace(",",".").replace(/\s+/g, ''),4,".",""));


	}

	function  inputCourseOut(el, e){

		//count = $(el).val().replace(",",".").split(".")[0].length;
		if($(el).val().trim() != "")
			$(el).val(number_format($(el).val().replace(",",".").replace(/\s+/g, ''),4,".",""));
		//else
			//$(el).val("1.0000");
		//$(el).val(number_format($(el).val().replace(",",".")/Math.pow(10,count-3),4,"."," "));

	}

	function  inputLump(el, e){

		if(!isFloat($(el).val())  || $(el).val() < 0)
			$(el).val(stringDifference(e.originalEvent.data,$(el).val()));

	}

	function  inputLumpIn(el, e){

		if($(el).val().trim() != "")
			$(el).val(number_format($(el).val().replace(",",".").replace(/\s+/g, ''),1,".",""));


	}

	function  inputLumpOut(el, e){


		if($(el).val().trim() != "")
			$(el).val(number_format($(el).val().replace(",",".").replace(/\s+/g, ''),1,".",""));


	}


	function number_format (number, decimals, dec_point, thousands_sep) {  


 		number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
	        var n = !isFinite(+number) ? 0 : +number,  prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),  sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,  dec = (typeof dec_point === 'undefined') ? '.' : dec_point,  s = '',  toFixedFix = function (n, prec) {  var k = Math.pow(10, prec);  return '' + Math.round(n * k) / k; 

 	         };
		  // Fix for IE parseFloat(0.55).toFixed(0) = 0; 
	         s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                  if (s[0].length > 3) {  s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);  } 
		  if ((s[1] || '').length < prec) {  s[1] = s[1] || '';  s[1] += new Array(prec - s[1].length + 1).join('0');  } 
		 return s.join(dec); 

    }  


    function stringDifference(a, b)
    {

    	if(!a) return b;
        var i = 0;
        var j = 0;
        var result = "";

        while (j < b.length)
        {
         if (a[i] != b[j] || i == a.length)
             result += b[j];
         else
             i++;
         j++;
        }
        return result;
    }

    function countMode(){

	    if($('input[name="count"]:checked').val() == "netto"){
	    	$(".brutto").attr("readonly", true);
	    	$(".brutto").css("background-color","#eee !important");
	    	$(".brutto").css("color","#999");
	    	$(".brutto").css("cursor","not-allowed");

	    	$(".netto").attr("readonly", false);
	    	$(".netto").css("background-color","#fff !important");
	    	$(".netto").css("color","#222");
	    	$(".netto").css("cursor","default");
	    }
	    else{
	    	$(".netto").attr("readonly", true);
	    	$(".netto").css("background-color","#eee !important");
	    	$(".netto").css("color","#999");
	    	$(".netto").css("cursor","not-allowed");

	    	$(".brutto").attr("readonly", false);
	    	$(".brutto").css("background-color","#fff !important");
	    	$(".brutto").css("color","#222");
	    	$(".brutto").css("cursor","default");
	    }

	    if($('input[name="count"]:checked').val() == "netto"){
	    	$(".rbrutto").attr("readonly", true);
	    	$(".rbrutto").css("background-color","#eee !important");
	    	$(".rbrutto").css("color","#999");
	    	$(".rbrutto").css("cursor","not-allowed");

	    	$(".rnetto").attr("readonly", false);
	    	$(".rnetto").css("background-color","#fff !important");
	    	$(".rnetto").css("color","#222");
	    	$(".rnetto").css("cursor","default");
	    }
	    else{
	    	$(".rnetto").attr("readonly", true);
	    	$(".rnetto").css("background-color","#eee !important");
	    	$(".rnetto").css("color","#999");
	    	$(".rnetto").css("cursor","not-allowed");

	    	$(".rbrutto").attr("readonly", false);
	    	$(".rbrutto").css("background-color","#fff !important");
	    	$(".rbrutto").css("color","#222");
	    	$(".rbrutto").css("cursor","default");
	    }
	}

    function protectFromEdit(){

        $(".delRow").hide();
        $(".addRow").hide();
        $(".btnadd").hide();
        $("#nbp").hide();
        $("#nbpu").hide();
        $("#useradd").hide();
        $("#scedig").hide();
        $("#bcedig").hide();
        $("#acedig").hide();

        $('#DatePicker-formCreateAt-date-create_at').prop('disabled', true);
        $('#DatePicker-formMakeAt-date-make_at').prop('disabled', true);
        $('#DatePicker-formPayTermin-date-_pay_termin').prop('disabled', true);
        $('#DatePicker-formPayDate-date-_pay_date').prop('disabled', true);
        $('#DatePicker-formUmoDate-date-_umo_date').prop('disabled', true);
        $('#DatePicker-formZamDate-date-_zam_date').prop('disabled', true)



        $("input").each(function(index) {

            $(this).attr("readonly", true);

            $(this).css("background-color","var(--mlcolor) !important");
            $(this).css("color","#999");
            $(this).css("cursor","not-allowed");


        });

        $("input[type='radio']").prop('disabled', true);


        $("input[type='checkbox']").each(function(index) {

        	if($(this).attr('name') != "Invoice[disp]"){
	            $(this).click(function(event) {
	                event.preventDefault();
	            });
        	}
     
        });

        $("textarea").each(function(index) {

            $(this).attr("readonly", true);

            $(this).css("background-color","var(--mlcolor) !important");
            $(this).css("color","#999");
            $(this).css("cursor","not-allowed");


        });

        $(".select2-selection").attr("style","background-color:var(--mlcolor) !important;cursor:not-allowed !important");

        
        $("select").each(function(index) {

            $(this).attr("readonly", true);

            $(this).on('select2:opening', function (e) {
                if( $(this).attr('readonly') == 'readonly') { 
                    e.preventDefault();
                    $(this).select2('close'); 
                    return false;
                }
            });

            $(this).on('mousedown', function(e) {
               e.preventDefault();
               this.blur();
               window.focus();
            });

            $(this).css("background-color","var(--mlcolor) !important");
            $(this).css("color","#999");
            $(this).css("cursor","not-allowed");


        });
    }

    function replaceParam(url, param, value){

	    var href = new URL(url);
	    if(Array.isArray(param)){
	    	for (let index = 0; index < param.length; ++index){ 
	    		href.searchParams.set(param[index], value[index]);
	    	}
	    }
	    else	
	    	href.searchParams.set(param, value);
	    return href.toString();

	}

    function deleteParam(url, param){

	    var href = new URL(url);
	    if(Array.isArray(param)){
	    	for (let index = 0; index < param.length; ++index){ 
	    		href.searchParams.delete(param[index]);
	    	}
	    }
	    else	
	    	href.searchParams.set(param, value);
	    return href.toString();

	}

