;(function($) { 
	$.entwine('sws', function($){

		$('select.exchangerate').entwine({

			onmatch : function() {
				var self = this;
				var form = this.closest('form');

				this.on('change', function(e){
					form.submit();
				});

				this._super();
			},

			onunmatch: function() {
				this._super();
			},
		});

	});
})(jQuery);
