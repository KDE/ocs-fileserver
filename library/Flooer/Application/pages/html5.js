var html5elements = array(
    'abbr', 'article', 'aside', 'audio', 'canvas', 'datalist',
    'details', 'eventsource', 'figure', 'footer', 'header', 'hgroup',
    'mark', 'menu', 'meter', 'nav', 'output', 'progress', 'section',
    'time', 'video'
);

for (i = 0; i < html5elements.length; i++) {
    document.createElement(html5elements[i]);
}
