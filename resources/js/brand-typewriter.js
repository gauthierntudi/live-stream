function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * @param {HTMLElement} el
 * @returns {HTMLElement}
 */
function getBrandTypewriterTextEl(el) {
    return el.querySelector('.brand-typewriter-text') ?? el;
}

/**
 * @param {HTMLElement} el
 * @returns {string[]}
 */
function readBrandTypewriterWords(el) {
    const raw = el.getAttribute('data-words');
    if (raw) {
        try {
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed) && parsed.length > 0) {
                return parsed.map(String);
            }
        } catch {
            /* attribut invalide */
        }
    }

    const alt = el.getAttribute('data-word-alt')?.trim() || 'Ize';
    return [alt, 'Revayah'];
}

/**
 * @param {HTMLElement} textEl
 * @param {string[]} words
 */
async function runBrandTypewriter(textEl, words) {
    const typeDelay = 110;
    const deleteDelay = 75;
    const holdMs = 2400;
    const betweenMs = 380;

    const reduced =
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!words.length) {
        return;
    }

    if (reduced) {
        if (words.length < 2) {
            textEl.textContent = words[0];
            return;
        }
        let i = 0;
        textEl.textContent = words[i];
        setInterval(() => {
            i = (i + 1) % words.length;
            textEl.textContent = words[i];
        }, 3200);
        return;
    }

    textEl.textContent = '';
    await sleep(280);

    let index = 0;
    for (;;) {
        const word = words[index % words.length];
        for (let len = 1; len <= word.length; len += 1) {
            textEl.textContent = word.slice(0, len);
            await sleep(typeDelay);
        }
        await sleep(holdMs);
        for (let len = word.length; len >= 0; len -= 1) {
            textEl.textContent = word.slice(0, len);
            await sleep(deleteDelay);
        }
        await sleep(betweenMs);
        index += 1;
    }
}

function initBrandTypewriter() {
    const el = document.getElementById('brand-typewriter');
    if (!el || el.dataset.brandTypewriterInit === '1') {
        return;
    }
    el.dataset.brandTypewriterInit = '1';
    const textEl = getBrandTypewriterTextEl(el);
    const words = readBrandTypewriterWords(el);
    void runBrandTypewriter(textEl, words);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBrandTypewriter);
} else {
    initBrandTypewriter();
}
