const dino = document.body.querySelector('div[id="dino"]');
const cactus = document.body.querySelector('div[id="cactus"]');
const injuries_counter_cell = document.body.querySelector('td');
const FEET_TOUCH_CACTUS = parseInt(window.getComputedStyle(dino).getPropertyValue('height'), 10);
const TAIL_TOUCH_CATUS =  parseInt(window.getComputedStyle(dino).getPropertyValue('left'), 10);
const HEAD_TOUCH_CACTUS = TAIL_TOUCH_CATUS + 60

let dino_injuries_counter = 0;

const jump_events = ['mousedown', 'keyup'];

jump_events.forEach((event) => document.addEventListener(event, dinoJump));

setInterval(() => isDinoInjuried(), 100);

/**
 * Make Dino jump
 * @param {string} key The key pressed if exists.
 */
function dinoJump({key}) {
    if ((key && key !== 'ArrowUp' && key !== ' ')) {
        return;
    }
    dino.classList.add('dino-jump');
    dino.addEventListener('animationend', () => dino.classList.remove('dino-jump'));
}

/**
 * Check if Dino is injuried.
 */
function isDinoInjuried(){
    const dino_position_bottom = parseInt(window.getComputedStyle(dino).getPropertyValue('bottom'), 10);
    const cactus_left_position = parseInt(window.getComputedStyle(cactus).getPropertyValue('left'), 10);

    if (
        (dino_position_bottom <= FEET_TOUCH_CACTUS)
        && (cactus_left_position <= HEAD_TOUCH_CACTUS)
        && (cactus_left_position >= TAIL_TOUCH_CATUS)
    ) {
        dinoIsInjuried();
    } else {
        dinoIsNotInjuried();
    }
}

function dinoIsInjuried() {
    dino_injuries_counter++;
    injuries_counter_cell.textContent = dino_injuries_counter;

    dino.classList.add('injuries');
}

function dinoIsNotInjuried() {
    dino.classList.remove('injuries');
}