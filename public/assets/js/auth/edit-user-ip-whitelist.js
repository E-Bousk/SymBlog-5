import ConfirmIdentity from "./modules/confirm-identity.js";

new ConfirmIdentity({
    controller_url: document.body.querySelector('p[id="user_ip_addresses"]').getAttribute('data-url'),
    element_to_listen: document.body.querySelector('p[id="user_ip_addresses"]'),
    fetch_options: {
        body: null,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'User-Ip-Entered': 'true'
        },
        method: 'POST'
    },
    type_of_event: "keydown"
});