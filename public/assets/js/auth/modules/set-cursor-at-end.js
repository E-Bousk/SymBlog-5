/**
 * Set cursor at the end of editable element.
 * 
 * @param {HTMLElement} element The editable element.
 */
export default function setCursorAtEnd(element) {
    const range = document.createRange();
    const selection = window.getSelection();
    range.setStart(element, 1);
    range.collapse(true);
    selection.removeAllRanges();
    selection.addRange(range);
}