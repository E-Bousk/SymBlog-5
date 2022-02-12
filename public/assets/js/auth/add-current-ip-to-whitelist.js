import ConfirmIdentity from "./modules/confirm-identity.js";

new ConfirmIdentity({
    controller_url: document.body.querySelector('button[id="add_current_ip_to_whitelist_button"]').getAttribute('data-url'),
    element_to_listen: document.body.querySelector('button[id="add_current_ip_to_whitelist_button"]'),
    fetch_options: {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        method: 'GET'
    },
    type_of_event: "click"
});