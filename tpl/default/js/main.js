function set_days_month_year() {
    var date_day = $('#date_day option:selected').val();
    var date_month = $('#date_month option:selected').val();
    var date_year = $('#date_year option:selected').val();

    // Handle day for month
    if(["4", "6", "9", "11"].indexOf(date_month) > -1) {
        (! $('#date_day options[value=29]').length) && $('#date_day').append('<option value="29">29</option>');
        (! $('#date_day options[value=30]').length) && $('#date_day').append('<option value="30">30</option>');
        $('#date_day option[value=31]').remove();
    }
    else if(date_month == "2") {
        // Handle bissextile years
        $('#date_day option[value=30]').remove();
        $('#date_day option[value=31]').remove();

        if(date_year % 4 != 0 || date_year % 400 == 0) {
            $('#date_day option[value=29]').remove();
        }
    }
    else {
        (! $('#date_day options[value=29]').length) && $('#date_day').append('<option value="29">29</option>');
        (! $('#date_day options[value=30]').length) && $('#date_day').append('<option value="30">30</option>');
        (! $('#date_day options[value=31]').length) && $('#date_day').append('<option value="31">31</option>');
    }
}

function guest_user_label(id) {
    if(document.getElementById('guest_user_'+id).value > 1) {
        document.getElementById('guest_user_'+id+'_label_plural').innerHTML = 's';

        if(!document.getElementById('users_in_'+id).checked) {
            document.getElementById('users_in_'+id).checked = true;
        }
    }
    else
        document.getElementById('guest_user_'+id+'_label_plural').innerHTML = '';
}

function toggle_password(id) {
    if(document.getElementById(id).type == 'password')
        document.getElementById(id).type = 'text';
    else
        document.getElementById(id).type = 'password';
}

function payback_nobody() {
    var users = document.getElementById('global_payback_form').getElementsByClassName("users_in");

    for(var index = 0; index < users.length; index ++) {
        users[index].checked = false;
    }

    return false;
}

function payback_everybody() {
    var users = document.getElementById('global_payback_form').getElementsByClassName("users_in");

    for(var index = 0; index < users.length; index ++) {
        users[index].checked = true;
    }

    return false;
}

$(document).ready(function() {
    $('#balance_table td, #global_paybacks_table td').hover(function() {
        $(this).closest('tr').find('td,th').addClass('highlight_td');
        var col = $(this).index()+1;
        $(this).closest('table').find('tr :nth-child('+col+')').addClass('highlight_td');
    }, function() {
        $(this).closest('tr').find('td,th').removeClass('highlight_td');
        var col = $(this).index()+1;
        $(this).closest('table').find('tr :nth-child('+col+')').removeClass('highlight_td');
    });

    $('#balance_table tr:first-child th:not(:first-child), #global_paybacks_table tr:first-child th:not(:first-child)').hover(function() {
        var col = $(this).index()+1;
        $(this).closest('table').find('tr :nth-child('+col+')').addClass('highlight_td');
    }, function() {
        var col = $(this).index()+1;
        $(this).closest('table').find('tr :nth-child('+col+')').removeClass('highlight_td');
    });

    if($('#invoice_form').length) {
        $('#invoice_form input[id^=users_in]').each(function () {
            var id = $(this).attr('id').replace('users_in_', '');
            guest_user_label(id);
        });
    }
});
