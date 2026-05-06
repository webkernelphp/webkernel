window.fetch = ((originalFetch) => {
    return (url, options = {}) => {
        options.headers = {
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers,
        };
        return originalFetch(url, options);
    };
})(window.fetch);
