/**
 * Retrieves JSON page data embedded in the HTML.
 * The data is expected to be stored in an element with the ID "JSON_PAGE_DATA".
 *
 * @returns {Object|null} - The parsed JSON object if the data exists, otherwise `null`.
 */
export const getPageData = () => {
    const jpd = document.querySelector('[id="JSON_PAGE_DATA"]')?.innerText;
    return jpd ? JSON.parse(jpd) : null;
}
