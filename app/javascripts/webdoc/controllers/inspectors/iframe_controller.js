/**
 * @author Noé
 */

WebDoc.IframeController = $.klass({
  initialize: function( selector ) {
    this.domNode = $(selector);

    $("#property-iframe-src").change(this.updateSrc);

  },

  refresh: function() {
    ddd("refresh iframe inspector");
    var selectedItem = WebDoc.application.boardController.selection()[0];
    if (selectedItem.item.data.media_type === WebDoc.ITEM_TYPE_IFRAME) {
      $("#property-iframe-src")[0].value = selectedItem.item.getSrc();
    }
  },

  updateSrc: function(e) {
    var input = jQuery(e.target);
    
    input.validate({
      pass: function( value ){
        var item = WebDoc.application.boardController.selection()[0].item;
        var pattern_has_protocole = /^(ftp|http|https):\/\//;
        var consolidateSrc = '';
        if (value.match(pattern_has_protocole)) {
  		    consolidateSrc = value;
        }
        else {
  		    consolidateSrc = "http://" + value;
        }
                
        item.setSrc( consolidateSrc );
      },
      fail: function( value, error ){
        ddd(error);
      }
    });
    

  }
  
});
