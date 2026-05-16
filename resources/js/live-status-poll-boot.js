import { initLiveStatusPoll } from './live-status-poll-core.js';

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initLiveStatusPoll(document));
} else {
    initLiveStatusPoll(document);
}
