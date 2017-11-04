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
		switch(cmd) {
        case "forms":case "reports":case "infoview":
          hash=$(src).closest(".infoTableView").data('dcode');
          gkey=$(src).closest(".infoTableView").data('dtuid');
          title=$(src).text();
          if(title==null || title.length<=0) {
            title=$(src).attr("title");
          }
          if(title==null || title.length<=0) {
            title="Dialog";
          }

          cmdX=cmdOriginalX.split("@");
          if(cmdX[1]!=null) {
            cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{gkey}",gkey);

            if(cmd=="forms") {
		lgksOverlayFrame(_link("modules/"+cmd+"/"+cmdX[1]),title,function() {
	                hideLoader();
	          });
	    } else {
		showLoader();
	        lgksOverlayURL(_link("popup/"+cmd+"/"+cmdX[1]),title,function() {
	                hideLoader();
	              });
	    }
          }
        break;
        case "page":
          hash=$(src).closest(".infoTableView").data('dcode');
          gkey=$(src).closest(".infoTableView").data('dtuid');
          title=$(src).text();
          if(title==null || title.length<=0) {
            title=$(src).attr("title");
          }
          if(title==null || title.length<=0) {
            title="Dialog";
          }

          cmdX=cmdOriginalX.split("@");
          if(cmdX[1]!=null) {
            cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{gkey}",gkey);
            window.location=_link("modules/"+cmdX[1]);
          }
          break;
        case "module":case "popup":
          hash=$(src).closest(".infoTableView").data('dcode');
          gkey=$(src).closest(".infoTableView").data('dtuid');
          title=$(src).text();
          if(title==null || title.length<=0) {
            title=$(src).attr("title");
          }
          if(title==null || title.length<=0) {
            title="Dialog";
          }

          cmdX=cmdOriginalX.split("@");
          if(cmdX[1]!=null) {
            cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{gkey}",gkey);

            if(cmd=="module" || cmd=="modules") {
              top.openLinkFrame(title,_link("modules/"+cmdX[1]),true);
            } else {
              showLoader();
              lgksOverlayURL(_link("popup/"+cmdX[1]),title,function() {
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
});
function viewpaneContentShown(src) {
	callBack=$(src).data("onshowncallback");
	if(callBack!=null && typeof window[callBack]=="function") {
		window[callBack](src);
	}
}
