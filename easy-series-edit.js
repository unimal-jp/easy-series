jQuery(document).ready(function($) {

	//from wp_localize_script()
	var prefix = easy_series.plugin_prefix;
	var series_list = easy_series.series_list;

	var get_series = function(id) {
		for (var i = 0; i < series_list.length; i++) {
			if (series_list[i].id == id) {
				return series_list[i];
			}
		}
		return null;
	};

	var get_series_number_of_times = function(id) {
		var series = get_series(id);
		if (series == null) {
			return null;
		}
		return series.number_of_times;
	};

	var get_series_name = function(id) {
		var series = get_series(id);
		if (series == null) {
			return null;
		}
		return series.title;
	};

	//(1-1)
	var set_series_and_number_data = function(series_id, series_number) {
		$('.' + prefix + 'id').val(series_id);
		$('.' + prefix + 'number').val(series_number);		
	}

	//(1-2)
	var set_series_and_number_text = function(series_id, series_number) {
		if ((series_id == null) || (series_id == '')) {
			$('.' + prefix + 'info').css('display', 'none');		//hide
			$('.' + prefix + 'info-not-set').css('display', '');	//show
		} else {
			var series_name = get_series_name(series_id);

			$('.' + prefix + 'name').html(series_name);
			$('.' + prefix + 'display-number').html(series_number);

			$('.' + prefix + 'info').css('display', '');				//show
			$('.' + prefix + 'info-not-set').css('display', 'none');	//hide
		}
	}

	//(1-3)
	var show_hide_unset_button = function(series_id) {
		if ((series_id == null) || (series_id == '')) {
			$('.' + prefix + 'delete-btn').css('display', 'none');	//hide
		} else {
			$('.' + prefix + 'delete-btn').css('display', '');		//show
		}
	}

	//(2-1)
	var set_series_select = function(series_id) {
		if ((series_id == null) || (series_id == '')) {
			series_id = '#NONE#';
		}
		var hoge = $('.' + prefix + 'series-select');
		$('.' + prefix + 'series-select').val(series_id);
	}

	//(2-2)
	var display_number_of_times = function(series_id) {
		if ((series_id == null) || (series_id == '')) {
			series_id = '#NONE#';
		}

		if (series_id == '#NONE#') {
			$('.' + prefix + 'display-number-of-times').html('');
			return;
		}

		var number_of_times = get_series_number_of_times(series_id);

		if (number_of_times == null) {
			$('.' + prefix + 'display-number-of-times').html('');
		} else if (number_of_times == 0) {
			$('.' + prefix + 'display-number-of-times').html('(回数未定)');
		} else {
			$('.' + prefix + 'display-number-of-times').html('(全' + number_of_times + '回)');
		}		
	}

	//(2-3-1)
	var prepare_number_select = function(series_id) {
		if ((series_id == null) || (series_id == '')) {
			series_id = '#NONE#';
		}

		//clear options
		$('.' + prefix + 'number-select > option').remove();
		$('.' + prefix + 'number-select').append($('<option>').html('— 選択 —').val('#NONE#'));

		if (series_id == '#NONE#') {
			$('.' + prefix + 'number-select').css('display', 'none'); //hide
			return;
		}

		var number_of_times = get_series_number_of_times(series_id);
		if (number_of_times == 0) { //User input with number input.
			$('.' + prefix + 'number-select').css('display', 'none'); //hide
			return;
		}

		$('.' + prefix + 'number-select').css('display', ''); //show

		for (var i=0; i < number_of_times; i++) {
			$('.' + prefix + 'number-select').append($('<option>').html(i + 1).val(i + 1));
		}
	}

	//(2-3-2)
	var set_number_select = function(series_number) {
		if ((series_number == null) || (series_number == '')) {
			series_number = '#NONE#';
		}

		$('.' + prefix + 'number-select').val(series_number);
	}

	//(2-4-1)
	var prepare_number_input = function(series_id) {
		if ((series_id == null) || (series_id == '')) {
			series_id = '#NONE#';
		}

		if (series_id == '#NONE#') {
			$('.' + prefix + 'number-input').css('display', ''); //show
			$('.' + prefix + 'number-input').prop('disabled', true); //disable
			return;
		}

		$('.' + prefix + 'number-input').prop('disabled', false); //enable

		var number_of_times = get_series_number_of_times(series_id);
		if (number_of_times != 0) { //User input with select.
			$('.' + prefix + 'number-input').css('display', 'none'); //hide
			return;
		}

		$('.' + prefix + 'number-input').css('display', ''); //show
	}

	//(2-4-2)
	var set_number_input = function(series_number) {
		if ((series_number == null) || (series_number == '')) {
			series_number = '';
		}

		$('.' + prefix + 'number-input').val(series_number);
	}

	//(1)
	var set_data = function(series_id, series_number) {
		//(1-1)
		set_series_and_number_data(series_id, series_number);

		//(1-2)
		set_series_and_number_text(series_id, series_number);

		//(1-2)
		show_hide_unset_button(series_id);
	};

	//(2)
	var set_inputs = function(series_id, series_number) {
		//(2-1)
		set_series_select(series_id);
	
		//(2-2)
		display_number_of_times(series_id);
	
		//(2-3-1)
		prepare_number_select(series_id);
	
		//(2-3-2)
		set_number_select(series_number);
	
		//(2-4-1)
		prepare_number_input(series_id);
	
		//(2-4-2)
		set_number_input(series_number);
	}

	//set button - click handler
	$('.' + prefix + 'set-btn').click(function(event)　{
		event.preventDefault();

		//check values

		//series
		var series_id = $('.' + prefix + 'series-select').val();
		if (series_id == '#NONE#') {
			alert('連載を選択してください。');
			return;
		}
		
		//number
		var number_of_times = get_series_number_of_times(series_id);
		var series_number;
		if (number_of_times == 0) {
			series_number = $('.' + prefix + 'number-input').val();
		} else {
			series_number = $('.' + prefix + 'number-select').val();
			
		}

		if ((series_number == null) || (series_number == '') || (series_number == '#NONE#')) {
			alert('回を指定してください。');
			return;			
		}

		//Check whether series_id and number are already used by another post via ajax.
		var mysack = new sack(ajaxurl);

		
		mysack.execute = 1;
		mysack.method = 'POST';
		mysack.setVar("action", "easy_series_check_series_and_number");
		mysack.setVar(prefix + "id", series_id);
		mysack.setVar(prefix + "number", series_number);
		mysack.onError = function() { alert('通信エラー'); };
		mysack.onCompletion = function() {			
			if (easy_series_ajax_result != 200) {
				alert(easy_series_ajax_error_message);
				return;
			}
				
			if (easy_series_ajax_error_message != 'USED') {
				//(1)
				set_data(series_id, series_number);
				return;
			} 

    		if (confirm("指定した連載の回はすでに別の記事に設定されています。\n変更してもよろしいですか？\n\nただし記事を保存するまで変更は反映されません。") == true) {
				//(1)
				set_data(series_id, series_number);    			
    		}
		};
		
		mysack.runAJAX();
	});

	//series select - change hander
	$('.' + prefix + 'series-select').change(function() {
		var series_id = $(this).val();

		var series_number = ''; //reset number

		//(2)
		set_inputs(series_id, series_number);
	});

	//delete button - click handler
	$('.' + prefix + 'delete-btn').click(function(event)　{
		event.preventDefault();

		var series_id = '';
		var series_number = '';

		//(1)
		set_data(series_id, series_number);

		//(2)
		set_inputs(series_id, series_number);				
	});

//init
	var series_id = $('.' + prefix + 'id').val();
	var series_number = $('.' + prefix + 'number').val();

	if ((series_id == null) || (series_id == '')) {
		var series_number = '';
	} 
		
	//(1)
	set_data(series_id, series_number);

	//(2)
	set_inputs(series_id, series_number);	
});

//ajax var
var easy_series_ajax_result = 0;
var easy_series_ajax_error_message = '';
