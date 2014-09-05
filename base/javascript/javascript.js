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