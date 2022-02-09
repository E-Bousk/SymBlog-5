const check_user_ip_checkbox = document.body.querySelector('input[id="check_user_ip_checkbox"]');

check_user_ip_checkbox.addEventListener('change', toggleCheckingIp);

document.body.querySelector('button[id="add_current_ip_to_whitelist_button"]').addEventListener('click', addCurrentIpToWhitelist);

/**
 * Enable/disable user's IP address verification during authentication process within an AJAX call.
 */
function toggleCheckingIp() {
    const check_user_ip_label = document.body.querySelector('label[for="check_user_ip_checkbox"]');

    const controller_url = this.getAttribute('data-url');

    const fetch_options = {
        body: JSON.stringify(check_user_ip_checkbox.checked),
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        method: 'POST'
    };

    fetch(controller_url, fetch_options)
        .then(response => response.json())
            .then(({isGuardCheckingIp}) => check_user_ip_label.textContent = isGuardCheckingIp ? "Activé" : "Inactivé")
                .catch(error => console.error(error))
    ;
}

/**
 * Add the current IP address to whitelist within an AJAX call.
 */
function addCurrentIpToWhitelist() {
    const user_ip_addresses = document.body.querySelector('p[id="user_ip_addresses"]');

    const controller_url = this.getAttribute('data-url');

    const fetch_options = {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        method: 'GET'
    };

    fetch(controller_url, fetch_options)
        .then(response => response.json())
        .then(({user_ip}) => {
            if (user_ip_addresses.textContent === '') {
                user_ip_addresses.textContent = user_ip
            } else {
                if (!user_ip_addresses.textContent.includes(user_ip)) {
                    user_ip_addresses.textContent += ` | ${user_ip}`
                }
            }
        })
        .catch(error => console.error(error))
    ;
}