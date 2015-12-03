jQuery(document).ready(function($) {

  var args = {
    api_endpoint:'../pledge_premiums/',
    form_type: 'sustainer'
  }

  if (pbs_passport_authenticate_args) {
    args = pbs_passport_authenticate_args;
  }

  function getAJAXData(argsarry) {
    var requrl = args.api_endpoint;
    $.ajax({
        url: requrl,
        dataType: 'json',
        data: argsarry,
        beforeSend: function() {
            $('#cboxLoadedContent').empty();
            $('#cboxLoadingGraphic').show();
        },
        complete: function() {
            $('#cboxLoadingGraphic').hide();
        },
        success: function(response) {
          updatePledgePremiumOverlay(response);
        }             
    });
  }

  function updatePledgePremiumOverlay(response) {
    var output = '<div id="pledge_overlay"><h2>Click on a choice to see available premiums</h2><h3><ul><li><a class="premium" data-pcode="" data-price=0><span class="title">No gift, I want all of my pledge to go towards supporting this station</span></a></li></ul></h3><div id="pledge_premiums_list">';
    $.each(response, function(index, program) {
      output += '<h3>' + program.label + '</h3><div>';
      output += formatPremiumList(program);
      output += '</div>';
    });
    output += '</div></div>';
    $('#cboxContent').html(output);
	  $('#pledge_premiums_list').accordion({
      active: false,
      collapsible: true,
      heightStyle: "content",
      animate: false,
      icons: { "header": "fa fa-caret-square-o-down", "activeHeader": "fa fa-caret-square-o-up" },
      activate: function( event, ui ) {
        setTimeout(cbresize, 100);
      }      
    });
    $('#pledge_overlay li a.premium').click(function(event) {
      event.preventDefault();
      $('#wnet_pledge_premiums button.launch').html("Change Selected Premium <i class='fa fa-minus-circle'></i>");    
      $('#wnet_pledge_premiums div.messages').html("Selected Premium: <b>"+ $("span.title", this).text() + "</b>").css({opacity: 1});
      $('#wnet_pledge_premiums input#pcode').val($(this).attr("data-pcode"));
      $('#wnet_pledge_premiums input#req_amt').val($(this).attr("data-price"));
      $.colorbox.remove();
    })
  };

  
  function cbresize() {
    $.colorbox.resize(cboxOptions);
  }


  function launchPassportOverlay(targetobj) {
    $.colorbox(cboxOptions);
    showChooserScreen();
  }


  var cboxOptions = {
      inline: true,
      closeButton: false, 
      height: "90%",
      width: "90%",
      initialWidth: "90%",
      initialHeight: "90%",
      maxWidth: '90%',
      maxHeight: '90%',
      scrolling: true
    }

	$('body').on('click', '.pbs_passport_authenticate button.launch', function(event) {	
    	event.preventDefault();
	    launchPassportOverlay( $(this) );
  });

  $(window).resize(function(){
    $.colorbox.resize({
      width: window.innerWidth > parseInt(cboxOptions.maxWidth) ? cboxOptions.maxWidth : cboxOptions.width,
      height: window.innerHeight > parseInt(cboxOptions.maxHeight) ? cboxOptions.maxHeight : cboxOptions.height
    });
  });
});
