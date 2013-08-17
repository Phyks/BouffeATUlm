function set_days_month_year() {
    
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
