$(function() {
	$(".infoviewbox-content .field-container").each(function() {
		if($(this).children().length<=0) {
			refid=$(this).closest(".tab-pane").attr("id");
			$(".infoviewContainer .nav.nav-tabs a[href='#"+refid+"']").parent().hide();
		}
	});

  $("*[cmd]",".infoviewContainer").click(function(e) {
    cmd=$(this).attr("cmd");
		cmdOriginalX=cmd;
    cmd=cmd.split("@");
		cmd=cmd[0];
		src=this;
		
		parentObject=$(src).closest(".infoTableView");
		if(parentObject.length<=0) {
			parentObject=$(src).closest(".infoviewContainer");
		}
		hash=parentObject.data('dcode');
		gkey=parentObject.data('dtuid');
		title=$(src).text();
		if(title==null || title.length<=0) {
			title=$(src).attr("title");
		}
		if(title==null || title.length<=0) {
			title="Dialog";
		}
		
		switch(cmd) {
        case "forms":case "reports":case "infoview":
          cmdX=cmdOriginalX.split("@");
          if(cmdX[1]!=null) {
            cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{gkey}",gkey);

						lgksOverlayFrame(_link("modules/"+cmd+"/"+cmdX[1]),title,function() {
	                hideLoader();
	              });
          }
        break;
        case "page":
          cmdX=cmdOriginalX.split("@");
          if(cmdX[1]!=null) {
            cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{gkey}",gkey);
            window.location=_link("modules/"+cmdX[1]);
          }
          break;
        case "module":case "popup":
          cmdX=cmdOriginalX.split("@");
          if(cmdX[1]!=null) {
            cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{gkey}",gkey);

            if(cmd=="module" || cmd=="modules") {
              top.openLinkFrame(title,_link("modules/"+cmdX[1]),true);
            } else {
              lgksOverlayFrame(_link("popup/"+cmdX[1]),title,function() {
                  hideLoader();
                });
            }
          }
        break;
        default:
          if(typeof window[cmd]=="function") {
            window[cmd](src);
          } else {
            console.warn("Report CMD not found : "+cmd);
          }
    }
  });
	
	initInfoviewFields();
});
function viewpaneContentShown(src) {
	callBack=$(src).data("onshowncallback");
	href=$(src).attr("href");
	if(callBack!=null && typeof window[callBack]=="function") {
		window[callBack](src);
	} else {
		infoTableGrid=$(src).closest(".infoviewContainer").find(href).find(".infoTableGrid");
		cmdCallback=$(infoTableGrid).data("cmd");
		if(cmdCallback!=null && typeof window[cmdCallback]=="function") {
			window[cmdCallback](infoTableGrid,cmdCallback);
        } 
	}
}
function initInfoviewFields() {
	$("form select[data-value]",".infoTableView").each(function() {this.value=$(this).data('value');});
	
	$("form .nodb",".infoTableView").each(function() {
		$(this).attr("name","");
	});
	
	$("input.field-date",".infoTableView").each(function() {
		$(this).datetimepicker({
				format: 'DD/MM/YYYY'
			});
		});
	$("input.field-datetime",".infoTableView").each(function() {$(this).datetimepicker({
				format: 'DD/MM/YYYY HH:ss'
			});
		});
	$("input.field-year",".infoTableView").each(function() {
			//$(this).val("");
			$(this).datetimepicker({
				format: 'YYYY'
			});
		});
	$("input.field-month",".infoTableView").each(function() {
			//$(this).val("");
			$(this).datetimepicker({
				format: 'DD/MM'
			});
		});
	$("input.field-time",".infoTableView").each(function() {
			//$(this).val("");
			$(this).datetimepicker({
				format: 'HH:ss'
			});
		});
	$("input.field-date,input.field-datetime,input.field-year,input.field-month,input.field-time",".infoTableView").on("dp.show",function (e) {
    $(this).closest(".infoview-table").addClass("open-table");
	});
	$("input.field-date,input.field-datetime,input.field-year,input.field-month,input.field-time",".infoTableView").on("dp.hide",function (e) {
    $(this).closest(".infoview-table").removeClass("open-table");
	});
}
