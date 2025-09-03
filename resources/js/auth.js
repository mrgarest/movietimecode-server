import { getPageData } from "./json-page-data";

const pageData = getPageData();

/**
 * Handles incoming postMessage events.
 * If the message is from the "auth" source, displays a success message.
 * @param {MessageEvent} event - The message event object.
 */
const onMessage = (event) => {
    if (event.data?.source !== "auth") return;
    switch (event.data?.type) {
        case "success":
            setText('Авторизація успішно виконана. Ви можете закрити цю сторінку.');
            break;
        case "error":
            setText('Помилка авторизації');
            break;
        default:
            break;
    }
};

/**
 * Sets the text content of the container and removes the message event listener.
 * @param {string} text - The text to display in the container.
 */
const setText = (text) => {
    window.removeEventListener('message', onMessage);
    document.getElementById('container').innerHTML = `<div class="text-2xl text-foreground font-medium text-center p-4">${text}</div>`;
};

if (pageData?.error) setText(pageData.error);
else if (pageData?.auth) {
    window.history.replaceState(null, '', window.location.href.split('?')[0]);

    window.addEventListener('message', onMessage);

    window.onload = () => {
        setTimeout(() => window.postMessage({
            source: 'auth',
            auth: pageData.auth
        }, '*'), 800);
        setTimeout(() => setText('Помилка авторизації. Спробуйте ще раз'), 180000);
    };
} else setText('Помилка авторизації');