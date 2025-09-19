/**
 * Formats a date/time into a specified format
 * @param {string|number|Date} dateInput - Date string, timestamp, or Date object
 * @param {string} format - Desired format string (e.g., 'YYYY-MM-DD HH:mm:ss')
 * @param {string} [locale='en-US'] - Locale for formatting
 * @returns {string} - Formatted date string
 */
export function formatDateTime(dateInput, format = 'YYYY-MM-DD HH:mm:ss', locale = 'en-US') {
  if (!dateInput) return '';

  const date = new Date(dateInput);

  if (isNaN(date.getTime())) {
    throw new Error('Invalid date provided');
  }

  const tokens = {
    YYYY: date.getFullYear(),
    MM: String(date.getMonth() + 1).padStart(2, '0'),
    DD: String(date.getDate()).padStart(2, '0'),
    HH: String(date.getHours()).padStart(2, '0'),
    mm: String(date.getMinutes()).padStart(2, '0'),
    ss: String(date.getSeconds()).padStart(2, '0'),
    dddd: date.toLocaleDateString(locale, { weekday: 'long' }),
    MMM: date.toLocaleDateString(locale, { month: 'short' }),
    MMMM: date.toLocaleDateString(locale, { month: 'long' }),
  };

  return format.replace(/YYYY|MM|DD|HH|mm|ss|dddd|MMM|MMMM/g, match => tokens[match]);
}
