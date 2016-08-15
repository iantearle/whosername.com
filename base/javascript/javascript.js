$(function() {
    $('.chart').easyPieChart({
        //your options goes here
        barColor: '#317D95',
        scaleColor: false,
        lineWidth: 10
    });
	$("#website").blur(function() {
		var input = $(this);
		var val = input.val();
		if(val && !val.match(/^http([s]?):\/\/.*/)) {
			input.val('http://' + val);
		}
	});
	$("#twitter").bind("keypress", function(event) {
	    var charCode = event.which;
	    if(charCode <= 13) {
	    	return true;
	    }

	    var keyChar = String.fromCharCode(charCode);

	    return /[a-zA-Z_]/.test(keyChar);
	});

	var options = {
	    valueNames: ['label-googleplus', 'label-linkedin', 'label-facebook', 'label-twitter']
	};
	var editList = new List('edit-list', options);
});
$(document).on('focus click', 'input',  function(e){
    this.selectionStart = this.selectionEnd = this.value.length;
});

jQuery(function($) {
    $('[data-numeric]').payment('restrictNumeric');
    $('.cc-number').payment('formatCardNumber');
    $('.cc-exp').payment('formatCardExpiry');
    $('.cc-cvc').payment('formatCardCVC');

    $('.cc-exp, .cc-number, .cc-cvc').focus(function(){
        var that = this;
        setTimeout(function(){ that.selectionStart = that.selectionEnd = 10000; }, 0);
    });


    $.fn.toggleInputError = function(erred) {
        this.parent('.form-group').toggleClass('has-error', erred);
        return this;
    };

    $('#subscription-form').submit(function(e) {
        e.preventDefault();

        var cardType = $.payment.cardType($('.cc-number').val());
        $('.cc-number').toggleInputError(!$.payment.validateCardNumber($('.cc-number').val()));
        $('.cc-exp').toggleInputError(!$.payment.validateCardExpiry($('.cc-exp').payment('cardExpiryVal')));
        $('.cc-cvc').toggleInputError(!$.payment.validateCardCVC($('.cc-cvc').val(), cardType));
        $('.cc-brand').text(cardType);
    });

});


 $(function() {
      var $form = $('#subscription-form');
      $form.submit(function(event) {
        // Disable the submit button to prevent repeated clicks:
        $form.find('.submit').prop('disabled', true);

        expiration = $('.cc-exp').payment('cardExpiryVal');

        // Request a token from Stripe:
        Stripe.card.createToken({
    		number: $('.cc-number').val(),
    		cvc: $('.cc-cvc').val(),
    		exp_month:  (expiration.month || 0),
    		exp_year:  (expiration.year || 0)
    	}, stripeResponseHandler);

        // Prevent the form from being submitted:
        return false;
      });
    });



	function stripeResponseHandler(status, response) {
		var $form = $('#subscription-form');

		if(response.error) {
			// Show the errors on the form
			$form.find('.payment-errors').addClass('alert alert-danger').text(response.error.message);
			$form.find('button').prop('disabled', false);
		} else {
			// response contains id and card, which contains additional card details
			var token = response.id;
			// Insert the token into the form so it gets submitted to the server
			$form.append($('<input type="hidden" name="stripeToken" />').val(token));
			// and submit
			$form.get(0).submit();
		}
	}
