	function reloadAukroAttributes(value,url,attributes) {
		if (value != -1) {
			new Ajax.Request(url, {
				method: 'Post',
				onSuccess: function(response) {
					document.getElementById('aukro_aukro_attributes_fieldset').update(response.responseText);
				},
				parameters: {'category': value,'attributes':attributes}
			});
		}
	}