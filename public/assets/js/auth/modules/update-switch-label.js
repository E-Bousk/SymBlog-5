/**
 * Update the switch and it's label depending on server response.
 * 
 * @param {bool} is_guard_checking_ip Whether or not to verify IP adress during authentication process.
 */
export default function updateSwitchAndLabel(is_guard_checking_ip) {
    document.body.querySelector('label[for="check_user_ip_checkbox"]').textContent = is_guard_checking_ip ? "Active" : "Inactive";
    document.body.querySelector('input[id="check_user_ip_checkbox"]').checked = is_guard_checking_ip;

}