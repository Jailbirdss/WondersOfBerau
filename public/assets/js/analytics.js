(function() {
    'use strict';
    
    function trackPageView() {
        const data = {
            page_url: window.location.href,
            page_title: document.title,
            referrer: document.referrer || 'direct'
        };
        
        fetch('api/track.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log('Analytics tracked successfully');
            }
        })
        .catch(error => {
            console.error('Analytics tracking error:', error);
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', trackPageView);
    } else {
        trackPageView();
    }
    
    let lastUrl = location.href;
    new MutationObserver(() => {
        const url = location.href;
        if (url !== lastUrl) {
            lastUrl = url;
            trackPageView();
        }
    }).observe(document, {subtree: true, childList: true});
})();