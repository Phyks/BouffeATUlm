function invites()
{
	var invites_input = document.getElementById('invites');
	var invites_label = document.getElementById('invites_label');
	
	invites_input.onkeyup = function ()
	{
		if(invites_input.value > 1)
		{
			invites_label.innerHTML = 'invités';
		}
		if(invites_input.value <= 1)
		{
			invites_label.innerHTML = 'invité';
		}
	};
}

if(document.getElementById && document.createTextNode)
{
	window.onload = function()
	{
		invites();
	};
}
