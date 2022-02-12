/**
 * Update the IP adresses whitelist (on table) and hide modal.
 * 
 * @param {string|null} user_ip IP adress of user.
 */
export default function updateWhitelistIpAddresses(user_ip) {
    const user_ip_adresses = document.body.querySelector('p[id="user_ip_addresses"]');

    user_ip_adresses.textContent = user_ip;
}