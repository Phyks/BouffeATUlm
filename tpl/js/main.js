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
    if(document.getElementById('guest_user_'+id).value > 1)
        document.getElementById('guest_user_'+id+'_label').innerHTML = ' guests';
    else
        document.getElementById('guest_user_'+id+'_label').innerHTML = ' guest';
}

function toggle_password(id) {
    if(document.getElementById(id).type == 'password')
        document.getElementById(id).type = 'text';
    else
        document.getElementById(id).type = 'password';
}
