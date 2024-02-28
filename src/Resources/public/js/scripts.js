var ModalOffer = function ModalOffer(args){
	var modal = this;
	modal.prototype = Object.create(ModalOffer.prototype);
  	modal.prototype.constructor = ModalOffer;

	modal.name    = (args.name !== undefined)    ? args.name    : 'modalOffer-'+(Math.floor(Math.random() * (9999 - 1000) + 1000));
	modal.content = (args.content !== undefined) ? args.content : '';
	modal.width   = (args.width !== undefined)   ? args.width   : false;
	modal.onOpen  = (args.onOpen !== undefined)  ? args.onOpen  : function(){};
	modal.onClose = (args.onClose !== undefined) ? args.onClose : function(){};

	// html construct
	modal.$el = $('<div class="modalOffer"></div>');
	modal.$el.attr('data-name',modal.name);
	modal.$wrapper = $('<div class="modalOffer__wrapper"></div>');
	modal.$header  = $('<div class="modalOffer__header"></div>');
	modal.$content = $('<div class="modalOffer__content"></div>');
	modal.$loader  = $('<div class="modalOffer__loader"><i class="fas fa-circle-notch fa-spin"></i></div>');
	modal.$close   = $('<div class="modalOffer__close"><i class="fas fa-times"></i></div>');
	modal.$header.append(modal.$close);
	modal.$wrapper
	    .append(modal.$header)
	    .append(modal.$loader)
	    .append(modal.$content)
	;
	if(modal.width)
	    modal.$wrapper.css({
	        'width' : modal.width,
	        'max-width' : '100%'
	    });
	modal.$el.get(0).innerHTML = '';
	modal.$el.append(modal.$wrapper);
    modal.$el.appendTo($('body'));

    modal.modalMouseDown;
    modal.modalMouseUp;
    modal.$el.on('mousedown',function(e){modalMouseDown = e.target;});
    modal.$el.on('mouseup',function(e){modalMouseUp = e.target;});

    modal.$close.on('click',function(e){
    	modal.close();
    })
    modal.$el.on('click',function(e){
    	if ($(e.target).hasClass('modalOffer') && modalMouseDown == modalMouseUp) {
            modal.close()
        } else if($(e.target).hasClass('modalOffer__close')){
            modal.close();
        }
    })

    modal.setContent();
    // console.log(modal);
	return modal;
}

ModalOffer.prototype.open = function(){
    var modal = this;
    $('.modalOffer.active .modalOffer__close').trigger('click');
    modal.$el.addClass('active');
    if(modal.onOpen)
        modal.onOpen();
    return modal;
};
ModalOffer.prototype.close = function(){
    var modal = this;
    modal.$el.removeClass('active');
    if(modal.onClose)
    	modal.onClose();
    return modal;
};
ModalOffer.prototype.setContent = function(){
    var modal = this;
    return new Promise(function(resolve,reject){
        if(modal.content != ''){
            modal.$content.html(modal.content);
            modal.$el.addClass('ready');
        }
        resolve();
    });
    return modal;
};
ModalOffer.prototype.destroy = function() {
  this.$el.remove();
};



var Modal = ModalOffer;
window.addEventListener("load", function(e) {
	// Modal = app.ModalFW;
	$('body').on('click', '.offer__action', function(e) {
		var btn = {
			$el: $(this)
		};
		btn.$el.addClass('no-events');
		btn.process = window[btn.$el.attr('data-process')];
		if(typeof btn.process == "function"){
			btn.process(btn).then(function(data){
				btn.$el.removeClass('no-events');
			}).catch(function(data){
				btn.$el.removeClass('no-events');
				throw Error(data);
			});
		}
	});


	$('.mod_offerslist .filter').on('change',function(){
		$(this).closest('form').submit();
	});
	$('body').on('click','.offer__gallery .offer__figure',function(){
		$(this).closest('.offer__details').find('.offer__mainPicture img')
			.attr('src',$(this).find('img').attr('src'))
			.attr('alt',$(this).find('img').attr('alt'));
		$('.offer__gallery .offer__figure').removeClass('active');
		$(this).addClass('active');
	})
});