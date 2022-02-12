import ConfirmIdentity from "./modules/confirm-identity.js";

new ConfirmIdentity({
    controller_url: document.body.querySelector('input[id="check_user_ip_checkbox"]').getAttribute('data-url'),
    element_to_listen: document.body.querySelector('input[id="check_user_ip_checkbox"]'),
    fetch_options: {
        body: null,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Switch-Guard-Checking-Ip-Value': 'true'
        },
        method: 'POST'
    },
    type_of_event: "change"
});